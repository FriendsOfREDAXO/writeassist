// WriteAssist JavaScript
(function() {
    'use strict';
    
    window.WriteAssist = {
        initialized: false,
        
        init: function() {
            // Use event delegation on document level to work with dynamically loaded content
            if (this.initialized) return;
            this.initialized = true;
            
            this.initEventDelegation();
            this.initPageInterface();
        },
        
        // Event delegation for all widget interactions
        initEventDelegation: function() {
            const self = this;
            
            document.addEventListener('click', function(e) {
                // Tab switching
                if (e.target.matches('.writeassist-tab')) {
                    const widget = e.target.closest('.writeassist-widget');
                    const tabName = e.target.dataset.tab;
                    
                    widget.querySelectorAll('.writeassist-tab').forEach(t => t.classList.remove('active'));
                    widget.querySelectorAll('.writeassist-tab-content').forEach(c => c.classList.remove('active'));
                    
                    e.target.classList.add('active');
                    widget.querySelector(`.writeassist-tab-content[data-tab="${tabName}"]`).classList.add('active');
                    return;
                }
                
                // Translate button
                if (e.target.matches('.writeassist-translate-btn')) {
                    const widget = e.target.closest('.writeassist-widget') || e.target.closest('.writeassist-page');
                    const sourceText = widget.querySelector('.writeassist-source').value;
                    const sourceLang = widget.querySelector('.writeassist-source-lang').value;
                    const targetLang = widget.querySelector('.writeassist-target-lang').value;
                    
                    self.translate(sourceText, targetLang, sourceLang, widget);
                    return;
                }
                
                // Copy translation button
                if (e.target.matches('.writeassist-copy-btn')) {
                    const widget = e.target.closest('.writeassist-widget') || e.target.closest('.writeassist-page');
                    const targetText = widget.querySelector('.writeassist-target');
                    self.copyToClipboard(targetText.value, widget.querySelector('.writeassist-translate-message'));
                    return;
                }
                
                // Improve button
                if (e.target.matches('.writeassist-improve-btn')) {
                    const widget = e.target.closest('.writeassist-widget') || e.target.closest('.writeassist-page');
                    const sourceText = widget.querySelector('.writeassist-improve-source').value;
                    const language = widget.querySelector('.writeassist-improve-lang').value;
                    const pickyMode = widget.querySelector('.writeassist-picky-mode')?.checked || false;
                    
                    self.improve(sourceText, language, pickyMode, widget);
                    return;
                }
                
                // Copy improved button
                if (e.target.matches('.writeassist-copy-improved-btn')) {
                    const widget = e.target.closest('.writeassist-widget') || e.target.closest('.writeassist-page');
                    const improvedText = widget.querySelector('.writeassist-improved');
                    self.copyToClipboard(improvedText.value, widget.querySelector('.writeassist-improve-message'));
                    return;
                }

                // Generate button
                if (e.target.matches('.writeassist-generate-btn')) {
                    const widget = e.target.closest('.writeassist-widget') || e.target.closest('.writeassist-page');
                    const topic = widget.querySelector('.writeassist-generate-topic').value;
                    const type = widget.querySelector('.writeassist-generate-type').value;
                    
                    self.generate(topic, type, widget);
                    return;
                }
                
                // Copy generated button
                if (e.target.matches('.writeassist-copy-generated-btn')) {
                    const widget = e.target.closest('.writeassist-widget') || e.target.closest('.writeassist-page');
                    const generatedText = widget.querySelector('.writeassist-generated');
                    self.copyToClipboard(generatedText.value, widget.querySelector('.writeassist-generate-message'));
                    return;
                }
            });
        },
        
        // Page interface (legacy support)
        initPageInterface: function() {
            const self = this;
            const translateBtn = document.getElementById('writeassist-translate-btn');
            if (translateBtn) {
                translateBtn.addEventListener('click', function() {
                    const sourceText = document.getElementById('writeassist-source-text').value;
                    const sourceLang = document.getElementById('writeassist-source-lang').value;
                    const targetLang = document.getElementById('writeassist-target-lang').value;
                    const page = document.querySelector('.writeassist-page') || document.querySelector('.writeassist-translator-page');
                    
                    if (page) {
                        self.translate(sourceText, targetLang, sourceLang, page);
                    }
                });
            }
        },
        
        // Translate via DeepL API
        translate: function(text, targetLang, sourceLang, widget) {
            const self = this;
            const messageEl = widget.querySelector('.writeassist-translate-message');
            const resultDiv = widget.querySelector('.writeassist-translate-result');
            const targetArea = widget.querySelector('.writeassist-target');
            
            if (!text.trim()) {
                self.showMessage(messageEl, 'Please enter text', 'warning');
                return;
            }
            
            self.showMessage(messageEl, 'Translating...', 'info');
            
            const apiUrl = './index.php?rex-api-call=writeassist_translate';
            const formData = new FormData();
            formData.append('text', text);
            formData.append('target_lang', targetLang);
            if (sourceLang) {
                formData.append('source_lang', sourceLang);
            }
            
            fetch(apiUrl, { method: 'POST', body: formData })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        targetArea.value = data.translation;
                        resultDiv.style.display = 'block';
                        self.showMessage(messageEl, '✓ Translated', 'success');
                    } else {
                        self.showMessage(messageEl, data.error || 'Translation error', 'error');
                    }
                })
                .catch(function(error) {
                    self.showMessage(messageEl, error.message || 'Network error', 'error');
                });
        },
        
        // Improve via LanguageTool API
        improve: function(text, language, pickyMode, widget) {
            const self = this;
            const messageEl = widget.querySelector('.writeassist-improve-message');
            const resultDiv = widget.querySelector('.writeassist-improve-result');
            const matchesDiv = widget.querySelector('.writeassist-matches');
            const improvedArea = widget.querySelector('.writeassist-improved');
            
            if (!text.trim()) {
                self.showMessage(messageEl, 'Please enter text', 'warning');
                return;
            }
            
            self.showMessage(messageEl, 'Checking...', 'info');
            
            const apiUrl = './index.php?rex-api-call=writeassist_improve';
            const formData = new FormData();
            formData.append('text', text);
            formData.append('language', language);
            formData.append('picky', pickyMode ? '1' : '0');
            formData.append('auto_correct', '1');
            
            fetch(apiUrl, { method: 'POST', body: formData })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        // Show matches
                        matchesDiv.innerHTML = '';
                        if (data.matches && data.matches.length > 0) {
                            data.matches.forEach(function(match) {
                                const matchEl = document.createElement('div');
                                matchEl.className = 'writeassist-match writeassist-match-' + (match.rule?.issueType || 'warning');
                                matchEl.innerHTML = 
                                    '<div class="writeassist-match-message">' + self.escapeHtml(match.message) + '</div>' +
                                    (match.replacements?.length ? '<div class="writeassist-match-replacements">→ ' + match.replacements.slice(0, 3).map(function(r) { return self.escapeHtml(r.value); }).join(', ') + '</div>' : '');
                                matchesDiv.appendChild(matchEl);
                            });
                            self.showMessage(messageEl, data.match_count + ' issue(s) found', 'warning');
                        } else {
                            self.showMessage(messageEl, 'No issues found ✓', 'success');
                        }
                        
                        // Show corrected text
                        if (data.corrected_text) {
                            improvedArea.value = data.corrected_text;
                        } else {
                            improvedArea.value = text;
                        }
                        
                        resultDiv.style.display = 'block';
                    } else {
                        self.showMessage(messageEl, data.error || 'Check error', 'error');
                    }
                })
                .catch(function(error) {
                    self.showMessage(messageEl, error.message || 'Network error', 'error');
                });
        },
        
        // Generate via Gemini/Provider API
        generate: function(text, type, widget) {
            const self = this;
            const messageEl = widget.querySelector('.writeassist-generate-message');
            const resultDiv = widget.querySelector('.writeassist-generate-result');
            const generatedArea = widget.querySelector('.writeassist-generated');
            
            if (!text.trim()) {
                self.showMessage(messageEl, 'Please enter topic', 'warning');
                return;
            }
            
            self.showMessage(messageEl, 'Generating...', 'info');
            
            const apiUrl = './index.php?rex-api-call=writeassist_generate';
            const formData = new FormData();
            formData.append('action', 'generate');
            formData.append('text', text); // Topic
            formData.append('type', type);
            
            fetch(apiUrl, { method: 'POST', body: formData })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        generatedArea.value = data.text;
                        resultDiv.style.display = 'block';
                        self.showMessage(messageEl, '✓ Generated', 'success');
                    } else {
                        self.showMessage(messageEl, data.error || 'Generation error', 'error');
                    }
                })
                .catch(function(error) {
                    self.showMessage(messageEl, error.message || 'Network error', 'error');
                });
        },
        
        // Copy to clipboard
        copyToClipboard: function(text, messageEl) {
            const self = this;
            navigator.clipboard.writeText(text).then(() => {
                self.showMessage(messageEl, 'Copied!', 'success');
            }).catch(() => {
                // Fallback
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                self.showMessage(messageEl, 'Copied!', 'success');
            });
        },
        
        // Show message
        showMessage: function(element, message, type) {
            if (!element) return;
            
            element.className = 'writeassist-message ' + type;
            element.textContent = message;
            element.style.display = 'block';
            
            // Auto-hide success and info messages after delay
            if (type === 'success') {
                setTimeout(() => { element.style.display = 'none'; }, 2000);
            } else if (type === 'info') {
                // Info messages (like "Checking...") are transient, don't auto-hide
                // They will be replaced by success/error/warning
            }
        },
        
        // Escape HTML
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // Auto-initialize - event delegation is set up once
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.WriteAssist.init());
    } else {
        window.WriteAssist.init();
    }
})();
