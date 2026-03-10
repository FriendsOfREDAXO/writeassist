<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\WriteAssist;

use Exception;
use rex;
use rex_addon;
use rex_article;
use rex_article_cache;
use rex_category;
use rex_clang;
use rex_sql;

/**
 * AutoTranslateService
 *
 * Automatically translates article and category names into all active languages
 * via DeepL when a new article or category is created in REDAXO.
 *
 * Activated via WriteAssist settings: "enable_auto_translate"
 * Requires a valid DeepL API key in WriteAssist settings.
 */
class AutoTranslateService
{
    /**
     * Whether the feature is active and usable.
     */
    public static function isEnabled(): bool
    {
        $addon = rex_addon::get('writeassist');
        if (!(bool) $addon->getConfig('enable_auto_translate', false)) {
            return false;
        }
        if ('' === (string) $addon->getConfig('api_key', '')) {
            return false;
        }
        return true;
    }

    /**
     * Translate a name (article or category) into all other active clangs.
     *
     * @param int    $id         Article or category ID
     * @param string $type       'article' or 'category'
     * @param string $sourceName The name as entered by the user
     * @param int    $sourceClang Clang ID of the user's current session language
     */
    public static function translateName(int $id, string $type, string $sourceName, int $sourceClang): void
    {
        if ('' === $sourceName) {
            return;
        }

        $sourceCode = self::getDeeplSourceCode($sourceClang);
        $deepl = new DeeplApi();

        foreach (rex_clang::getAll() as $clang) {
            if ($clang->getId() === $sourceClang) {
                continue;
            }

            try {
                $targetCode = self::getDeeplCode($clang->getId());
                $result = $deepl->translate($sourceName, $targetCode, $sourceCode);
                $translatedName = $result['text'];

                if ('category' === $type) {
                    // Kategorien sind startarticle=1-Zeilen in rex_article – Feld: catname
                    rex_sql::factory()
                        ->setTable(rex::getTablePrefix() . 'article')
                        ->setWhere(['id' => $id, 'clang_id' => $clang->getId(), 'startarticle' => 1])
                        ->setValue('catname', $translatedName)
                        ->setValue('name', $translatedName)
                        ->update();
                } else {
                    rex_sql::factory()
                        ->setTable(rex::getTablePrefix() . 'article')
                        ->setWhere(['id' => $id, 'clang_id' => $clang->getId()])
                        ->setValue('name', $translatedName)
                        ->update();
                }

                rex_article_cache::generateMeta($id, $clang->getId());
            } catch (Exception $e) {
                // Silently skip on DeepL error – original name stays
            }
        }
    }

    /**
     * @deprecated Use translateName() instead
     */
    public static function translateArticleName(int $articleId, int $sourceClang): void
    {
        $article = rex_article::get($articleId, $sourceClang);
        if ($article) {
            self::translateName($articleId, 'article', $article->getName(), $sourceClang);
        }
    }

    /**
     * @deprecated Use translateName() instead
     */
    public static function translateCategoryName(int $categoryId, int $sourceClang): void
    {
        $category = rex_category::get($categoryId, $sourceClang);
        if ($category) {
            self::translateName($categoryId, 'category', $category->getName(), $sourceClang);
        }
    }

    /**
     * Map REDAXO clang code to DeepL target language code.
     * e.g. "en_gb" → "EN-GB", "de_de" → "DE"
     */
    private static function getDeeplCode(int $clangId): string
    {
        $clang = rex_clang::get($clangId);
        if (!$clang) {
            return 'DE';
        }

        $code = strtoupper($clang->getCode());

        $map = [
            'DE' => 'DE', 'DE_DE' => 'DE', 'DE_AT' => 'DE', 'DE_CH' => 'DE',
            'EN' => 'EN-US', 'EN_GB' => 'EN-GB', 'EN_US' => 'EN-US',
            'FR' => 'FR', 'FR_FR' => 'FR',
            'ES' => 'ES', 'ES_ES' => 'ES',
            'IT' => 'IT', 'IT_IT' => 'IT',
            'NL' => 'NL', 'NL_NL' => 'NL',
            'PL' => 'PL', 'PL_PL' => 'PL',
            'PT' => 'PT-PT', 'PT_PT' => 'PT-PT', 'PT_BR' => 'PT-BR',
            'RU' => 'RU', 'JA' => 'JA', 'ZH' => 'ZH',
            'SL' => 'SL', 'CS' => 'CS', 'SK' => 'SK', 'HU' => 'HU',
            'RO' => 'RO', 'BG' => 'BG', 'DA' => 'DA', 'FI' => 'FI',
            'EL' => 'EL', 'ET' => 'ET', 'LT' => 'LT', 'LV' => 'LV',
            'SV' => 'SV', 'TR' => 'TR', 'UK' => 'UK', 'ID' => 'ID',
            'KO' => 'KO', 'NB' => 'NB',
        ];

        return $map[$code] ?? explode('_', $code)[0];
    }

    /**
     * Map REDAXO clang code to DeepL SOURCE language code.
     * Source langs only support base codes – EN-US/EN-GB are NOT valid source langs.
     */
    private static function getDeeplSourceCode(int $clangId): string
    {
        $target = self::getDeeplCode($clangId);
        // DeepL source lang never has region suffix
        return explode('-', $target)[0];
    }
}
