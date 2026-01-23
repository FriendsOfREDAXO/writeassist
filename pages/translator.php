<?php

declare(strict_types=1);

/**
 * WriteAssist - Translator Page (DeepL)
 */

use FriendsOfREDAXO\WriteAssist\DeeplApi;

$package = rex_addon::get('writeassist');
$api = new DeeplApi();

// Check if API key is configured
$apiKey = $package->getConfig('api_key', '');
if ($apiKey === '' || $apiKey === null) {
    echo rex_view::warning(rex_i18n::rawMsg('writeassist_no_api_key_warning', rex_url::backendPage('writeassist/settings')));
}

// Get usage statistics
$usage = $api->getUsage();
$usagePercent = 0;
if ($usage['character_limit'] > 0) {
    $usagePercent = round(($usage['character_count'] / $usage['character_limit']) * 100, 2);
}

?>

<div class="writeassist-translator-page">
    
    <?php if (!empty($apiKey) && !isset($usage['error'])): ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><?= $package->i18n('writeassist_usage_title') ?></h3>
        </div>
        <div class="panel-body">
            <div class="progress">
                <div class="progress-bar <?= $usagePercent > 90 ? 'progress-bar-danger' : ($usagePercent > 75 ? 'progress-bar-warning' : 'progress-bar-success') ?>" 
                     role="progressbar" 
                     style="width: <?= $usagePercent ?>%">
                    <?= $usagePercent ?>%
                </div>
            </div>
            <p><?= number_format($usage['character_count']) ?> / <?= number_format($usage['character_limit']) ?> <?= $package->i18n('writeassist_characters_used') ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><?= $package->i18n('writeassist_translator') ?></h3>
        </div>
        <div class="panel-body">
            <div class="writeassist-translator-interface">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><?= $package->i18n('writeassist_source_language') ?></label>
                            <select class="form-control" id="writeassist-source-lang">
                                <option value=""><?= $package->i18n('writeassist_auto_detect') ?></option>
                                <option value="DE">Deutsch</option>
                                <option value="EN">English</option>
                                <option value="FR">Français</option>
                                <option value="ES">Español</option>
                                <option value="IT">Italiano</option>
                                <option value="NL">Nederlands</option>
                                <option value="PL">Polski</option>
                                <option value="PT">Português</option>
                                <option value="RU">Русский</option>
                                <option value="JA">日本語</option>
                                <option value="ZH">中文</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?= $package->i18n('writeassist_source_text') ?></label>
                            <textarea class="form-control" id="writeassist-source-text" rows="10" placeholder="<?= $package->i18n('writeassist_enter_text') ?>"></textarea>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><?= $package->i18n('writeassist_target_language') ?></label>
                            <select class="form-control" id="writeassist-target-lang">
                                <option value="DE">Deutsch</option>
                                <option value="EN" selected>English</option>
                                <option value="FR">Français</option>
                                <option value="ES">Español</option>
                                <option value="IT">Italiano</option>
                                <option value="NL">Nederlands</option>
                                <option value="PL">Polski</option>
                                <option value="PT">Português</option>
                                <option value="RU">Русский</option>
                                <option value="JA">日本語</option>
                                <option value="ZH">中文</option>
                                <option value="KO">한국어</option>
                                <option value="CS">Čeština</option>
                                <option value="DA">Dansk</option>
                                <option value="EL">Ελληνικά</option>
                                <option value="FI">Suomi</option>
                                <option value="HU">Magyar</option>
                                <option value="ID">Indonesia</option>
                                <option value="NB">Norsk</option>
                                <option value="RO">Română</option>
                                <option value="SK">Slovenčina</option>
                                <option value="SV">Svenska</option>
                                <option value="TR">Türkçe</option>
                                <option value="UK">Українська</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?= $package->i18n('writeassist_translated_text') ?></label>
                            <textarea class="form-control" id="writeassist-target-text" rows="10" readonly></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="button" class="btn btn-primary" id="writeassist-translate-btn" <?= empty($apiKey) ? 'disabled' : '' ?>>
                        <i class="rex-icon fa-language"></i> <?= $package->i18n('writeassist_translate') ?>
                    </button>
                    <button type="button" class="btn btn-default" id="writeassist-copy-btn" style="display:none;">
                        <i class="rex-icon fa-copy"></i> <?= $package->i18n('writeassist_copy') ?>
                    </button>
                    <button type="button" class="btn btn-default" id="writeassist-clear-btn">
                        <i class="rex-icon fa-trash"></i> <?= $package->i18n('writeassist_clear') ?>
                    </button>
                    <button type="button" class="btn btn-default" id="writeassist-swap-btn">
                        <i class="rex-icon fa-exchange"></i> <?= $package->i18n('writeassist_swap') ?>
                    </button>
                </div>
                
                <div id="writeassist-message" style="display:none; margin-top:15px;"></div>
            </div>
        </div>
    </div>
    
</div>

<script nonce="<?= rex_response::getNonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const translateBtn = document.getElementById('writeassist-translate-btn');
    const copyBtn = document.getElementById('writeassist-copy-btn');
    const clearBtn = document.getElementById('writeassist-clear-btn');
    const swapBtn = document.getElementById('writeassist-swap-btn');
    const sourceText = document.getElementById('writeassist-source-text');
    const targetText = document.getElementById('writeassist-target-text');
    const sourceLang = document.getElementById('writeassist-source-lang');
    const targetLang = document.getElementById('writeassist-target-lang');
    const message = document.getElementById('writeassist-message');
    
    translateBtn?.addEventListener('click', async function() {
        const text = sourceText.value.trim();
        if (!text) return;
        
        translateBtn.disabled = true;
        translateBtn.innerHTML = '<i class="rex-icon fa-spinner fa-spin"></i> ...';
        
        try {
            const response = await fetch('./index.php?rex-api-call=writeassist_translate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    text: text,
                    target_lang: targetLang.value,
                    source_lang: sourceLang.value
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                targetText.value = data.translation;
                copyBtn.style.display = 'inline-block';
                message.style.display = 'none';
            } else {
                message.className = 'alert alert-danger';
                message.textContent = data.error || 'Translation failed';
                message.style.display = 'block';
            }
        } catch (e) {
            message.className = 'alert alert-danger';
            message.textContent = e.message;
            message.style.display = 'block';
        }
        
        translateBtn.disabled = false;
        translateBtn.innerHTML = '<i class="rex-icon fa-language"></i> <?= $package->i18n('writeassist_translate') ?>';
    });
    
    copyBtn?.addEventListener('click', function() {
        navigator.clipboard.writeText(targetText.value);
        copyBtn.innerHTML = '<i class="rex-icon fa-check"></i> Copied!';
        setTimeout(() => {
            copyBtn.innerHTML = '<i class="rex-icon fa-copy"></i> <?= $package->i18n('writeassist_copy') ?>';
        }, 2000);
    });
    
    clearBtn?.addEventListener('click', function() {
        sourceText.value = '';
        targetText.value = '';
        copyBtn.style.display = 'none';
        message.style.display = 'none';
    });
    
    swapBtn?.addEventListener('click', function() {
        const temp = sourceText.value;
        sourceText.value = targetText.value;
        targetText.value = temp;
        
        const tempLang = sourceLang.value;
        if (targetLang.value) {
            sourceLang.value = targetLang.value;
        }
        targetLang.value = tempLang || 'EN';
    });
});
</script>
