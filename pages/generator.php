<?php

declare(strict_types=1);

/**
 * WriteAssist - AI Generator Page (Gemini)
 */

use FriendsOfREDAXO\WriteAssist\GeminiApi;

$package = rex_addon::get('writeassist');
$api = new GeminiApi();

// Check if API key is configured
if (!$api->isConfigured()) {
    echo rex_view::warning(
        $package->i18n('writeassist_no_gemini_key') . ' ' .
        '<a href="' . rex_url::backendPage('writeassist/settings') . '">' . 
        $package->i18n('writeassist_settings') . '</a>'
    );
}

?>

<div class="writeassist-generator-page">
    
    <div class="row">
        <div class="col-md-6">
            <!-- Input Panel -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-feather"></i> <?= $package->i18n('writeassist_generator_input') ?>
                    </h3>
                </div>
                <div class="panel-body">
                    
                    <!-- Action Select -->
                    <div class="form-group">
                        <label for="writeassist-action"><?= $package->i18n('writeassist_action') ?></label>
                        <select id="writeassist-action" class="form-control">
                            <option value="rewrite"><?= $package->i18n('writeassist_action_rewrite') ?></option>
                            <option value="summarize"><?= $package->i18n('writeassist_action_summarize') ?></option>
                            <option value="expand"><?= $package->i18n('writeassist_action_expand') ?></option>
                            <option value="generate"><?= $package->i18n('writeassist_action_generate') ?></option>
                            <option value="custom"><?= $package->i18n('writeassist_action_custom') ?></option>
                        </select>
                    </div>
                    
                    <!-- Style Select (for rewrite) -->
                    <div class="form-group" id="writeassist-style-group">
                        <label for="writeassist-style"><?= $package->i18n('writeassist_style') ?></label>
                        <select id="writeassist-style" class="form-control">
                            <option value="professional"><?= $package->i18n('writeassist_style_professional') ?></option>
                            <option value="casual"><?= $package->i18n('writeassist_style_casual') ?></option>
                            <option value="simple"><?= $package->i18n('writeassist_style_simple') ?></option>
                            <option value="formal"><?= $package->i18n('writeassist_style_formal') ?></option>
                            <option value="creative"><?= $package->i18n('writeassist_style_creative') ?></option>
                            <option value="concise"><?= $package->i18n('writeassist_style_concise') ?></option>
                        </select>
                    </div>
                    
                    <!-- Type Select (for generate) -->
                    <div class="form-group" id="writeassist-type-group" style="display:none;">
                        <label for="writeassist-type"><?= $package->i18n('writeassist_type') ?></label>
                        <select id="writeassist-type" class="form-control">
                            <option value="paragraph"><?= $package->i18n('writeassist_type_paragraph') ?></option>
                            <option value="headline"><?= $package->i18n('writeassist_type_headline') ?></option>
                            <option value="bullet_points"><?= $package->i18n('writeassist_type_bullets') ?></option>
                            <option value="intro"><?= $package->i18n('writeassist_type_intro') ?></option>
                            <option value="meta_description"><?= $package->i18n('writeassist_type_meta') ?></option>
                        </select>
                    </div>
                    
                    <!-- Custom Prompt (for custom) -->
                    <div class="form-group" id="writeassist-prompt-group" style="display:none;">
                        <label for="writeassist-prompt"><?= $package->i18n('writeassist_prompt') ?></label>
                        
                        <!-- Prompt Selection -->
                        <div class="input-group" style="margin-bottom: 5px;">
                            <select id="writeassist-prompt-select" class="form-control">
                                <option value=""><?= $package->i18n('writeassist_load_prompt') ?></option>
                            </select>
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button" id="writeassist-save-prompt-btn" title="<?= $package->i18n('writeassist_save_prompt') ?>">
                                    <i class="fa fa-floppy-o"></i>
                                </button>
                                <button class="btn btn-default" type="button" id="writeassist-delete-prompt-btn" title="<?= $package->i18n('writeassist_delete_prompt') ?>" disabled>
                                    <i class="fa fa-trash"></i>
                                </button>
                            </span>
                        </div>
                        
                        <textarea id="writeassist-prompt" class="form-control" rows="3" 
                            placeholder="<?= $package->i18n('writeassist_prompt_placeholder') ?>"></textarea>
                    </div>
                    
                    <!-- Input Text -->
                    <div class="form-group">
                        <label for="writeassist-input"><?= $package->i18n('writeassist_input_text') ?></label>
                        <textarea id="writeassist-input" class="form-control" rows="8" 
                            placeholder="<?= $package->i18n('writeassist_input_placeholder') ?>"></textarea>
                    </div>
                    
                    <!-- Generate Button -->
                    <button type="button" id="writeassist-generate-btn" class="btn btn-primary" <?= !$api->isConfigured() ? 'disabled' : '' ?>>
                        <i class="fa fa-bolt"></i> <?= $package->i18n('writeassist_generate') ?>
                    </button>
                    
                    <span id="writeassist-generate-status" class="text-muted" style="margin-left:15px;"></span>
                    
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- Output Panel -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-file-text-o"></i> <?= $package->i18n('writeassist_generator_output') ?>
                    </h3>
                </div>
                <div class="panel-body">
                    
                    <div class="form-group">
                        <textarea id="writeassist-output" class="form-control" rows="14" readonly 
                            placeholder="<?= $package->i18n('writeassist_output_placeholder') ?>"></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" id="writeassist-copy-btn" class="btn btn-default">
                            <i class="fa fa-copy"></i> <?= $package->i18n('writeassist_copy') ?>
                        </button>
                        <button type="button" id="writeassist-use-as-input-btn" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> <?= $package->i18n('writeassist_use_as_input') ?>
                        </button>
                    </div>
                    
                    <div id="writeassist-usage" class="text-muted pull-right" style="margin-top:8px;"></div>
                    
                </div>
            </div>
        </div>
    </div>
    
