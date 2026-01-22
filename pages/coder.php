<?php

declare(strict_types=1);

/**
 * WriteAssist - AI Code Generator (Admin only)
 */

use FriendsOfREDAXO\WriteAssist\GeminiApi;
use FriendsOfREDAXO\WriteAssist\WriteAssistAiFactory;

$package = rex_addon::get('writeassist');
$api = new GeminiApi();

// Get current provider label
$providerKey = rex_config::get('writeassist', 'ai_provider', 'gemini');
$providerLabel = WriteAssistAiFactory::PROVIDERS[$providerKey] ?? 'Unknown Provider';

// Security warning
echo rex_view::info('
    <i class="fa fa-shield"></i> <strong>' . $package->i18n('writeassist_code_security_title') . '</strong><br>
    ' . $package->i18n('writeassist_code_security_notice', $providerLabel) . '
');

// Check if API key is configured
if (!$api->isConfigured()) {
    echo rex_view::warning(
        $package->i18n('writeassist_no_gemini_key') . ' ' .
        '<a href="' . rex_url::backendPage('writeassist/settings') . '">' . 
        $package->i18n('writeassist_settings') . '</a>'
    );
}

?>

<div class="writeassist-coder-page">
    
    <div class="row">
        <div class="col-md-6">
            <!-- Input Panel -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-code"></i> <?= $package->i18n('writeassist_code_input') ?>
                    </h3>
                </div>
                <div class="panel-body">
                    
                    <!-- Action Select -->
                    <div class="form-group">
                        <label for="writeassist-code-action"><?= $package->i18n('writeassist_action') ?></label>
                        <select id="writeassist-code-action" class="form-control">
                            <option value="code_generate"><?= $package->i18n('writeassist_code_generate') ?></option>
                            <option value="code_ask"><?= $package->i18n('writeassist_code_ask') ?></option>
                            <option value="code_explain"><?= $package->i18n('writeassist_code_explain') ?></option>
                            <option value="code_improve"><?= $package->i18n('writeassist_code_improve') ?></option>
                        </select>
                    </div>
                    
                    <!-- Question input (for ask action) -->
                    <div class="form-group" id="writeassist-question-group" style="display:none;">
                        <label for="writeassist-question"><?= $package->i18n('writeassist_code_question') ?></label>
                        <input type="text" id="writeassist-question" class="form-control" 
                            placeholder="<?= $package->i18n('writeassist_code_question_placeholder') ?>">
                    </div>
                    
                    <!-- Language Select -->
                    <div class="form-group">
                        <label for="writeassist-language"><?= $package->i18n('writeassist_code_language') ?></label>
                        <select id="writeassist-language" class="form-control">
                            <option value="php">PHP</option>
                            <option value="javascript">JavaScript</option>
                            <option value="typescript">TypeScript</option>
                            <option value="python">Python</option>
                            <option value="sql">SQL</option>
                            <option value="html">HTML</option>
                            <option value="css">CSS/SCSS</option>
                            <option value="bash">Bash/Shell</option>
                        </select>
                    </div>
                    
                    <!-- Input Text/Code -->
                    <div class="form-group">
                        <label for="writeassist-code-input"><?= $package->i18n('writeassist_code_input_label') ?></label>
                        <textarea id="writeassist-code-input" class="form-control" rows="12" 
                            style="font-family: monospace; font-size: 13px;"
                            placeholder="<?= $package->i18n('writeassist_code_input_placeholder') ?>"></textarea>
                    </div>
                    
                    <!-- Generate Button -->
                    <button type="button" id="writeassist-code-btn" class="btn btn-primary" <?= !$api->isConfigured() ? 'disabled' : '' ?>>
                        <i class="fa fa-bolt"></i> <?= $package->i18n('writeassist_generate') ?>
                    </button>
                    
                    <span id="writeassist-code-status" class="text-muted" style="margin-left:15px;"></span>
                    
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- Output Panel -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-file-code-o"></i> <?= $package->i18n('writeassist_code_output') ?>
                    </h3>
                </div>
                <div class="panel-body">
                    
                    <div class="form-group">
                        <textarea id="writeassist-code-output" class="form-control" rows="16" readonly 
                            style="font-family: monospace; font-size: 13px; background: #1e1e1e; color: #d4d4d4;"
                            placeholder="<?= $package->i18n('writeassist_code_output_placeholder') ?>"></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" id="writeassist-code-copy-btn" class="btn btn-default">
                            <i class="fa fa-copy"></i> <?= $package->i18n('writeassist_copy') ?>
                        </button>
                        <button type="button" id="writeassist-code-use-btn" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> <?= $package->i18n('writeassist_use_as_input') ?>
                        </button>
                    </div>
                    
                    <div id="writeassist-code-usage" class="text-muted pull-right" style="margin-top:8px;"></div>
                    
                </div>
            </div>
        </div>
    </div>
    
</div>

<script>
(function() {
    const actionSelect = document.getElementById('writeassist-code-action');
    const languageSelect = document.getElementById('writeassist-language');
    const questionGroup = document.getElementById('writeassist-question-group');
    const questionEl = document.getElementById('writeassist-question');
    const generateBtn = document.getElementById('writeassist-code-btn');
    const statusEl = document.getElementById('writeassist-code-status');
    const inputEl = document.getElementById('writeassist-code-input');
    const outputEl = document.getElementById('writeassist-code-output');
    const usageEl = document.getElementById('writeassist-code-usage');
    const copyBtn = document.getElementById('writeassist-code-copy-btn');
    const useBtn = document.getElementById('writeassist-code-use-btn');
    
    // Update placeholder and visibility based on action
    function updateUI() {
        const action = actionSelect.value;
        questionGroup.style.display = action === 'code_ask' ? 'block' : 'none';
        
        if (action === 'code_generate') {
            inputEl.placeholder = '<?= $package->i18n('writeassist_code_generate_placeholder') ?>';
        } else if (action === 'code_ask') {
            inputEl.placeholder = '<?= $package->i18n('writeassist_code_ask_code_placeholder') ?>';
        } else {
            inputEl.placeholder = '<?= $package->i18n('writeassist_code_paste_placeholder') ?>';
        }
    }
    
    actionSelect.addEventListener('change', updateUI);
    updateUI(); // Initial call
    
    // Generate
    generateBtn.addEventListener('click', function() {
        const action = actionSelect.value;
        const language = languageSelect.value;
        const text = inputEl.value.trim();
        const question = questionEl ? questionEl.value.trim() : '';
        
        if (action === 'code_ask' && !question) {
            statusEl.textContent = '<?= $package->i18n('writeassist_enter_question') ?>';
            return;
        }
        
        if (!text) {
            statusEl.textContent = '<?= $package->i18n('writeassist_enter_text') ?>';
            return;
        }
        
        generateBtn.disabled = true;
        statusEl.innerHTML = '<i class="fa fa-spinner fa-spin"></i> <?= $package->i18n('writeassist_generating') ?>';
        
        const formData = new FormData();
        formData.append('rex-api-call', 'writeassist_generate');
        formData.append('action', action);
        formData.append('text', text);
        formData.append('language', language);
        if (action === 'code_ask') {
            formData.append('prompt', question);
        }
        
        fetch(window.location.href.split('?')[0] + '?rex-api-call=writeassist_generate', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clean up markdown code blocks if present
                let code = data.text;
                code = code.replace(/^```[\w]*\n?/gm, '').replace(/```$/gm, '').trim();
                outputEl.value = code;
                
                if (data.usage) {
                    usageEl.textContent = data.usage.total_tokens + ' <?= $package->i18n('writeassist_tokens_used') ?>';
                }
                statusEl.textContent = '';
            } else {
                statusEl.textContent = data.error || '<?= $package->i18n('writeassist_generation_failed') ?>';
            }
        })
        .catch(error => {
            statusEl.textContent = '<?= $package->i18n('writeassist_generation_failed') ?>: ' + error.message;
        })
        .finally(() => {
            generateBtn.disabled = false;
        });
    });
    
    // Copy to clipboard
    copyBtn.addEventListener('click', function() {
        outputEl.select();
        document.execCommand('copy');
        
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fa fa-check"></i> Kopiert!';
        setTimeout(() => { this.innerHTML = originalText; }, 2000);
    });
    
    // Use as input
    useBtn.addEventListener('click', function() {
        inputEl.value = outputEl.value;
        outputEl.value = '';
        usageEl.textContent = '';
    });
})();
</script>
