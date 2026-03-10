<?php

/**
 * WriteAssist - Settings Page
 */

$package = rex_addon::get('writeassist');

// -------------------------------------------------------------------------
// Aktuelle Konfiguration für Sidebar
// -------------------------------------------------------------------------
$cfgApiKey        = trim((string) $package->getConfig('api_key', ''));
$cfgAutoTranslate = (bool) $package->getConfig('enable_auto_translate', false);
$cfgTinyMce       = (bool) $package->getConfig('enable_tinymce_plugin', true);
$cfgInfoCenter    = (bool) $package->getConfig('enable_infocenter_widget', true);
$cfgAiProvider    = (string) $package->getConfig('ai_provider', 'gemini');
$cfgGeminiKey     = trim((string) $package->getConfig('gemini_api_key', ''));
$cfgOwKey         = trim((string) $package->getConfig('openwebui_api_key', ''));
$cfgOwUrl         = trim((string) $package->getConfig('openwebui_base_url', ''));

$deeplConfigured  = $cfgApiKey !== '';
$aiConfigured     = ('gemini' === $cfgAiProvider) ? ($cfgGeminiKey !== '') : ($cfgOwKey !== '' && $cfgOwUrl !== '');

// -------------------------------------------------------------------------
// Hilfsfunktion: Status-Badge
// -------------------------------------------------------------------------
$statusBadge = static function (bool $ok, string $labelOn, string $labelOff): string {
    if ($ok) {
        return '<span class="label label-success"><i class="rex-icon fa-check"></i> ' . rex_escape($labelOn) . '</span>';
    }
    return '<span class="label label-default"><i class="rex-icon fa-times"></i> ' . rex_escape($labelOff) . '</span>';
};

// -------------------------------------------------------------------------
// Sidebar HTML
// -------------------------------------------------------------------------
$sidebar = '';

// --- Auto-Translate (prominent oben) ---
$atPanelClass = $cfgAutoTranslate ? 'panel-success' : 'panel-warning';
$sidebar .= '<div class="panel ' . $atPanelClass . '">';
$sidebar .= '<div class="panel-heading"><strong><i class="rex-icon fa-language"></i> Auto-Übersetzen</strong></div>';
$sidebar .= '<div class="panel-body">';
if ($cfgAutoTranslate) {
    $sidebar .= '<p><i class="rex-icon fa-check text-success"></i> <strong>Aktiv</strong></p>';
    $sidebar .= '<p class="small">Neue Artikel und Kategorien werden automatisch per DeepL in alle Sprachen übersetzt.</p>';
} else {
    $sidebar .= '<p><i class="rex-icon fa-times text-muted"></i> <strong>Inaktiv</strong></p>';
    $sidebar .= '<p class="small">Aktiviere diese Option, damit neue Artikel und Kategorien automatisch übersetzt werden.</p>';
    if (!$deeplConfigured) {
        $sidebar .= '<p class="small text-warning"><i class="rex-icon fa-exclamation-triangle"></i> DeepL-API-Key fehlt.</p>';
    }
}
$sidebar .= '</div></div>';

// --- Dienste ---
$sidebar .= '<div class="panel panel-default">';
$sidebar .= '<div class="panel-heading"><strong><i class="rex-icon fa-plug"></i> Dienste &amp; API</strong></div>';
$sidebar .= '<ul class="list-group">';

$sidebar .= '<li class="list-group-item"><strong>DeepL</strong> ';
$sidebar .= $statusBadge($deeplConfigured, 'Konfiguriert', 'Kein Key');
$sidebar .= '</li>';

$providerLabel = 'gemini' === $cfgAiProvider ? 'Google Gemini' : 'OpenWebUI';
$sidebar .= '<li class="list-group-item"><strong>KI-Provider</strong><br>';
$sidebar .= '<span class="small">' . rex_escape($providerLabel) . '</span> ';
$sidebar .= $statusBadge($aiConfigured, 'Konfiguriert', 'Kein Key');
$sidebar .= '</li>';

