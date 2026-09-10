<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\WriteAssist;

use Exception;
use rex;
use rex_addon;
use rex_addon_interface;
use rex_article;
use rex_article_cache;
use rex_category;
use rex_clang;
use rex_sql;
use FriendsOfREDAXO\WriteAssist\WriteAssistAiFactory;

/**
 * AutoTranslateService
 *
 * Automatically translates article and category names into all active languages
 * when a new article or category is created in REDAXO, using the translation
 * provider configured in WriteAssist settings (DeepL or Text-KI).
 *
 * Activated via WriteAssist settings: "enable_auto_translate"
 * Requires a configured translation provider (DeepL API key, or a configured AI provider).
 */
class AutoTranslateService
{
    /**
     * Whether the auto-translate on creation feature is active and usable.
     */
    public static function isEnabled(): bool
    {
        $addon = rex_addon::get('writeassist');
        if (!(bool) $addon->getConfig('enable_auto_translate', false)) {
            return false;
        }
        return self::hasConfiguredProvider($addon);
    }

    /**
     * Whether the auto-translate on rename/update feature is active and usable.
     */
    public static function isRenameEnabled(): bool
    {
        $addon = rex_addon::get('writeassist');
        if (!(bool) $addon->getConfig('translate_on_rename', false)) {
            return false;
        }
        return self::hasConfiguredProvider($addon);
    }

    /**
     * Ob der in den Einstellungen gewählte Übersetzungs-Dienst (DeepL oder Text-KI)
     * tatsächlich konfiguriert ist. Muss dieselbe Provider-Wahl wie translateText()
     * widerspiegeln, sonst bleibt Auto-Übersetzen z.B. bei "Text-KI" ohne DeepL-Key
     * stillschweigend inaktiv, obwohl translateText() den KI-Provider nutzen würde.
     */
    private static function hasConfiguredProvider(rex_addon_interface $addon): bool
    {
        if ($addon->getConfig('translation_provider', 'deepl') === 'ai') {
            return WriteAssistAiFactory::factory()->isConfigured();
        }
        return '' !== (string) $addon->getConfig('api_key', '');
    }

    /**
     * Optionaler Hintergrund-Kontext aus den Einstellungen (z.B. Art der Website,
     * feste Begriffe/Abkürzungen, die unübersetzt bleiben sollen), der jedem
     * KI-Übersetzungs-Prompt mitgegeben wird - gemeinsam genutzt von translateText()
     * und api_translate.php (Einzelübersetzung/TinyMCE), damit der Kontext einheitlich
     * überall greift, wo translation_provider=ai übersetzt.
     */
    public static function getTranslationContext(): string
    {
        return trim((string) rex_addon::get('writeassist')->getConfig('translation_context', ''));
    }

    /**
     * Übersetzt einen einzelnen Text/Titel in eine Zielsprache, unter Verwendung
     * des in den WriteAssist-Einstellungen gewählten Providers (DeepL oder KI).
     *
     * Gemeinsame Provider-Wahl-Logik für translateName() (Auto-Übersetzung bei
     * Artikel/Kategorie-Anlage) und die Massenübersetzung (api_bulk_translate.php),
     * damit beide denselben KI-Fallback nutzen, statt die Provider-Auswahl doppelt
     * zu pflegen.
     *
     * @param string $text        Zu übersetzender Text (Titel, kein HTML)
     * @param int    $targetClang Ziel-Sprache
     * @param int    $sourceClang Quell-Sprache
     * @throws Exception Wenn der gewählte Provider fehlschlägt (z.B. DeepL-API-Fehler)
     */
    public static function translateText(string $text, int $targetClang, int $sourceClang): string
    {
        $providerType = rex_addon::get('writeassist')->getConfig('translation_provider', 'deepl');

        if ($providerType === 'ai') {
            $ai = WriteAssistAiFactory::factory();
            if ($ai->isConfigured()) {
                $targetCode = self::getDeeplCode($targetClang);
                $context = self::getTranslationContext();

                // "/no_think" schaltet bei Qwen3 und einigen anderen Reasoning-Modellen
                // den Thinking-Modus ab (sonst können Reasoning-Marker wie "<think>...</think>"
                // oder ein angehängtes "think" in der Antwort landen, siehe stripReasoningArtifacts()).
                // Bei Modellen, die die Direktive nicht kennen, ist sie wirkungslos, aber unschädlich.
                $prompt = "/no_think

"
                        . "Übersetze den folgenden Text/Titel in die Sprache/den Sprachcode: " . $targetCode . ".

";

                if ('' !== $context) {
                    // Optionaler Hintergrund-Kontext aus den Einstellungen (z.B. Art der Website,
                    // feste Begriffe/Abkürzungen, die unübersetzt bleiben sollen) - hilft gerade
                    // kleineren Modellen, Eigennamen/Akronyme nicht frei zu "übersetzen"
                    // (beobachtet z.B. bei Vereinskürzeln wie "WDFV").
                    $prompt .= "Kontext zur Website: " . $context . "

";
                }

                $prompt .= "Antworte AUSSCHLIESSLICH mit dem übersetzten Text. Keine Einleitung, keine Anführungszeichen.

"
                        . "Zu übersetzender Text:
" . $text;

                $aiResponse = $ai->generate($prompt);
                return self::stripReasoningArtifacts($aiResponse['text']);
            }
        }

        $sourceCode = self::getDeeplSourceCode($sourceClang);
        $targetCode = self::getDeeplCode($targetClang);
        $deepl = new DeeplApi();
        $result = $deepl->translate($text, $targetCode, $sourceCode);
        return $result['text'];
    }

    /**
     * Entfernt Reasoning-Artefakte, die manche über OpenWebUI/ai_platform
     * angebundene "Thinking"-Modelle (z.B. Qwen3) trotz einfachem Prompt in
     * die Antwort durchsickern lassen: <think>...</think>-Blöcke sowie die
     * Steuer-Tokens /think und /no_think am Rand der Antwort. Ohne dieses
     * Aufräumen würden solche Marker unbemerkt als Teil des übersetzten
     * Artikel-/Kategorienamens gespeichert.
     */
    private static function stripReasoningArtifacts(string $text): string
    {
        $text = preg_replace('#<think>.*?</think>#is', '', $text) ?? $text;
        $text = preg_replace('#^\s*/no_think\s*|^\s*/think\s*|\s*/no_think\s*$|\s*/think\s*$#i', '', $text) ?? $text;

        return trim($text);
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

        foreach (rex_clang::getAll() as $clang) {
            if ($clang->getId() === $sourceClang) {
                continue;
            }

            try {
                $translatedName = self::translateText($sourceName, $clang->getId(), $sourceClang);

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
                // Silently skip on provider error – original name stays
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
     *
     * @alias getTargetCode
     */
    public static function getDeeplCode(int $clangId): string
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

    /** @alias getDeeplCode */
    public static function getTargetCode(int $clangId): string
    {
        return self::getDeeplCode($clangId);
    }

    /**
     * Map REDAXO clang code to DeepL SOURCE language code.
     * Source langs only support base codes – EN-US/EN-GB are NOT valid source langs.
     *
     * @alias getSourceCode
     */
    public static function getDeeplSourceCode(int $clangId): string
    {
        $target = self::getDeeplCode($clangId);
        // DeepL source lang never has region suffix
        return explode('-', $target)[0];
    }

    /** @alias getDeeplSourceCode */
    public static function getSourceCode(int $clangId): string
    {
        return self::getDeeplSourceCode($clangId);
    }
}
