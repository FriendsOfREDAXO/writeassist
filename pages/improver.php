<?php

/**
 * WriteAssist - Text Improver Page (LanguageTool)
 */

$package = rex_addon::get('writeassist');

?>

<div class="writeassist-improver-page">
    
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><?= $package->i18n('writeassist_improver') ?></h3>
        </div>
        <div class="panel-body">
            <p class="text-muted"><?= $package->i18n('writeassist_improver_description') ?></p>
            
            <div class="writeassist-improver-interface">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label><?= $package->i18n('writeassist_language') ?></label>
                            <select class="form-control" id="writeassist-improve-lang">
                                <option value="auto"><?= $package->i18n('writeassist_auto_detect') ?></option>
                                <option value="de-DE">Deutsch (Deutschland)</option>
                                <option value="de-AT">Deutsch (Österreich)</option>
                                <option value="de-CH">Deutsch (Schweiz)</option>
                                <option value="en-US">English (US)</option>
                                <option value="en-GB">English (UK)</option>
                                <option value="fr">Français</option>
                                <option value="es">Español</option>
                                <option value="it">Italiano</option>
                                <option value="nl">Nederlands</option>
                                <option value="pl-PL">Polski</option>
                                <option value="pt-PT">Português (Portugal)</option>
                                <option value="pt-BR">Português (Brasil)</option>
                                <option value="ru-RU">Русский</option>
                                <option value="uk-UA">Українська</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><?= $package->i18n('writeassist_source_text') ?></label>
                            <textarea class="form-control" id="writeassist-improve-source" rows="12" placeholder="<?= $package->i18n('writeassist_enter_text') ?>"></textarea>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><?= $package->i18n('writeassist_corrected_text') ?></label>
                            <textarea class="form-control" id="writeassist-improve-result" rows="12" readonly></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="writeassist-improve-picky">
                            <?= $package->i18n('writeassist_picky_mode') ?>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="button" class="btn btn-primary" id="writeassist-improve-btn">
                        <i class="rex-icon fa-check-circle"></i> <?= $package->i18n('writeassist_check') ?>
                    </button>
                    <button type="button" class="btn btn-success" id="writeassist-improve-apply-btn" style="display:none;">
                        <i class="rex-icon fa-magic"></i> <?= $package->i18n('writeassist_apply_corrections') ?>
                    </button>
                    <button type="button" class="btn btn-default" id="writeassist-improve-copy-btn" style="display:none;">
                        <i class="rex-icon fa-copy"></i> <?= $package->i18n('writeassist_copy') ?>
                    </button>
                    <button type="button" class="btn btn-default" id="writeassist-improve-clear-btn">
                        <i class="rex-icon fa-trash"></i> <?= $package->i18n('writeassist_clear') ?>
                    </button>
                </div>
                
                <div id="writeassist-improve-message" style="display:none; margin-top:15px;"></div>
                
                <!-- Corrections List -->
                <div id="writeassist-corrections-container" style="display:none; margin-top:20px;">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <i class="rex-icon fa-list"></i> <?= $package->i18n('writeassist_corrections') ?>
                                <span id="writeassist-correction-count" class="badge"></span>
                            </h4>
                        </div>
                        <div class="panel-body">
                            <div id="writeassist-corrections-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<style nonce="<?= rex_response::getNonce() ?>">