$ltUrl = trim((string) $package->getConfig('languagetool_api_url', ''));
$ltUser = trim((string) $package->getConfig('languagetool_username', ''));
$ltConfigured = $ltUrl !== '' || $ltUser !== '';
$sidebar .= '<li class="list-group-item"><strong>LanguageTool</strong> ';
$sidebar .= $statusBadge($ltConfigured, 'Konfiguriert', 'Standard-API');
$sidebar .= '</li>';

$sidebar .= '</ul></div>';

// --- Integrationen ---
$sidebar .= '<div class="panel panel-default">';
$sidebar .= '<div class="panel-heading"><strong><i class="rex-icon fa-puzzle-piece"></i> Integrationen</strong></div>';
$sidebar .= '<ul class="list-group">';
$sidebar .= '<li class="list-group-item">TinyMCE Plugin ' . $statusBadge($cfgTinyMce, 'Aktiv', 'Inaktiv') . '</li>';
$sidebar .= '<li class="list-group-item">Info Center Widget ' . $statusBadge($cfgInfoCenter, 'Aktiv', 'Inaktiv') . '</li>';
$sidebar .= '</ul></div>';

// -------------------------------------------------------------------------
// Formular
// -------------------------------------------------------------------------
$form = rex_config_form::factory($package->getName());

// === DeepL Settings ===
$form->addRawField('<fieldset><legend>' . $package->i18n('writeassist_deepl_settings') . '</legend>');

$field = $form->addInputField('text', 'api_key', null, ['class' => 'form-control']);
$field->setLabel($package->i18n('writeassist_api_key'));
$field->setNotice($package->i18n('writeassist_api_key_notice'));

$field = $form->addSelectField('use_free_api');
$field->setLabel($package->i18n('writeassist_api_type'));
$select = $field->getSelect();
$select->addOption($package->i18n('writeassist_api_type_free'), '1');
$select->addOption($package->i18n('writeassist_api_type_pro'), '0');
$field->setNotice($package->i18n('writeassist_api_type_notice'));

$form->addRawField('</fieldset>');

// === LanguageTool Settings ===
$form->addRawField('<fieldset><legend>' . $package->i18n('writeassist_languagetool_settings') . '</legend>');

$field = $form->addInputField('text', 'languagetool_api_url', null, ['class' => 'form-control']);
$field->setLabel($package->i18n('writeassist_languagetool_url'));
$field->setNotice($package->i18n('writeassist_languagetool_url_notice'));

$field = $form->addInputField('text', 'languagetool_username', null, ['class' => 'form-control']);
$field->setLabel($package->i18n('writeassist_languagetool_username'));

$field = $form->addInputField('text', 'languagetool_api_key', null, ['class' => 'form-control']);
$field->setLabel($package->i18n('writeassist_languagetool_api_key'));
$field->setNotice($package->i18n('writeassist_languagetool_api_key_notice'));

$form->addRawField('</fieldset>');

// === AI Generator Provider Settings ===
$form->addRawField('<fieldset><legend>' . $package->i18n('writeassist_ai_provider_settings') . '</legend>');

$field = $form->addSelectField('ai_provider');
$field->setLabel($package->i18n('writeassist_ai_provider'));
$select = $field->getSelect();
$select->addOption('Google Gemini', 'gemini');
$select->addOption('OpenWebUI / OpenAI Compatible', 'openwebui');
$field->setAttribute('id', 'ai-provider-select');
$field->setNotice($package->i18n('writeassist_ai_provider_notice'));

$form->addRawField('<div id="gemini-settings" class="ai-provider-settings">');

$field = $form->addInputField('text', 'gemini_api_key', null, ['class' => 'form-control']);
$field->setLabel($package->i18n('writeassist_gemini_api_key'));
$field->setNotice($package->i18n('writeassist_gemini_api_key_notice'));

$field = $form->addSelectField('gemini_model');
$field->setLabel($package->i18n('writeassist_gemini_model'));
$select = $field->getSelect();
$select->addOption('Gemini 2.5 Flash (empfohlen)', 'gemini-2.5-flash');
$select->addOption('Gemini 2.5 Flash-Lite (schnellstes)', 'gemini-2.5-flash-lite');
$select->addOption('Gemini 2.5 Pro (beste Qualität)', 'gemini-2.5-pro');
$select->addOption('Gemini 3 Pro (Preview)', 'gemini-3-pro-preview');
$field->setNotice($package->i18n('writeassist_gemini_model_notice'));

