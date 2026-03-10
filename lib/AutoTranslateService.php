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
                    ->setWhere(['id' => $id, 'clang_id' => $clang->getId()])
                    ->setValue('name', $translatedName)
                    ->update();

                if ('category' === $type) {
                    rex_sql::factory()
                        ->setTable(rex::getTablePrefix() . 'category')
                        ->setWhere(['id' => $id, 'clang_id' => $clang->getId()])
                        ->setValue('name', $translatedName)
                        ->update();
                }

                rex_article_cache::generateMeta($id, $clang->getId());
            } catch (Exception) {
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