</div>

<script>
(function() {
    const actionSelect = document.getElementById('writeassist-action');
    const styleGroup = document.getElementById('writeassist-style-group');
    const typeGroup = document.getElementById('writeassist-type-group');
    const promptGroup = document.getElementById('writeassist-prompt-group');
    const generateBtn = document.getElementById('writeassist-generate-btn');
    const statusEl = document.getElementById('writeassist-generate-status');
    const inputEl = document.getElementById('writeassist-input');
    const outputEl = document.getElementById('writeassist-output');
    const usageEl = document.getElementById('writeassist-usage');
    const copyBtn = document.getElementById('writeassist-copy-btn');
    const useAsInputBtn = document.getElementById('writeassist-use-as-input-btn');
    const promptSelect = document.getElementById('writeassist-prompt-select');
    const savePromptBtn = document.getElementById('writeassist-save-prompt-btn');
    const deletePromptBtn = document.getElementById('writeassist-delete-prompt-btn');
    const promptTextarea = document.getElementById('writeassist-prompt');
    
    // Load prompts
    function loadPrompts() {
        fetch('./index.php?rex-api-call=writeassist_prompts&action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const currentVal = promptSelect.value;
                promptSelect.innerHTML = '<option value=""><?= $package->i18n('writeassist_load_prompt') ?></option>';
                data.prompts.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p.id;
                    option.textContent = p.title;
                    option.dataset.content = p.content;
                    promptSelect.appendChild(option);
                });
                if (currentVal) promptSelect.value = currentVal; // Restore selection if exists
            }
        });
    }
    
    // Toggle visibility based on action
    actionSelect.addEventListener('change', function() {
        const action = this.value;
        styleGroup.style.display = action === 'rewrite' ? 'block' : 'none';
        typeGroup.style.display = action === 'generate' ? 'block' : 'none';
        
        // Show prompt group for 'custom' AND 'generate'
        const showPrompt = (action === 'custom' || action === 'generate');
        promptGroup.style.display = showPrompt ? 'block' : 'none';
        if (showPrompt) loadPrompts(); // Reload prompts when shown
        
        // Adjust labels based on action
        if (action === 'generate') {
             document.querySelector('label[for="writeassist-prompt"]').textContent = '<?= $package->i18n('writeassist_optional_instructions') ?>';
             document.querySelector('label[for="writeassist-input"]').textContent = '<?= $package->i18n('writeassist_topic_keywords') ?>';
        } else if (action === 'custom') {
             document.querySelector('label[for="writeassist-prompt"]').textContent = '<?= $package->i18n('writeassist_prompt') ?>';
             document.querySelector('label[for="writeassist-input"]').textContent = '<?= $package->i18n('writeassist_input_text_optional') ?>';
        } else {
             document.querySelector('label[for="writeassist-input"]').textContent = '<?= $package->i18n('writeassist_input_text') ?>';
        }
    });
    
    // Trigger initial state
    actionSelect.dispatchEvent(new Event('change'));
    
    // Prompt Management
    promptSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option.value) {
            promptTextarea.value = option.dataset.content;
            deletePromptBtn.disabled = false;
        } else {
            promptTextarea.value = '';
            deletePromptBtn.disabled = true;
        }
    });
    
    savePromptBtn.addEventListener('click', function() {
        const content = promptTextarea.value.trim();
        if (!content) {
            alert('<?= $package->i18n('writeassist_prompt_empty') ?>');
            return;
        }
        
        const title = prompt('<?= $package->i18n('writeassist_prompt_name') ?>');
        if (title) {
            const formData = new FormData();
            formData.append('title', title);
            formData.append('content', content);
            
            fetch('./index.php?rex-api-call=writeassist_prompts&action=save', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadPrompts();
                    alert('<?= $package->i18n('writeassist_prompt_saved') ?>');
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
    });
    
    deletePromptBtn.addEventListener('click', function() {
        const id = promptSelect.value;
        if (id && confirm('<?= $package->i18n('writeassist_prompt_delete_confirm') ?>')) {
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('./index.php?rex-api-call=writeassist_prompts&action=delete', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    promptSelect.value = '';
                    promptTextarea.value = '';
                    deletePromptBtn.disabled = true;
                    loadPrompts();
                }
            });
        }
    });

    // Generate
    generateBtn.addEventListener('click', function() {
        const action = actionSelect.value;
        const text = inputEl.value.trim();
        const style = document.getElementById('writeassist-style').value;
        const type = document.getElementById('writeassist-type').value;
        const prompt = document.getElementById('writeassist-prompt').value;
        
        if (!text && action !== 'custom') {
            statusEl.textContent = '<?= $package->i18n('writeassist_enter_text') ?>';
            statusEl.className = 'text-warning';
            return;
        }
        
        if (action === 'custom' && !prompt) {
            statusEl.textContent = '<?= $package->i18n('writeassist_enter_prompt') ?>';
            statusEl.className = 'text-warning';
            return;
        }
        
        generateBtn.disabled = true;
        statusEl.textContent = '<?= $package->i18n('writeassist_generating') ?>';
        statusEl.className = 'text-info';
        
        const formData = new FormData();
        formData.append('action', action);
        formData.append('text', text);
        formData.append('style', style);
        formData.append('type', type);
        formData.append('prompt', prompt);
        
        fetch('./index.php?rex-api-call=writeassist_generate', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            generateBtn.disabled = false;
            
            if (data.success) {
                outputEl.value = data.text;
                statusEl.textContent = '✓';
                statusEl.className = 'text-success';
                
                if (data.usage) {
                    usageEl.textContent = 'Tokens: ' + data.usage.total_tokens;
                }
            } else {
                statusEl.textContent = data.error || 'Error';
                statusEl.className = 'text-danger';
            }
        })
        .catch(function(error) {
            generateBtn.disabled = false;
            statusEl.textContent = error.message || 'Network error';
            statusEl.className = 'text-danger';
        });
    });
    
    // Copy
    copyBtn.addEventListener('click', function() {
        navigator.clipboard.writeText(outputEl.value).then(function() {
            copyBtn.innerHTML = '<i class="fa fa-check"></i> <?= $package->i18n('writeassist_copied') ?>';
            setTimeout(function() {
                copyBtn.innerHTML = '<i class="fa fa-copy"></i> <?= $package->i18n('writeassist_copy') ?>';
            }, 2000);
        });
    });
    
    // Use as input
    useAsInputBtn.addEventListener('click', function() {
        inputEl.value = outputEl.value;
        outputEl.value = '';
        usageEl.textContent = '';
    });
})();
</script>