/* Dark theme compatible styles using CSS variables */
.writeassist-correction-item {
    padding: 10px;
    margin-bottom: 10px;
    border-left: 3px solid var(--rex-color-info, #5bc0de);
    background: var(--rex-background-color-2, #f5f5f5);
    color: var(--rex-color-text, #333);
    border-radius: 4px;
}
.writeassist-correction-item.error {
    border-left-color: var(--rex-color-danger, #d9534f);
}
.writeassist-correction-item.warning {
    border-left-color: var(--rex-color-warning, #f0ad4e);
}
.writeassist-correction-item.style {
    border-left-color: var(--rex-color-success, #5cb85c);
}
.writeassist-correction-context {
    font-family: monospace;
    background: var(--rex-background-color-3, rgba(0,0,0,0.05));
    color: var(--rex-color-text, #333);
    padding: 5px 10px;
    margin: 5px 0;
    border-radius: 3px;
}
.writeassist-correction-context .highlight {
    background: var(--rex-color-danger-light, rgba(217, 83, 79, 0.3));
    color: var(--rex-color-danger, #d9534f);
    text-decoration: line-through;
    padding: 1px 3px;
    border-radius: 2px;
}
.writeassist-correction-replacement {
    color: var(--rex-color-success, #5cb85c);
    font-weight: bold;
}

/* Panel styling for dark theme */
.writeassist-improver-page .panel {
    background: var(--rex-background-color, #fff);
    border-color: var(--rex-border-color, #ddd);
}
.writeassist-improver-page .panel-heading {
    background: var(--rex-background-color-2, #f5f5f5);
    border-color: var(--rex-border-color, #ddd);
}
.writeassist-improver-page .panel-body {
    color: var(--rex-color-text, #333);
}

/* ---- Dark Mode (REDAXO be_style) ---- */
body.rex-theme-dark .writeassist-improver-page .panel {
    background: #1e2a3a;
    border-color: #2d3e50;
}
body.rex-theme-dark .writeassist-improver-page .panel-heading {
    background: #253344;
    border-color: #2d3e50;
}
body.rex-theme-dark .writeassist-improver-page .panel-body,
body.rex-theme-dark .writeassist-improver-page .panel-body .checkbox label,
body.rex-theme-dark .writeassist-correction-item {
    color: #cfe0ee;
}
body.rex-theme-dark .writeassist-correction-item {
    background: #253344;
}
body.rex-theme-dark .writeassist-correction-context {
    background: rgba(255, 255, 255, 0.06);
    color: #cfe0ee;
}
@media (prefers-color-scheme: dark) {
    body.rex-has-theme:not(.rex-theme-light) .writeassist-improver-page .panel {
        background: #1e2a3a;
        border-color: #2d3e50;
    }
    body.rex-has-theme:not(.rex-theme-light) .writeassist-improver-page .panel-heading {
        background: #253344;
        border-color: #2d3e50;
    }
    body.rex-has-theme:not(.rex-theme-light) .writeassist-improver-page .panel-body,
    body.rex-has-theme:not(.rex-theme-light) .writeassist-improver-page .panel-body .checkbox label,
    body.rex-has-theme:not(.rex-theme-light) .writeassist-correction-item {
        color: #cfe0ee;
    }
    body.rex-has-theme:not(.rex-theme-light) .writeassist-correction-item {
        background: #253344;
    }
    body.rex-has-theme:not(.rex-theme-light) .writeassist-correction-context {
        background: rgba(255, 255, 255, 0.06);
        color: #cfe0ee;
    }
}
</style>

<script nonce="<?= rex_response::getNonce() ?>">
document.addEventListener('DOMContentLoaded', function() {
    const improveBtn = document.getElementById('writeassist-improve-btn');
    const applyBtn = document.getElementById('writeassist-improve-apply-btn');
    const copyBtn = document.getElementById('writeassist-improve-copy-btn');
    const clearBtn = document.getElementById('writeassist-improve-clear-btn');
    const sourceText = document.getElementById('writeassist-improve-source');
    const resultText = document.getElementById('writeassist-improve-result');
    const language = document.getElementById('writeassist-improve-lang');
    const pickyMode = document.getElementById('writeassist-improve-picky');
    const message = document.getElementById('writeassist-improve-message');
    const correctionsContainer = document.getElementById('writeassist-corrections-container');
    const correctionsList = document.getElementById('writeassist-corrections-list');
    const correctionCount = document.getElementById('writeassist-correction-count');
    
    let currentMatches = [];
    
    improveBtn?.addEventListener('click', async function() {
        const text = sourceText.value.trim();
        if (!text) return;
        
        improveBtn.disabled = true;
        improveBtn.innerHTML = '<i class="rex-icon fa-spinner fa-spin"></i> ...';
        
        try {
            const response = await fetch('./index.php?rex-api-call=writeassist_improve', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    text: text,
                    language: language.value,
                    picky: pickyMode.checked ? '1' : '0'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                currentMatches = data.matches || [];
                correctionCount.textContent = currentMatches.length;
                
                if (currentMatches.length === 0) {
                    message.className = 'alert alert-success';
                    message.innerHTML = '<i class="rex-icon fa-check"></i> <?= $package->i18n('writeassist_no_errors') ?>';
                    message.style.display = 'block';
                    correctionsContainer.style.display = 'none';
                    applyBtn.style.display = 'none';
                    resultText.value = text;
                    copyBtn.style.display = 'inline-block';
                } else {
                    message.className = 'alert alert-warning';
                    message.innerHTML = currentMatches.length + ' <?= $package->i18n('writeassist_issues_found') ?>';
                    message.style.display = 'block';
                    
                    // Display corrections
                    renderCorrections(currentMatches, text);
                    correctionsContainer.style.display = 'block';
                    applyBtn.style.display = 'inline-block';
                }
            } else {
                message.className = 'alert alert-danger';
                message.textContent = data.error || 'Check failed';
                message.style.display = 'block';
            }
        } catch (e) {
            message.className = 'alert alert-danger';
            message.textContent = e.message;
            message.style.display = 'block';
        }
        
        improveBtn.disabled = false;
        improveBtn.innerHTML = '<i class="rex-icon fa-check-circle"></i> <?= $package->i18n('writeassist_check') ?>';
    });
    
    applyBtn?.addEventListener('click', async function() {
        const text = sourceText.value;
        
        try {
            const response = await fetch('./index.php?rex-api-call=writeassist_improve', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    text: text,
                    language: language.value,
                    picky: pickyMode.checked ? '1' : '0',
                    auto_correct: '1'
                })
            });
            
            const data = await response.json();
            
            if (data.success && data.corrected_text) {
                resultText.value = data.corrected_text;
                copyBtn.style.display = 'inline-block';
                
                message.className = 'alert alert-success';
                message.innerHTML = '<i class="rex-icon fa-check"></i> <?= $package->i18n('writeassist_corrections_applied') ?>';
                message.style.display = 'block';
            }
        } catch (e) {
            message.className = 'alert alert-danger';
            message.textContent = e.message;
            message.style.display = 'block';
        }
    });
    
    copyBtn?.addEventListener('click', function() {
        navigator.clipboard.writeText(resultText.value);
        copyBtn.innerHTML = '<i class="rex-icon fa-check"></i> Copied!';
        setTimeout(() => {
            copyBtn.innerHTML = '<i class="rex-icon fa-copy"></i> <?= $package->i18n('writeassist_copy') ?>';
        }, 2000);
    });
    
    clearBtn?.addEventListener('click', function() {
        sourceText.value = '';
        resultText.value = '';
        copyBtn.style.display = 'none';
        applyBtn.style.display = 'none';
        message.style.display = 'none';
        correctionsContainer.style.display = 'none';
        currentMatches = [];
    });
    
    function renderCorrections(matches, text) {
        let html = '';
        
        matches.forEach((match, index) => {
            const type = match.rule?.issueType || 'other';
            const typeClass = type.includes('grammar') ? 'error' : 
                             type.includes('spelling') ? 'error' : 
                             type.includes('style') ? 'style' : 'warning';
            
            const contextBefore = text.substring(Math.max(0, match.offset - 20), match.offset);
            const errorText = text.substring(match.offset, match.offset + match.length);
            const contextAfter = text.substring(match.offset + match.length, match.offset + match.length + 20);
            
            const replacements = (match.replacements || []).slice(0, 3).map(r => r.value).join(', ');
            
            html += `
                <div class="writeassist-correction-item ${typeClass}">
                    <strong>${match.message}</strong>
                    <div class="writeassist-correction-context">
                        ...${escapeHtml(contextBefore)}<span class="highlight">${escapeHtml(errorText)}</span>${escapeHtml(contextAfter)}...
                    </div>
                    ${replacements ? `<span class="writeassist-correction-replacement">→ ${escapeHtml(replacements)}</span>` : ''}
                </div>
            `;
        });
        
        correctionsList.innerHTML = html;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
