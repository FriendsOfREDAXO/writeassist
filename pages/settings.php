<?php

/**
 * WriteAssist - Settings Page
 */

$package = rex_addon::get('writeassist');

// Single form for all settings
$form = rex_config_form::factory($package->getName());

// === DeepL Settings ===
$form->addRawField('<fieldset><legend>' . $package->i18n('writeassist_deepl_settings') . '</legend>');

// DeepL API Key
$field = $form->addInputField('text', 'api_key', null, ['class' => 'form-control']);
$field->setLabel($package->i18n('writeassist_api_key'));
$field->setNotice($package->i18n('writeassist_api_key_notice'));

// DeepL API Type
$field = $form->addSelectField('use_free_api');
$field->setLabel($package->i18n('writeassist_api_type'));
$select = $field->getSelect();
$select->addOption($package->i18n('writeassist_api_type_free'), '1');
$select->addOption($package->i18n('writeassist_api_type_pro'), '0');
$field->setNotice($package->i18n('writeassist_api_type_notice'));

$form->addRawField('</fieldset>');

// === LanguageTool Settings ===
$form->addRawField('<fieldset><legend>' . $package->i18n('writeassist_languagetool_settings') . '</legend>');

// Custom API URL (for self-hosted)
$field = $form->addInputField('text', 'languagetool_api_url', null, ['class' => 'form-control']);
$field->setLabel($package->i18n('writeassist_languagetool_url'));
$field->setNotice($package->i18n('writeassist_languagetool_url_notice'));

// Premium Username
$field = $form->addInputField('text', 'languagetool_username', null, ['class' => 'form-control']);
$field->setLabel($package->i18n('writeassist_languagetool_username'));

// Premium API Key
$field = $form->addInputField('text', 'languagetool_api_key', null, ['class' => 'form-control']);
$field->setLabel($package->i18n('writeassist_languagetool_api_key'));
$field->setNotice($package->i18n('writeassist_languagetool_api_key_notice'));

$form->addRawField('</fieldset>');

// === AI Generator Provider Settings ===
$form->addRawField('<fieldset><legend>' . $package->i18n('writeassist_ai_provider_settings') . '</legend>');

// AI Provider Selection
$field = $form->addSelectField('ai_provider');
$field->setLabel($package->i18n('writeassist_ai_provider'));
$select = $field->getSelect();
$select->addOption('Google Gemini', 'gemini');
$select->addOption('OpenWebUI / OpenAI Compatible', 'openwebui');
$field->setAttribute('id', 'ai-provider-select');
$field->setNotice($package->i18n('writeassist_ai_provider_notice'));

// === GEMINI SETTINGS ===
$form->addRawField('<div id="gemini-settings" class="ai-provider-settings">');

// Gemini API Key
$field = $form->addInputField('text', 'gemini_api_key', null, ['class' => 'form-control']);
$field->setLabel($package->i18n('writeassist_gemini_api_key'));
$field->setNotice($package->i18n('writeassist_gemini_api_key_notice'));

// Default Model
$field = $form->addSelectField('gemini_model');
$field->setLabel($package->i18n('writeassist_gemini_model'));
$select = $field->getSelect();
$select->addOption('Gemini 2.5 Flash (empfohlen)', 'gemini-2.5-flash');
$select->addOption('Gemini 2.5 Flash-Lite (schnellstes)', 'gemini-2.5-flash-lite');
$select->addOption('Gemini 2.5 Pro (beste Qualität)', 'gemini-2.5-pro');
$select->addOption('Gemini 3 Pro (Preview)', 'gemini-3-pro-preview');
$field->setNotice($package->i18n('writeassist_gemini_model_notice'));

$form->addRawField('</div>'); // End Gemini settings

// === OPEN WEB UI SETTINGS ===
$form->addRawField('<div id="openwebui-settings" class="ai-provider-settings" style="display:none;">');

// API Key
$field = $form->addInputField('text', 'openwebui_api_key', null, ['class' => 'form-control']);
$field->setLabel($package->i18n('writeassist_openwebui_api_key'));
$field->setNotice($package->i18n('writeassist_openwebui_api_key_notice'));

// Base URL
$field = $form->addInputField('text', 'openwebui_base_url', null, ['class' => 'form-control', 'placeholder' => 'http://localhost:3000/api']);
$field->setLabel($package->i18n('writeassist_openwebui_base_url'));
$field->setNotice($package->i18n('writeassist_openwebui_base_url_notice'));