$form->addRawField('</div>');

$form->addRawField('<div id="openwebui-settings" class="ai-provider-settings" style="display:none;">');

$field = $form->addInputField('text', 'openwebui_api_key', null, ['class' => 'form-control']);
$field->setLabel($package->i18n('writeassist_openwebui_api_key'));
$field->setNotice($package->i18n('writeassist_openwebui_api_key_notice'));

$field = $form->addInputField('text', 'openwebui_base_url', null, ['class' => 'form-control', 'placeholder' => 'http://localhost:3000/api']);
$field->setLabel($package->i18n('writeassist_openwebui_base_url'));
$field->setNotice($package->i18n('writeassist_openwebui_base_url_notice'));

$field = $form->addInputField('text', 'openwebui_model', null, ['class' => 'form-control', 'placeholder' => 'llava, llama2, mistral']);
$field->setLabel($package->i18n('writeassist_openwebui_model'));
$field->setNotice($package->i18n('writeassist_openwebui_model_notice'));

$form->addRawField('</div>');

$form->addRawField('<div class="form-group" style="margin-top:15px; padding-top:15px; border-top:1px solid #eee;">');
$form->addRawField('<button type="button" class="btn btn-info" id="test-ai-connection"><i class="rex-icon fa-plug"></i> ' . $package->i18n('writeassist_test_connection') . '</button>');
$form->addRawField('<span id="test-connection-result" style="margin-left:10px; font-weight:bold;"></span>');
$form->addRawField('</div>');

$form->addRawField('</fieldset>');

// === Ignore List ===
$form->addRawField('<fieldset><legend>' . $package->i18n('writeassist_ignore_settings') . '</legend>');

$field = $form->addTextAreaField('ignore_words', null, ['class' => 'form-control', 'rows' => 5]);
$field->setLabel($package->i18n('writeassist_ignore_words'));
$field->setNotice($package->i18n('writeassist_ignore_words_notice'));

$form->addRawField('</fieldset>');

// === Integration Settings ===
$form->addRawField('<fieldset><legend>' . $package->i18n('writeassist_integration_settings') . '</legend>');

$field = $form->addSelectField('enable_infocenter_widget');
$field->setLabel($package->i18n('writeassist_enable_infocenter_widget'));
$select = $field->getSelect();
$select->addOption($package->i18n('writeassist_yes'), '1');
$select->addOption($package->i18n('writeassist_no'), '0');
$field->setNotice($package->i18n('writeassist_enable_infocenter_widget_notice'));

$field = $form->addSelectField('enable_tinymce_plugin');
$field->setLabel($package->i18n('writeassist_enable_tinymce_plugin'));
$select = $field->getSelect();
$select->addOption($package->i18n('writeassist_yes'), '1');
$select->addOption($package->i18n('writeassist_no'), '0');
$field->setNotice($package->i18n('writeassist_enable_tinymce_plugin_notice'));

$field = $form->addSelectField('enable_auto_translate');
$field->setLabel($package->i18n('writeassist_enable_auto_translate'));
$select = $field->getSelect();
$select->addOption($package->i18n('writeassist_yes'), '1');
$select->addOption($package->i18n('writeassist_no'), '0');
$field->setNotice($package->i18n('writeassist_enable_auto_translate_notice'));

$form->addRawField('</fieldset>');

// -------------------------------------------------------------------------
// 2-spaltiges Layout: Form (links) + Sidebar (rechts)
// -------------------------------------------------------------------------
$body = '
<div class="row">
    <div class="col-sm-8">' . $form->get() . '</div>
    <div class="col-sm-4">' . $sidebar . '</div>
</div>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $package->i18n('writeassist_settings'), false);
$fragment->setVar('body', $body, false);
echo $fragment->parse('core/page/section.php');

