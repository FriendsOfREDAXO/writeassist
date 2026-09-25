<?php

declare(strict_types=1);

/**
 * WriteAssist AddOn for REDAXO
 * 
 * Provides translation (DeepL) and text improvement (LanguageTool) services
 * Can be used standalone or integrated into Info Center
 */

$addon = rex_addon::get('writeassist');

// API-Klassen explizit registrieren (umgeht Autoload-Cache-Probleme)
rex_api_function::register('writeassist_bulk_translate', 'rex_api_writeassist_bulk_translate');

// Auto-Translate: Artikelnamen und Kategorienamen bei Neuanlage übersetzen
// ART_ADDED feuert innerhalb der foreach-Clang-Schleife von addArticle().
// Wir sammeln nur beim ersten Aufruf (done-Guard) und führen die Übersetzung
// NACH dem Ende aller Clang-Inserts via register_shutdown_function aus.
if (\FriendsOfREDAXO\WriteAssist\AutoTranslateService::isEnabled()) {
    rex_extension::register('ART_ADDED', static function (rex_extension_point $ep): void {
        static $queued = [];
        $id = (int) $ep->getParam('id');
        if ($id <= 0 || isset($queued[$id])) {
            return;
        }
        $queued[$id] = true;
        $name = (string) $ep->getParam('name');
        $sourceClang = rex_clang::getCurrentId();
        register_shutdown_function(static function () use ($id, $name, $sourceClang): void {
            \FriendsOfREDAXO\WriteAssist\AutoTranslateService::translateName($id, 'article', $name, $sourceClang);
        });
    });

    rex_extension::register('CAT_ADDED', static function (rex_extension_point $ep): void {
        static $queued = [];
        $id = (int) $ep->getParam('id');
        if ($id <= 0 || isset($queued[$id])) {
            return;
        }
        $queued[$id] = true;
        $name = (string) $ep->getParam('name');
        $sourceClang = rex_clang::getCurrentId();
        register_shutdown_function(static function () use ($id, $name, $sourceClang): void {
            \FriendsOfREDAXO\WriteAssist\AutoTranslateService::translateName($id, 'category', $name, $sourceClang);
        });
    });
}

// Auto-Translate bei Umbenennung: Übersetzt Artikel-/Kategorienamen bei Bearbeitung
if (\FriendsOfREDAXO\WriteAssist\AutoTranslateService::isRenameEnabled()) {
    rex_extension::register('ART_UPDATED', static function (rex_extension_point $ep): void {
        static $queued = [];
        $id = (int) $ep->getParam('id');
        if ($id <= 0 || isset($queued[$id])) {
            return;
        }
        $queued[$id] = true;
        $name = (string) $ep->getParam('name');
        $clangId = $ep->getParam('clang_id');
        $sourceClang = null !== $clangId ? (int) $clangId : rex_clang::getCurrentId();
        if ('' === $name || $sourceClang <= 0) {
            return;
        }
        register_shutdown_function(static function () use ($id, $name, $sourceClang): void {
            \FriendsOfREDAXO\WriteAssist\AutoTranslateService::translateName($id, 'article', $name, $sourceClang);
        });
    });

    rex_extension::register('CAT_UPDATED', static function (rex_extension_point $ep): void {
        static $queued = [];
        $id = (int) $ep->getParam('id');
        if ($id <= 0 || isset($queued[$id])) {
            return;
        }
        $queued[$id] = true;
        $name = (string) $ep->getParam('name');
        $clangId = $ep->getParam('clang_id');
        $sourceClang = null !== $clangId ? (int) $clangId : rex_clang::getCurrentId();
        if ('' === $name || $sourceClang <= 0) {
            return;
        }
        register_shutdown_function(static function () use ($id, $name, $sourceClang): void {
            \FriendsOfREDAXO\WriteAssist\AutoTranslateService::translateName($id, 'category', $name, $sourceClang);
        });
    });
}