// Model
$field = $form->addInputField('text', 'openwebui_model', null, ['class' => 'form-control', 'placeholder' => 'llava, llama2, mistral']);
$field->setLabel($package->i18n('writeassist_openwebui_model'));
$field->setNotice($package->i18n('writeassist_openwebui_model_notice'));

$form->addRawField('</div>'); // End OpenWebUI settings

// Test connection button
$form->addRawField('<div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">');
$form->addRawField('<button type="button" class="btn btn-info" id="test-ai-connection"><i class="rex-icon fa-plug"></i> Verbindung testen</button>');
$form->addRawField('<span id="test-connection-result" style="margin-left: 10px; font-weight: bold;"></span>');
$form->addRawField('</div>');

$form->addRawField('</fieldset>');

// JavaScript for toggling providers and testing connection
$form->addRawField('
<script>
document.addEventListener("DOMContentLoaded", function() {
    var providerSelect = document.getElementById("ai-provider-select");
    
    function updateVisibility() {
        var value = providerSelect.value;
        document.querySelectorAll(".ai-provider-settings").forEach(function(el) {
            el.style.display = "none";
        });
        
        var targetParam = value === "gemini" ? "gemini" : "openwebui";
        var target = document.getElementById(targetParam + "-settings");
        if (target) {
            target.style.display = "block";
        }
    }
    
    if (providerSelect) {
        providerSelect.addEventListener("change", updateVisibility);
        updateVisibility();
    }
    
    // Test Connection
    var testBtn = document.getElementById("test-ai-connection");
    if (testBtn) {
        testBtn.addEventListener("click", function() {
            var resultSpan = document.getElementById("test-connection-result");
            resultSpan.innerHTML = "<i class=\'rex-icon fa-spinner fa-spin\'></i> Teste Verbindung...";
            resultSpan.className = "";
            testBtn.disabled = true;
            
            // Save params could be tricky if form is not saved. 
            // For now we assume user saved settings.
            
            fetch("index.php?rex-api-call=writeassist_ai_test")
            .then(response => response.json())
            .then(data => {
                testBtn.disabled = false;
                if (data.success) {
                    resultSpan.innerHTML = "<span class=\'text-success\'><i class=\'rex-icon fa-check\'></i> " + data.message + "</span>";
                } else {
                    resultSpan.innerHTML = "<span class=\'text-danger\'><i class=\'rex-icon fa-exclamation-triangle\'></i> " + data.message + "</span>";
                }
            })
            .catch(error => {
                testBtn.disabled = false;
                resultSpan.innerHTML = "<span class=\'text-danger\'><i class=\'rex-icon fa-exclamation-triangle\'></i> Fehler: " + error + "</span>";
            });
        });
    }
});
</script>
');

// === Ignore List Settings ===
$form->addRawField('<fieldset><legend>' . $package->i18n('writeassist_ignore_settings') . '</legend>');

// Ignore words (one per line)
$field = $form->addTextAreaField('ignore_words', null, ['class' => 'form-control', 'rows' => 8]);
$field->setLabel($package->i18n('writeassist_ignore_words'));
$field->setNotice($package->i18n('writeassist_ignore_words_notice'));

$form->addRawField('</fieldset>');

// === Integration Settings ===
$form->addRawField('<fieldset><legend>' . $package->i18n('writeassist_integration_settings') . '</legend>');

// Info Center Widget
$field = $form->addSelectField('enable_infocenter_widget');
$field->setLabel($package->i18n('writeassist_enable_infocenter_widget'));
$select = $field->getSelect();
$select->addOption($package->i18n('writeassist_yes'), '1');
$select->addOption($package->i18n('writeassist_no'), '0');
$field->setNotice($package->i18n('writeassist_enable_infocenter_widget_notice'));

// TinyMCE Plugin
$field = $form->addSelectField('enable_tinymce_plugin');
$field->setLabel($package->i18n('writeassist_enable_tinymce_plugin'));
$select = $field->getSelect();
$select->addOption($package->i18n('writeassist_yes'), '1');
$select->addOption($package->i18n('writeassist_no'), '0');
$field->setNotice($package->i18n('writeassist_enable_tinymce_plugin_notice'));

// Auto-Translate on Article/Category creation
$field = $form->addSelectField('enable_auto_translate');
$field->setLabel($package->i18n('writeassist_enable_auto_translate'));
$select = $field->getSelect();
$select->addOption($package->i18n('writeassist_yes'), '1');
$select->addOption($package->i18n('writeassist_no'), '0');
$field->setNotice($package->i18n('writeassist_enable_auto_translate_notice'));

$form->addRawField('</fieldset>');

// Render form
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $package->i18n('writeassist_settings'), false);
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');
