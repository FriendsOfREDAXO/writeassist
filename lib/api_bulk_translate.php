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

        if (in_array($type, ['articles', 'both'], true)) {
            $sql = rex_sql::factory();
            $sql->setQuery(
                'SELECT id, name FROM ' . $prefix . 'article WHERE clang_id = :clang AND startarticle = 0',
                ['clang' => $sourceClangId]
            );

            foreach ($sql as $row) {
                $id         = (int) $row->getValue('id');
                $sourceName = (string) $row->getValue('name');

                foreach ($targetClangIds as $targetClangId) {
                    if ($onlyUntranslated && !self::isUntranslated($id, false, $sourceName, $targetClangId)) {
                        continue;
                    }
                    $items[] = ['id' => $id, 'is_category' => false, 'target_clang' => $targetClangId];
                }
            }
        }

        if (in_array($type, ['categories', 'both'], true)) {
            $sql = rex_sql::factory();
            $sql->setQuery(
                'SELECT id, catname FROM ' . $prefix . 'article WHERE clang_id = :clang AND startarticle = 1',
                ['clang' => $sourceClangId]
            );

            foreach ($sql as $row) {
                $id         = (int) $row->getValue('id');
                $sourceName = (string) $row->getValue('catname');

                foreach ($targetClangIds as $targetClangId) {
                    if ($onlyUntranslated && !self::isUntranslated($id, true, $sourceName, $targetClangId)) {
                        continue;
                    }
                    $items[] = ['id' => $id, 'is_category' => true, 'target_clang' => $targetClangId];
                }
            }
        }

        rex_response::sendJson([
            'success' => true,
            'items'   => $items,
            'total'   => count($items),
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

            if (0 === $id || 0 === $targetClangId) {
                continue;
            }

            $sourceName = self::fetchName($id, $isCategory, $sourceClangId);
            if (null === $sourceName) {
                continue;
            }

            try {
                $translatedName = AutoTranslateService::translateText($sourceName, $targetClangId, $sourceClangId);

                $update = rex_sql::factory()
                    ->setTable($prefix . 'article')
                    ->setWhere($isCategory
                        ? ['id' => $id, 'clang_id' => $targetClangId, 'startarticle' => 1]
                        : ['id' => $id, 'clang_id' => $targetClangId])
                    ->setValue('name', $translatedName);
                if ($isCategory) {
                    $update->setValue('catname', $translatedName);
                }
                $update->update();

                rex_article_cache::generateMeta($id, $targetClangId);
                ++$translated;
            } catch (Exception $e) {
                ++$errors;
                $log[] = 'Fehler ' . ($isCategory ? 'Kategorie' : 'Artikel') . ' ' . $id . ': ' . $e->getMessage();
            }
        }

        rex_response::sendJson([
            'success'    => true,
            'translated' => $translated,
            'errors'     => $errors,
            'log'        => $log,
        ]);
    }

    private static function fetchName(int $id, bool $isCategory, int $sourceClangId): ?string
    {
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT ' . ($isCategory ? 'catname' : 'name') . ' as n FROM ' . rex::getTablePrefix() . 'article'
            . ' WHERE id = :id AND clang_id = :clang' . ($isCategory ? ' AND startarticle = 1' : ' AND startarticle = 0'),
            ['id' => $id, 'clang' => $sourceClangId]
        );

        if (0 === $sql->getRows()) {
            return null;
        }

        return (string) $sql->getValue('n');
    }

    /**
     * Check if the name in target clang is still identical to the source name
     * (= REDAXO default behaviour: copies source name verbatim to all clangs on creation)
     */
    private static function isUntranslated(int $id, bool $isCategory, string $sourceName, int $targetClangId): bool
    {
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT ' . ($isCategory ? 'catname' : 'name') . ' as n FROM ' . rex::getTablePrefix() . 'article'
            . ' WHERE id = :id AND clang_id = :clang' . ($isCategory ? ' AND startarticle = 1' : ''),
            ['id' => $id, 'clang' => $targetClangId]
        );

        if ($sql->getRows() === 0) {
            return true;
        }

        return (string) $sql->getValue('n') === $sourceName;
    }
}
