<?php

/**
 * WriteAssist – Bulk Translate Page
 *
 * Allows batch translation of existing article and category names
 * from a source language into all other active languages via DeepL.
 */

$package = rex_addon::get('writeassist');

rex_view::addJsFile($package->getAssetsUrl('js/writeassist-bulk-translate.js'));

// Check prerequisites
$apiKey = trim((string) $package->getConfig('api_key', ''));
if ($apiKey === '') {
    echo rex_view::warning($package->i18n('writeassist_bulk_translate_no_api_key'));
}

$clangs = rex_clang::getAll();
$clangCount = count($clangs);

// Build source lang options
$clangOptions = '';
foreach ($clangs as $clang) {
    $clangOptions .= '<option value="' . $clang->getId() . '">' . rex_escape($clang->getName()) . ' (' . rex_escape($clang->getCode()) . ')</option>';
}

$content = '
<div class="row">
    <div class="col-sm-8">
        <div class="panel panel-default" id="wa-bulk-form-panel">
            <div class="panel-heading">
                <strong><i class="rex-icon fa-language"></i> ' . $package->i18n('writeassist_bulk_translate_title') . '</strong>
            </div>
            <div class="panel-body">
                <p class="text-muted">' . $package->i18n('writeassist_bulk_translate_description') . '</p>

                <div class="form-group">
                    <label for="wa-bulk-source-clang">' . $package->i18n('writeassist_bulk_translate_source_lang') . '</label>
                    <select id="wa-bulk-source-clang" class="form-control selectpicker">
                        ' . $clangOptions . '
                    </select>
                </div>

                <div class="form-group">
                    <label>' . $package->i18n('writeassist_bulk_translate_type') . '</label>
                    <div>
                        <label class="checkbox-inline">
                            <input type="radio" name="wa-bulk-type" value="both" checked> ' . $package->i18n('writeassist_bulk_translate_type_both') . '
                        </label>
                        &nbsp;
                        <label class="checkbox-inline">
                            <input type="radio" name="wa-bulk-type" value="articles"> ' . $package->i18n('writeassist_bulk_translate_type_articles') . '
                        </label>
                        &nbsp;
                        <label class="checkbox-inline">
                            <input type="radio" name="wa-bulk-type" value="categories"> ' . $package->i18n('writeassist_bulk_translate_type_categories') . '
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox">
                        <input type="checkbox" id="wa-bulk-only-untranslated" value="1" checked>
                        ' . $package->i18n('writeassist_bulk_translate_only_untranslated') . '
                        <br><small class="text-muted">' . $package->i18n('writeassist_bulk_translate_only_untranslated_notice') . '</small>
                    </label>
                </div>

                <hr>

                <button type="button" class="btn btn-primary" id="wa-bulk-start" ' . ($apiKey === '' ? 'disabled' : '') . '>
                    <i class="rex-icon fa-play"></i> ' . $package->i18n('writeassist_bulk_translate_start') . '
                </button>
            </div>
        </div>

        <div id="wa-bulk-progress" style="display:none;">
            <div class="panel panel-default">
                <div class="panel-heading"><strong><i class="rex-icon fa-spinner fa-spin" id="wa-bulk-spinner"></i> ' . $package->i18n('writeassist_bulk_translate_running') . '</strong></div>
                <div class="panel-body">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped active" id="wa-bulk-bar" role="progressbar" style="width:100%">' . $package->i18n('writeassist_bulk_translate_please_wait') . '</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="wa-bulk-result" style="display:none;"></div>
    </div>

    <div class="col-sm-4">
        <div class="panel panel-info">
            <div class="panel-heading"><strong><i class="rex-icon fa-info-circle"></i> ' . $package->i18n('writeassist_bulk_translate_info_title') . '</strong></div>
            <div class="panel-body">
                <p>' . $package->i18n('writeassist_bulk_translate_info_text') . '</p>
                <ul>
                    <li>' . $package->i18n('writeassist_bulk_translate_info_item1') . '</li>
                    <li>' . $package->i18n('writeassist_bulk_translate_info_item2') . '</li>
                    <li>' . $package->i18n('writeassist_bulk_translate_info_item3') . '</li>
                </ul>
            </div>
        </div>
        <div class="panel panel-warning">
            <div class="panel-heading"><strong><i class="rex-icon fa-exclamation-triangle"></i> ' . $package->i18n('writeassist_bulk_translate_warning_title') . '</strong></div>
            <div class="panel-body">
                <p>' . $package->i18n('writeassist_bulk_translate_warning_text') . '</p>
            </div>
        </div>
    </div>
</div>';

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $package->i18n('writeassist_bulk_translate_page_title'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
