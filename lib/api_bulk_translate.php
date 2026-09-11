<?php

declare(strict_types=1);

use FriendsOfREDAXO\WriteAssist\AutoTranslateService;
use FriendsOfREDAXO\WriteAssist\WriteAssistAiFactory;

/**
 * WriteAssist – Bulk Translate API
 *
 * Translates existing article and/or category names from a source language
 * into all other active clangs, via DeepL or - falls in den Einstellungen als
 * Provider gewählt - über den konfigurierten KI-Provider (gleicher Fallback
 * wie bei der Einzelübersetzung und der Auto-Übersetzung bei Neuanlage, siehe
 * AutoTranslateService::translateText()).
 *
 * Läuft in zwei Schritten, damit die Massenübersetzung bei vielen Artikeln
 * nicht an PHP's max_execution_time oder einem blockierenden Browser-Tab
 * scheitert (vorher: ein einziger Request übersetzte alle Artikel x alle
 * Zielsprachen komplett synchron durch):
 *
 *  action=collect        Ermittelt die Arbeitsliste (id, isCategory, targetClangId)
 *                         unter Berücksichtigung von only_untranslated, ohne zu
 *                         übersetzen. Antwort enthält die vollständige Liste.
 *  action=translate_batch Übersetzt eine vom Client übergebene Teilmenge dieser
 *                         Liste (ein "Batch"). Wird vom JS wiederholt aufgerufen,
 *                         bis alle Einträge abgearbeitet sind.
 *
 * POST params (collect):
 *  source_clang      int     ID of the source clang
 *  type              string  'articles' | 'categories' | 'both'
 *  only_untranslated int     1 = only translate where name is still identical to source
 *
 * POST params (translate_batch):
 *  source_clang      int     ID of the source clang
 *  items             string  JSON-kodierte Liste von {id, is_category, target_clang}
 */
class rex_api_writeassist_bulk_translate extends rex_api_function
{
    protected $published = false;

    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        if (!rex::getUser() || !rex::getUser()->isAdmin()) {
            rex_response::sendJson(['success' => false, 'error' => 'Keine Berechtigung']);
            exit;
        }

        $addon = rex_addon::get('writeassist');
        $providerType = $addon->getConfig('translation_provider', 'deepl');
        $aiConfigured = $providerType === 'ai' && WriteAssistAiFactory::factory()->isConfigured();
        $deeplConfigured = trim((string) $addon->getConfig('api_key', '')) !== '';

        if (!$aiConfigured && !$deeplConfigured) {
            rex_response::sendJson(['success' => false, 'error' => 'Kein Übersetzungs-Provider konfiguriert (weder DeepL-API-Key noch KI-Provider)']);
            exit;
        }

        $action = rex_post('action', 'string', 'collect');

        if ('translate_batch' === $action) {
            $this->translateBatch();
        } else {
            $this->collect();
        }

