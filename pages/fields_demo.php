<?php

declare(strict_types=1);

/**
 * WriteAssist - Demo: watext / watranslate Feld-Widgets
 */

use FriendsOfREDAXO\WriteAssist\WriteAssistAiFactory;

$package = rex_addon::get('writeassist');

$providerKey = rex_config::get('writeassist', 'ai_provider', 'gemini');
$providerLabel = WriteAssistAiFactory::PROVIDERS[$providerKey] ?? $providerKey;
$translationProvider = rex_config::get('writeassist', 'translation_provider', 'deepl');
$translationLabel = $translationProvider === 'ai' ? $providerLabel . ' (KI)' : 'DeepL';

echo rex_view::info('
    <i class="rex-icon fa-info-circle"></i> <strong>' . $package->i18n('writeassist_fields_demo_title') . '</strong><br>
    ' . $package->i18n('writeassist_fields_demo_intro')
);

?>

<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="rex-icon fa-magic"></i> class="watext"</h3>
            </div>
            <div class="panel-body">
                <p class="text-muted"><?= $package->i18n('writeassist_fields_demo_watext_hint') ?></p>

                <div class="form-group">
                    <label for="wa-demo-input-watext"><?= $package->i18n('writeassist_fields_demo_input_label') ?></label>
                    <input type="text" id="wa-demo-input-watext" class="form-control watext"
                        placeholder="<?= $package->i18n('writeassist_fields_demo_input_placeholder') ?>">
                </div>

                <div class="form-group">
                    <label for="wa-demo-textarea-watext"><?= $package->i18n('writeassist_fields_demo_textarea_label') ?></label>
                    <textarea id="wa-demo-textarea-watext" class="form-control watext" rows="5"
                        placeholder="<?= $package->i18n('writeassist_fields_demo_textarea_placeholder') ?>"></textarea>
                </div>

                <p class="help-block">
                    <code>&lt;textarea class="watext"&gt;...&lt;/textarea&gt;</code><br>
                    <?= $package->i18n('writeassist_fields_demo_watext_provider', $providerLabel) ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="rex-icon fa-language"></i> class="watranslate"</h3>
            </div>
            <div class="panel-body">
                <p class="text-muted"><?= $package->i18n('writeassist_fields_demo_watranslate_hint') ?></p>

                <div class="form-group">
                    <label for="wa-demo-input-watranslate"><?= $package->i18n('writeassist_fields_demo_input_label') ?></label>
                    <input type="text" id="wa-demo-input-watranslate" class="form-control watranslate"
                        value="<?= $package->i18n('writeassist_fields_demo_translate_sample') ?>">
                </div>

                <div class="form-group">
                    <label for="wa-demo-textarea-watranslate"><?= $package->i18n('writeassist_fields_demo_textarea_label') ?></label>
                    <textarea id="wa-demo-textarea-watranslate" class="form-control watranslate" rows="5"
                        placeholder="<?= $package->i18n('writeassist_fields_demo_textarea_placeholder') ?>"></textarea>
                </div>

                <p class="help-block">
                    <code>&lt;input class="watranslate"&gt;</code><br>
                    <?= $package->i18n('writeassist_fields_demo_watranslate_provider', $translationLabel) ?>
                    <a href="<?= rex_url::backendPage('writeassist/settings') ?>"><?= $package->i18n('writeassist_settings') ?></a>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><i class="rex-icon fa-magic"></i> class="watext watranslate"</h3>
    </div>
    <div class="panel-body">
        <p class="text-muted"><?= $package->i18n('writeassist_fields_demo_combined_hint') ?></p>

        <div class="form-group">
            <label for="wa-demo-input-combined"><?= $package->i18n('writeassist_fields_demo_input_label') ?></label>
            <input type="text" id="wa-demo-input-combined" class="form-control watext watranslate"
                placeholder="<?= $package->i18n('writeassist_fields_demo_input_placeholder') ?>">
        </div>

        <p class="help-block"><code>&lt;input class="watext watranslate"&gt;</code></p>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><i class="rex-icon fa-text-width"></i> maxlength</h3>
    </div>
    <div class="panel-body">
        <p class="text-muted"><?= $package->i18n('writeassist_fields_demo_maxlength_hint') ?></p>

        <div class="form-group">
            <label for="wa-demo-input-maxlength"><?= $package->i18n('writeassist_fields_demo_input_label') ?> (maxlength="40")</label>
            <input type="text" id="wa-demo-input-maxlength" class="form-control watext" maxlength="40"
                placeholder="<?= $package->i18n('writeassist_fields_demo_input_placeholder') ?>">
        </div>

        <p class="help-block"><code>&lt;input class="watext" maxlength="40"&gt;</code></p>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><i class="rex-icon fa-code"></i> <?= $package->i18n('writeassist_fields_demo_usage_title') ?></h3>
    </div>
    <div class="panel-body">
        <p><?= $package->i18n('writeassist_fields_demo_usage_intro') ?></p>
        <pre>&lt;textarea class="form-control watext" name="my_field"&gt;&lt;/textarea&gt;

&lt;input type="text" class="form-control watranslate" name="my_translated_field"&gt;

&lt;textarea class="form-control watext watranslate" name="my_combined_field"&gt;&lt;/textarea&gt;</pre>
        <p class="text-muted"><?= $package->i18n('writeassist_fields_demo_usage_note') ?></p>
    </div>
</div>
