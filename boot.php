<?php

declare(strict_types=1);

/**
 * WriteAssist AddOn for REDAXO
 * 
 * Provides translation (DeepL) and text improvement (LanguageTool) services
 * Can be used standalone or integrated into Info Center
 */

$addon = rex_addon::get('writeassist');

// Auto-Translate: Artikelnamen und Kategorienamen bei Neuanlage übersetzen
// ART_ADDED/CAT_ADDED feuern einmal pro Sprache – Deduplication über static Set
if (\FriendsOfREDAXO\WriteAssist\AutoTranslateService::isEnabled()) {
    rex_extension::register('ART_ADDED', static function (rex_extension_point $ep): void {
        static $done = [];
        $id = (int) $ep->getParam('id');
        if ($id <= 0 || isset($done[$id])) {
            return;
        }
        $done[$id] = true;
        $name = (string) $ep->getParam('name');
        $sourceClang = rex_clang::getCurrentId();
        \FriendsOfREDAXO\WriteAssist\AutoTranslateService::translateName($id, 'article', $name, $sourceClang);
    });

    rex_extension::register('CAT_ADDED', static function (rex_extension_point $ep): void {
        static $done = [];
        $id = (int) $ep->getParam('id');
        if ($id <= 0 || isset($done[$id])) {
            return;
        }
        $done[$id] = true;
        $name = (string) $ep->getParam('name');
        $sourceClang = rex_clang::getCurrentId();
        \FriendsOfREDAXO\WriteAssist\AutoTranslateService::translateName($id, 'category', $name, $sourceClang);
    });
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