        exit;
    }

    /**
     * Ermittelt die vollständige Arbeitsliste, ohne zu übersetzen.
     */
    private function collect(): void
    {
        $sourceClangId    = (int) rex_post('source_clang', 'int', 0);
        $type             = rex_post('type', 'string', 'both');
        $onlyUntranslated = (bool) rex_post('only_untranslated', 'int', 0);

        // YRewrite SEO fields (title/description) are optional and only offered
        // when the YRewrite add-on is available (it provides the article columns).
        $yrewriteAvailable = rex_addon::get('yrewrite')->isAvailable();
        $seoTitle = $yrewriteAvailable && (bool) rex_post('seo_title', 'int', 0);
        $seoDescription = $yrewriteAvailable && (bool) rex_post('seo_description', 'int', 0);

        $sourceClang = rex_clang::get($sourceClangId);
        if (!$sourceClang) {
            rex_response::sendJson(['success' => false, 'error' => 'Ungültige Quellsprache']);
            return;
        }

        $targetClangIds = [];
        foreach (rex_clang::getAll() as $clang) {
            if ($clang->getId() !== $sourceClangId) {
                $targetClangIds[] = $clang->getId();
            }
        }

        if ([] === $targetClangIds) {
            rex_response::sendJson(['success' => false, 'error' => 'Keine weiteren Sprachen vorhanden']);
            return;
        }

        $prefix = rex::getTablePrefix();
        $items = [];
        $skipped = 0;
        $seoColumns = ($seoTitle ? ', yrewrite_title' : '') . ($seoDescription ? ', yrewrite_description' : '');

        if (in_array($type, ['articles', 'both'], true)) {
            $sql = rex_sql::factory();
            $sql->setQuery(
                'SELECT id, name' . $seoColumns . ' FROM ' . $prefix . 'article WHERE clang_id = :clang AND startarticle = 0',
                ['clang' => $sourceClangId]
            );

            foreach ($sql as $row) {
                $id = (int) $row->getValue('id');
                $sourceValues = ['name' => (string) $row->getValue('name')];
                if ($seoTitle) {
                    $sourceValues['yrewrite_title'] = (string) $row->getValue('yrewrite_title');
                }
                if ($seoDescription) {
                    $sourceValues['yrewrite_description'] = (string) $row->getValue('yrewrite_description');
                }
                self::collectItems($items, $skipped, $id, false, $sourceValues, $targetClangIds, $onlyUntranslated);
            }
        }

        if (in_array($type, ['categories', 'both'], true)) {
            $sql = rex_sql::factory();
            $sql->setQuery(
                'SELECT id, catname' . $seoColumns . ' FROM ' . $prefix . 'article WHERE clang_id = :clang AND startarticle = 1',
                ['clang' => $sourceClangId]
            );

            foreach ($sql as $row) {
                $id = (int) $row->getValue('id');
                $sourceValues = ['name' => (string) $row->getValue('catname')];
                if ($seoTitle) {
                    $sourceValues['yrewrite_title'] = (string) $row->getValue('yrewrite_title');
                }
                if ($seoDescription) {
                    $sourceValues['yrewrite_description'] = (string) $row->getValue('yrewrite_description');
                }
                self::collectItems($items, $skipped, $id, true, $sourceValues, $targetClangIds, $onlyUntranslated);
            }
        }

        rex_response::sendJson([
            'success' => true,
            'items'   => $items,
            'total'   => count($items),
            'skipped' => $skipped,
        ]);
    }

    /**
     * Übersetzt einen vom Client übergebenen Ausschnitt der Arbeitsliste.
     */
    private function translateBatch(): void
    {
        $sourceClangId = (int) rex_post('source_clang', 'int', 0);
        $sourceClang = rex_clang::get($sourceClangId);
        if (!$sourceClang) {
            rex_response::sendJson(['success' => false, 'error' => 'Ungültige Quellsprache']);
            return;
        }

        $itemsJson = rex_post('items', 'string', '[]');
        $items = json_decode($itemsJson, true);
        if (!is_array($items)) {
            rex_response::sendJson(['success' => false, 'error' => 'Ungültige Übergabe']);
            return;
        }

        $prefix = rex::getTablePrefix();
        $translated = 0;
        $errors = 0;
        $log = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id           = (int) ($item['id'] ?? 0);
            $isCategory   = (bool) ($item['is_category'] ?? false);
            $targetClangId = (int) ($item['target_clang'] ?? 0);
            $field        = (string) ($item['field'] ?? 'name');

            if (0 === $id || 0 === $targetClangId) {
                continue;
            }
            if (!in_array($field, ['name', 'yrewrite_title', 'yrewrite_description'], true)) {
                continue;
            }

            $sourceValue = self::fetchValue($id, $isCategory, $sourceClangId, $field);
            if (null === $sourceValue || '' === trim($sourceValue)) {
                continue;
            }

            try {
                $translatedValue = AutoTranslateService::translateText($sourceValue, $targetClangId, $sourceClangId);

                $update = rex_sql::factory()
                    ->setTable($prefix . 'article')
                    ->setWhere($isCategory
                        ? ['id' => $id, 'clang_id' => $targetClangId, 'startarticle' => 1]
                        : ['id' => $id, 'clang_id' => $targetClangId]);
                if ('name' === $field) {
                    $update->setValue('name', $translatedValue);
                    if ($isCategory) {
                        $update->setValue('catname', $translatedValue);
                    }
                } else {
                    $update->setValue($field, $translatedValue);
                }
                $update->update();

                rex_article_cache::generateMeta($id, $targetClangId);
                ++$translated;
            } catch (Exception $e) {
                ++$errors;
                $log[] = 'Fehler ' . ($isCategory ? 'Kategorie' : 'Artikel') . ' ' . $id . ' (' . $field . '): ' . $e->getMessage();
            }
        }

        rex_response::sendJson([
            'success'    => true,
            'translated' => $translated,
            'errors'     => $errors,
            'log'        => $log,
        ]);
    }

    /**
     * Builds work items for every selected field and target language.
     *
     * @param array<int,array<string,mixed>> $items    collected work items (by reference)
     * @param array<string,string>           $sourceValues field key => source value
     * @param array<int,int>                 $targetClangIds
     */
    private static function collectItems(array &$items, int &$skipped, int $id, bool $isCategory, array $sourceValues, array $targetClangIds, bool $onlyUntranslated): void
    {
        foreach ($sourceValues as $field => $sourceValue) {
            // Empty SEO source values have nothing to translate.
            if ('name' !== $field && '' === trim($sourceValue)) {
                continue;
            }
            foreach ($targetClangIds as $targetClangId) {
                if ($onlyUntranslated && !self::isValueUntranslated($id, $isCategory, $field, $sourceValue, $targetClangId)) {
                    ++$skipped;
                    continue;
                }
                $items[] = ['id' => $id, 'is_category' => $isCategory, 'target_clang' => $targetClangId, 'field' => $field];
            }
        }
    }

    /**
     * Maps a logical field to its article table column.
     */
    private static function columnFor(string $field, bool $isCategory): string
    {
        if ('name' === $field) {
            return $isCategory ? 'catname' : 'name';
        }

        // yrewrite_title / yrewrite_description (whitelisted by the callers)
        return $field;
    }

    private static function fetchValue(int $id, bool $isCategory, int $sourceClangId, string $field): ?string
    {
        $column = self::columnFor($field, $isCategory);
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT `' . $column . '` AS v FROM ' . rex::getTablePrefix() . 'article'
            . ' WHERE id = :id AND clang_id = :clang' . ($isCategory ? ' AND startarticle = 1' : ' AND startarticle = 0'),
            ['id' => $id, 'clang' => $sourceClangId]
        );

        if (0 === $sql->getRows()) {
            return null;
        }

        return (string) $sql->getValue('v');
    }

    /**
     * Check if the value in the target clang is still identical to the source value
     * (= REDAXO default behaviour: copies source verbatim to all clangs on creation)
     */
    private static function isValueUntranslated(int $id, bool $isCategory, string $field, string $sourceValue, int $targetClangId): bool
    {
        $column = self::columnFor($field, $isCategory);
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT `' . $column . '` AS v FROM ' . rex::getTablePrefix() . 'article'
            . ' WHERE id = :id AND clang_id = :clang' . ($isCategory ? ' AND startarticle = 1' : ''),
            ['id' => $id, 'clang' => $targetClangId]
        );

        if ($sql->getRows() === 0) {
            return true;
        }

        return (string) $sql->getValue('v') === $sourceValue;
    }
}