// Auto-SEO: das yrewrite-SEO-Bild sprachübergreifend übernehmen und/oder SEO-
// Titel/-Beschreibung in die anderen Sprachen übersetzen. Der yrewrite-SEO-Block
// in der Content-Sidebar speichert per YForm (feuert YFORM_SAVED/REX_YFORM_SAVED),
// das metainfo-Metadatenformular feuert ART_META_UPDATED/CAT_META_UPDATED.
if (\FriendsOfREDAXO\WriteAssist\AutoTranslateService::isSeoImageEnabled()
    || \FriendsOfREDAXO\WriteAssist\AutoTranslateService::isSeoMetaEnabled()) {
    $writeAssistSeoQueue = static function (int $id, int $sourceClang): void {
        static $queued = [];
        if ($id <= 0 || $sourceClang <= 0 || isset($queued[$id . '_' . $sourceClang])) {
            return;
        }
        $queued[$id . '_' . $sourceClang] = true;
        register_shutdown_function(static function () use ($id, $sourceClang): void {
            if (\FriendsOfREDAXO\WriteAssist\AutoTranslateService::isSeoImageEnabled()) {
                \FriendsOfREDAXO\WriteAssist\AutoTranslateService::propagateSeoImage($id, $sourceClang);
            }
            if (\FriendsOfREDAXO\WriteAssist\AutoTranslateService::isSeoMetaEnabled()) {
                \FriendsOfREDAXO\WriteAssist\AutoTranslateService::translateSeoMeta($id, $sourceClang);
            }
        });
    };

    // metainfo metadata form (id + clang in the params)
    $writeAssistMetaHandler = static function (rex_extension_point $ep) use ($writeAssistSeoQueue): void {
        $params = $ep->getParams();
        $writeAssistSeoQueue((int) ($params['id'] ?? 0), (int) ($params['clang'] ?? rex_clang::getCurrentId()));
    };
    rex_extension::register('ART_META_UPDATED', $writeAssistMetaHandler);
    rex_extension::register('CAT_META_UPDATED', $writeAssistMetaHandler);

    // yrewrite SEO sidebar block (saved via a YForm "db" action on rex_article).
    // Restricted to that form via the yrewrite_func=seo hidden field.
    $writeAssistYformHandler = static function (rex_extension_point $ep) use ($writeAssistSeoQueue): void {
        $params = $ep->getParams();
        if (($params['table'] ?? '') !== rex::getTable('article') || 'seo' !== rex_post('yrewrite_func', 'string')) {
            return;
        }
        $id = (int) ($params['id'] ?? 0);
        $clang = rex_request('clang', 'int', rex_clang::getCurrentId());
        $writeAssistSeoQueue($id, $clang);
    };
    rex_extension::register('YFORM_SAVED', $writeAssistYformHandler);
    rex_extension::register('REX_YFORM_SAVED', $writeAssistYformHandler);
}

if (rex::isBackend() && rex::getUser()) {
    // Register as Info Center Widget if info_center addon is available and enabled
    if ($addon->getConfig('enable_infocenter_widget', true) && rex_addon::get('info_center')->isAvailable() && class_exists(\KLXM\InfoCenter\InfoCenter::class)) {
        $infoCenter = \KLXM\InfoCenter\InfoCenter::getInstance();
        $widget = new \FriendsOfREDAXO\WriteAssist\WriteAssistWidget();
        $widget->setPriority(1);  // After system widgets
        $infoCenter->registerWidget($widget);
    }

    // Add assets to backend
    rex_view::addCssFile($addon->getAssetsUrl('css/writeassist.css'));
    rex_view::addJsFile($addon->getAssetsUrl('js/writeassist.js'));
    rex_view::addJsFile($addon->getAssetsUrl('js/writeassist-bulk-translate.js'));
    rex_view::addJsFile($addon->getAssetsUrl('js/writeassist-settings.js'));

    // KI-Text-/Übersetzungs-Widget für einfache Textareas/Inputs ohne WYSIWYG-Editor
    // (class="watext" bzw. class="watranslate") - addonweit im Backend verfügbar,
    // damit auch YForm-/MForm-Felder oder Custom-Module davon profitieren können.
    rex_view::addCssFile($addon->getAssetsUrl('css/writeassist-fields.css'));
    rex_view::addJsFile($addon->getAssetsUrl('js/writeassist-fields.js'));
    if ($addon->getConfig('enable_markitup_plugin', true) && rex_addon::get('markitup')->isAvailable()) {
        // Register MarkItUp integration JS
        rex_view::addJsFile($addon->getAssetsUrl('js/markitup-writeassist-plugin.js'));
    }
}

// Register TinyMCE Plugin if TinyMCE addon is available and enabled
if (rex::isBackend() && rex::getUser() && $addon->getConfig('enable_tinymce_plugin', true) && rex_addon::get('tinymce')->isAvailable()) {
    // Register Plugin Directory
    // Ensure we can register multiple plugins
    
    if (class_exists(\FriendsOfRedaxo\TinyMce\PluginRegistry::class)) {
        // 1. DeepL Translate Plugin
        \FriendsOfRedaxo\TinyMce\PluginRegistry::addPlugin(
            'writeassist_translate',
            rex_url::base('assets/addons/writeassist/js/tinymce-deepl-plugin.js'),
            'writeassist_translate'
        );

        // 2. AI Generator Plugin (Gemini/OpenWebUI)
        \FriendsOfRedaxo\TinyMce\PluginRegistry::addPlugin(
            'writeassist_generate',
            rex_url::base('assets/addons/writeassist/js/tinymce-generate-plugin.js'),
            'writeassist_generate'
        );
    }
}

    // Register TipTap plugin if TipTap addon is available
    if (rex::isBackend() && rex::getUser() && rex_addon::get('tiptap')->isAvailable()) {
        // Register a minimal integration plugin that enables WriteAssist toolbar features
        if (class_exists(\FriendsOfRedaxo\TipTap\PluginRegistry::class)) {
            \FriendsOfRedaxo\TipTap\PluginRegistry::addPlugin(
                'writeassist_tiptap',
                rex_url::base('assets/addons/writeassist/js/tiptap-writeassist-plugin.js'),
                'writeassist_tiptap'
            );
        }
    }
