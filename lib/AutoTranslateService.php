<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\WriteAssist;

use Exception;
use rex;
use rex_addon;
use rex_article;
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
     * Translate article name into all other active clangs.
     *
     * @param int $articleId  REDAXO article ID
     * @param int $sourceClang Clang ID in which the article was originally created
     */
    public static function translateArticleName(int $articleId, int $sourceClang): void
    {
        $article = rex_article::get($articleId, $sourceClang);
        if (!$article) {
            return;
        }

        $sourceName = $article->getName();
        if ('' === $sourceName) {
            return;
        }

        $sourceCode = self::getDeeplCode($sourceClang);
        $deepl = new DeeplApi();

        foreach (rex_clang::getAll() as $clang) {
            if ($clang->getId() === $sourceClang) {
                continue;
            }

            try {
                $result = $deepl->translate($sourceName, self::getDeeplCode($clang->getId()), $sourceCode);
                $translatedName = $result['text'];

                rex_sql::factory()
                    ->setTable(rex::getTablePrefix() . 'article')
                    ->setWhere(['id' => $articleId, 'clang_id' => $clang->getId()])
                    ->setValue('name', $translatedName)
                    ->update();
            } catch (Exception) {
                // Silently skip on DeepL error – original name stays
            }
        }
    }

    /**
     * Translate category name into all other active clangs.
     *
     * @param int $categoryId  REDAXO category ID
     * @param int $sourceClang Clang ID in which the category was originally created
     */
    public static function translateCategoryName(int $categoryId, int $sourceClang): void
    {
        $category = rex_category::get($categoryId, $sourceClang);
        if (!$category) {
            return;
        }

        $sourceName = $category->getName();
        if ('' === $sourceName) {
            return;
        }

        $sourceCode = self::getDeeplCode($sourceClang);
        $deepl = new DeeplApi();

        foreach (rex_clang::getAll() as $clang) {
            if ($clang->getId() === $sourceClang) {
                continue;
            }

            try {
                $result = $deepl->translate($sourceName, self::getDeeplCode($clang->getId()), $sourceCode);
                $translatedName = $result['text'];

                rex_sql::factory()
                    ->setTable(rex::getTablePrefix() . 'article')
                    ->setWhere(['id' => $categoryId, 'clang_id' => $clang->getId(), 'startarticle' => 1])
                    ->setValue('name', $translatedName)
                    ->update();

                rex_sql::factory()
                    ->setTable(rex::getTablePrefix() . 'category')
                    ->setWhere(['id' => $categoryId, 'clang_id' => $clang->getId()])
                    ->setValue('name', $translatedName)
                    ->update();
            } catch (Exception) {
                // Silently skip on DeepL error – original name stays
            }
        }
    }

    /**
     * Map REDAXO clang code to DeepL language code.
     * e.g. "de_de" → "DE", "en_gb" → "EN-GB"
     */
    private static function getDeeplCode(int $clangId): string
    {
        $clang = rex_clang::get($clangId);
        if (!$clang) {
            return 'DE';
        }

        $code = strtoupper($clang->getCode());

        // Map common REDAXO codes to DeepL codes
        $map = [
            'DE' => 'DE',
            'DE_DE' => 'DE',
            'EN' => 'EN-US',
            'EN_GB' => 'EN-GB',
            'EN_US' => 'EN-US',
            'FR' => 'FR',
            'FR_FR' => 'FR',
            'ES' => 'ES',
            'ES_ES' => 'ES',
            'IT' => 'IT',
            'IT_IT' => 'IT',
            'NL' => 'NL',
            'NL_NL' => 'NL',
            'PL' => 'PL',
            'PT' => 'PT-PT',
            'PT_PT' => 'PT-PT',
            'PT_BR' => 'PT-BR',
            'RU' => 'RU',
            'JA' => 'JA',
            'ZH' => 'ZH',
        ];

        return $map[$code] ?? explode('_', $code)[0];
    }
}
