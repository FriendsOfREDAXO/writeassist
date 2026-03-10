<?php

declare(strict_types=1);

use FriendsOfREDAXO\WriteAssist\AutoTranslateService;
use FriendsOfREDAXO\WriteAssist\DeeplApi;

/**
 * WriteAssist – Bulk Translate API
 *
 * Translates existing article and/or category names from a source language
 * into all other active clangs via DeepL.
 *
 * POST params:
 *  source_clang      int   ID of the source clang
 *  type              string  'articles' | 'categories' | 'both'
 *  only_untranslated int   1 = only translate where name is still identical to source
 */
class rex_api_writeassist_bulk_translate extends rex_api_function
{
    /** @var bool accessible for logged-in admins only */
    protected $published = true;

    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        if (!rex::getUser() || !rex::getUser()->isAdmin()) {
            rex_response::sendJson(['success' => false, 'error' => 'Keine Berechtigung']);
            exit;
        }

        $addon = rex_addon::get('writeassist');
        if (trim((string) $addon->getConfig('api_key', '')) === '') {
            rex_response::sendJson(['success' => false, 'error' => 'Kein DeepL-API-Key hinterlegt']);
            exit;
        }

        $sourceClangId    = (int) rex_post('source_clang', 'int', 0);
        $type             = rex_post('type', 'string', 'both');
        $onlyUntranslated = (bool) rex_post('only_untranslated', 'int', 0);

        $sourceClang = rex_clang::get($sourceClangId);
        if (!$sourceClang) {
            rex_response::sendJson(['success' => false, 'error' => 'Ungültige Quellsprache']);
            exit;
        }

        $targetClangs = [];
        foreach (rex_clang::getAll() as $clang) {
            if ($clang->getId() !== $sourceClangId) {
                $targetClangs[] = $clang;
            }
        }

        if (empty($targetClangs)) {
            rex_response::sendJson(['success' => false, 'error' => 'Keine weiteren Sprachen vorhanden']);
            exit;
        }

        $deepl     = new DeeplApi();
        $translated = 0;
        $skipped    = 0;
        $errors     = 0;
        $log        = [];

        $prefix = rex::getTablePrefix();

        // --- Artikel ---
        if (in_array($type, ['articles', 'both'], true)) {
            $sql = rex_sql::factory();
            $sql->setQuery(
                'SELECT id, name FROM ' . $prefix . 'article WHERE clang_id = :clang AND startarticle = 0',
                ['clang' => $sourceClangId]
            );

            foreach ($sql as $row) {
                $id         = (int) $row->getValue('id');
                $sourceName = (string) $row->getValue('name');

                foreach ($targetClangs as $targetClang) {
                    if ($onlyUntranslated && !self::isUntranslated($id, false, $sourceName, $targetClang->getId())) {
                        ++$skipped;
                        continue;
                    }

                    try {
                        $sourceCode = AutoTranslateService::getSourceCode($sourceClangId);
                        $targetCode = AutoTranslateService::getTargetCode($targetClang->getId());
                        $result     = $deepl->translate($sourceName, $targetCode, $sourceCode);
                        $translated_name = $result['text'];

                        rex_sql::factory()
                            ->setTable($prefix . 'article')
                            ->setWhere(['id' => $id, 'clang_id' => $targetClang->getId()])
                            ->setValue('name', $translated_name)
                            ->update();

                        rex_article_cache::generateMeta($id, $targetClang->getId());
                        ++$translated;
                    } catch (Exception $e) {
                        ++$errors;
                        $log[] = 'Fehler Artikel ' . $id . ': ' . $e->getMessage();
                    }
                }
            }
        }

        // --- Kategorien ---
        if (in_array($type, ['categories', 'both'], true)) {
            $sql = rex_sql::factory();
            $sql->setQuery(
                'SELECT id, catname FROM ' . $prefix . 'article WHERE clang_id = :clang AND startarticle = 1',
                ['clang' => $sourceClangId]
            );

            foreach ($sql as $row) {
                $id         = (int) $row->getValue('id');
                $sourceName = (string) $row->getValue('catname');

                foreach ($targetClangs as $targetClang) {
                    if ($onlyUntranslated && !self::isUntranslated($id, true, $sourceName, $targetClang->getId())) {
                        ++$skipped;
                        continue;
                    }

                    try {
                        $sourceCode      = AutoTranslateService::getSourceCode($sourceClangId);
                        $targetCode      = AutoTranslateService::getTargetCode($targetClang->getId());
                        $result          = $deepl->translate($sourceName, $targetCode, $sourceCode);
                        $translated_name = $result['text'];

                        rex_sql::factory()
                            ->setTable($prefix . 'article')
                            ->setWhere(['id' => $id, 'clang_id' => $targetClang->getId(), 'startarticle' => 1])
                            ->setValue('catname', $translated_name)
                            ->setValue('name', $translated_name)
                            ->update();

                        rex_article_cache::generateMeta($id, $targetClang->getId());
                        ++$translated;
                    } catch (Exception $e) {
                        ++$errors;
                        $log[] = 'Fehler Kategorie ' . $id . ': ' . $e->getMessage();
                    }
                }
            }
        }

        rex_response::sendJson([
            'success'    => true,
            'translated' => $translated,
            'skipped'    => $skipped,
            'errors'     => $errors,
            'log'        => $log,
        ]);
        exit;
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
