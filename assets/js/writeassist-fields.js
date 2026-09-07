/**
 * WriteAssist - Field Widgets für einfache Textareas/Inputs ohne WYSIWYG-Editor
 *
 * Felder mit class="watext" bekommen einen KI-Button (Generieren/Umschreiben/
 * Zusammenfassen/Erweitern/Custom, ruft writeassist_generate auf).
 * Felder mit class="watranslate" bekommen einen Übersetzen-Button (DeepL/KI,
 * ruft writeassist_translate auf, respektiert die in den Einstellungen
 * gewählte Übersetzungs-Engine).
 *
 * Neu ins DOM eingefügte Felder (MBlock, MForm, dynamische Formulare) werden
 * per MutationObserver automatisch mit erkannt.
 */
(function () {
    'use strict';

    var LANGUAGES = [
        { code: 'DE', name: 'Deutsch' },
        { code: 'EN', name: 'English' },
        { code: 'FR', name: 'Français' },
        { code: 'ES', name: 'Español' },
        { code: 'IT', name: 'Italiano' },
        { code: 'NL', name: 'Nederlands' },
        { code: 'PL', name: 'Polski' },
        { code: 'PT', name: 'Português' },
        { code: 'RU', name: 'Русский' },
        { code: 'JA', name: '日本語' },
        { code: 'ZH', name: '中文' }
    ];

    var GENERATE_ACTIONS = [
        { value: 'rewrite', text: 'Umschreiben / Verbessern' },
        { value: 'summarize', text: 'Zusammenfassen' },
        { value: 'expand', text: 'Erweitern' },
        { value: 'generate', text: 'Neu generieren (Thema/Stichworte)' },
        { value: 'custom', text: 'Eigener Prompt' }
    ];

    var STYLES = [
        { value: 'professional', text: 'Professionell' },
        { value: 'casual', text: 'Locker' },
        { value: 'simple', text: 'Einfach' },
        { value: 'formal', text: 'Formell' },
        { value: 'creative', text: 'Kreativ' },
        { value: 'concise', text: 'Prägnant' }
    ];

    function closeAllPopovers() {
        document.querySelectorAll('.wa-field-popover').forEach(function (el) {
            el.remove();
        });
    }

    function setBusy(field, busy) {
        field.disabled = busy;
        var wrapper = field.closest('.wa-field-wrapper');
        var trigger = wrapper ? wrapper.querySelector('.wa-field-trigger') : null;
        if (trigger) {
            trigger.classList.toggle('wa-field-trigger-busy', busy);
        }
    }

    function createPopover(anchorBtn, contentEl) {
        closeAllPopovers();

        var popover = document.createElement('div');
        popover.className = 'wa-field-popover';
        popover.appendChild(contentEl);
        document.body.appendChild(popover);

        var rect = anchorBtn.getBoundingClientRect();
        var top = rect.bottom + window.scrollY + 4;
        var left = rect.left + window.scrollX;

        popover.style.top = top + 'px';
        popover.style.left = left + 'px';

        // Nach rechts aus dem Viewport rausragende Popover wieder reinschieben
        requestAnimationFrame(function () {
            var popRect = popover.getBoundingClientRect();
            if (popRect.right > window.innerWidth - 10) {
                popover.style.left = Math.max(10, window.innerWidth - popRect.width - 10) + 'px';
            }
        });

        function onOutsideClick(e) {
            if (!popover.contains(e.target) && e.target !== anchorBtn) {
                popover.remove();
                document.removeEventListener('mousedown', onOutsideClick);
            }
        }
        setTimeout(function () {
            document.addEventListener('mousedown', onOutsideClick);
        }, 0);

        return popover;
    }

    function getFieldValue(field) {
        return 'value' in field ? field.value : field.textContent;
    }

    // Setzt den Feldwert und beachtet ein vorhandenes maxlength-Attribut - das
    // Attribut begrenzt nur Tastatureingaben, ein programmatisches field.value=
    // wuerde es sonst stillschweigend ignorieren und einen zu langen Wert setzen.
    // Gibt true zurueck wenn gekuerzt werden musste, damit der Aufrufer warnen kann.
    function setFieldValue(field, value) {
        var maxLength = field.maxLength;
        var truncated = false;
        if (typeof maxLength === 'number' && maxLength >= 0 && value.length > maxLength) {
            value = value.slice(0, maxLength);
            truncated = true;
        }
        field.value = value;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        return truncated;
    }

    // === watext: KI-Generierung/Umschreiben ===

    function buildGeneratePopover(field) {
        var hasText = getFieldValue(field).trim() !== '';
        var wrap = document.createElement('div');
        wrap.className = 'wa-field-popover-body';

        var actionRow = document.createElement('div');
        actionRow.className = 'wa-field-row';
        var actionSelect = document.createElement('select');
        actionSelect.className = 'wa-field-select';
        GENERATE_ACTIONS.forEach(function (a) {
            var opt = document.createElement('option');
            opt.value = a.value;
            opt.textContent = a.text;
            actionSelect.appendChild(opt);
        });
        actionSelect.value = hasText ? 'rewrite' : 'generate';
        actionRow.appendChild(actionSelect);
        wrap.appendChild(actionRow);

        var styleRow = document.createElement('div');
        styleRow.className = 'wa-field-row';
        var styleSelect = document.createElement('select');
        styleSelect.className = 'wa-field-select';
        STYLES.forEach(function (s) {
            var opt = document.createElement('option');
            opt.value = s.value;
            opt.textContent = s.text;
            styleSelect.appendChild(opt);
        });
        styleRow.appendChild(styleSelect);
        wrap.appendChild(styleRow);

        var promptRow = document.createElement('div');
        promptRow.className = 'wa-field-row';
        var promptInput = document.createElement('textarea');
        promptInput.className = 'wa-field-textarea';
        promptInput.rows = 2;
        promptInput.placeholder = 'Thema, Stichworte oder eigener Prompt...';
        if (hasText) {
            promptInput.placeholder = 'Zusätzliche Anweisung (optional)...';
        }
        promptRow.appendChild(promptInput);
        wrap.appendChild(promptRow);

        function updatePromptVisibility() {
            var action = actionSelect.value;
            styleRow.style.display = action === 'rewrite' ? '' : 'none';
            promptRow.style.display = (action === 'generate' || action === 'custom' || action === 'rewrite') ? '' : 'none';
            promptInput.placeholder = action === 'generate'
                ? 'Thema / Stichworte...'
                : action === 'custom'
                    ? 'Eigener Prompt...'
                    : 'Zusätzliche Anweisung (optional)...';
        }
        actionSelect.addEventListener('change', updatePromptVisibility);
        updatePromptVisibility();

        var btnRow = document.createElement('div');
        btnRow.className = 'wa-field-row wa-field-actions';
        var runBtn = document.createElement('button');
        runBtn.type = 'button';
        runBtn.className = 'wa-field-run-btn';
        runBtn.textContent = 'Generieren';
        var statusEl = document.createElement('span');
        statusEl.className = 'wa-field-status';
        btnRow.appendChild(runBtn);
        btnRow.appendChild(statusEl);
        wrap.appendChild(btnRow);

        runBtn.addEventListener('click', function () {
            var action = actionSelect.value;
            var text = action === 'generate' ? promptInput.value.trim() : getFieldValue(field);
            var prompt = action === 'custom' ? promptInput.value.trim() : promptInput.value.trim();

            if (action === 'generate' && !text) {
                statusEl.textContent = 'Bitte Thema eingeben';
                statusEl.className = 'wa-field-status wa-field-status-warning';
                return;
            }
            if (action === 'custom' && !prompt) {
                statusEl.textContent = 'Bitte Prompt eingeben';
                statusEl.className = 'wa-field-status wa-field-status-warning';
                return;
            }
            if (action !== 'generate' && action !== 'custom' && !text) {
                statusEl.textContent = 'Feld ist leer';
                statusEl.className = 'wa-field-status wa-field-status-warning';
                return;
            }

            runBtn.disabled = true;
            setBusy(field, true);
            statusEl.textContent = 'Generiere...';
            statusEl.className = 'wa-field-status wa-field-status-info';

            var formData = new FormData();
            formData.append('action', action);
            formData.append('text', action === 'generate' ? text : getFieldValue(field));
            formData.append('style', styleSelect.value);
            formData.append('prompt', prompt);
            formData.append('type', 'paragraph');

            fetch('index.php?rex-api-call=writeassist_generate', {
                method: 'POST',
                body: formData
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    runBtn.disabled = false;
                    setBusy(field, false);
                    if (data.success && data.text) {
                        var wasTruncated = setFieldValue(field, data.text.trim());
                        if (wasTruncated) {
                            statusEl.textContent = 'Gekürzt auf ' + field.maxLength + ' Zeichen (Feldlimit)';
                            statusEl.className = 'wa-field-status wa-field-status-warning';
                        } else {
                            statusEl.textContent = 'Fertig';
                            statusEl.className = 'wa-field-status wa-field-status-success';
                            setTimeout(closeAllPopovers, 700);
                        }
                    } else {
                        statusEl.textContent = data.error || 'Fehler bei der Generierung';
                        statusEl.className = 'wa-field-status wa-field-status-error';
                    }
                })
                .catch(function (err) {
                    runBtn.disabled = false;
                    setBusy(field, false);
                    statusEl.textContent = 'Fehler: ' + err.message;
                    statusEl.className = 'wa-field-status wa-field-status-error';
                });
        });

        return wrap;
    }

    // === watranslate: DeepL/KI-Übersetzung ===

    function buildTranslatePopover(field) {
        var wrap = document.createElement('div');
        wrap.className = 'wa-field-popover-body';

        var listWrap = document.createElement('div');
        listWrap.className = 'wa-field-lang-list';
        LANGUAGES.forEach(function (lang) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'wa-field-lang-btn';
            btn.textContent = lang.name;
            btn.addEventListener('click', function () {
                runTranslate(lang.code);
            });
            listWrap.appendChild(btn);
        });
        wrap.appendChild(listWrap);

        var statusEl = document.createElement('span');
        statusEl.className = 'wa-field-status';
        wrap.appendChild(statusEl);

        function runTranslate(targetLang) {
            var text = getFieldValue(field);
            if (!text.trim()) {
                statusEl.textContent = 'Feld ist leer';
                statusEl.className = 'wa-field-status wa-field-status-warning';
                return;
            }

            setBusy(field, true);
            statusEl.textContent = 'Übersetze...';
            statusEl.className = 'wa-field-status wa-field-status-info';

            var formData = new FormData();
            formData.append('text', text);
            formData.append('target_lang', targetLang);
            formData.append('preserve_formatting', '0');

            fetch('index.php?rex-api-call=writeassist_translate', {
                method: 'POST',
                body: formData
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    setBusy(field, false);
                    if (data.success && data.translation) {
                        var wasTruncated = setFieldValue(field, data.translation.trim());
                        if (wasTruncated) {
                            statusEl.textContent = 'Übersetzt, gekürzt auf ' + field.maxLength + ' Zeichen (Feldlimit)';
                            statusEl.className = 'wa-field-status wa-field-status-warning';
                        } else {
                            statusEl.textContent = 'Übersetzt nach ' + targetLang;
                            statusEl.className = 'wa-field-status wa-field-status-success';
                            setTimeout(closeAllPopovers, 700);
                        }
                    } else {
                        statusEl.textContent = data.error || 'Übersetzungsfehler';
                        statusEl.className = 'wa-field-status wa-field-status-error';
                    }
                })
                .catch(function (err) {
                    setBusy(field, false);
                    statusEl.textContent = 'Fehler: ' + err.message;
                    statusEl.className = 'wa-field-status wa-field-status-error';
                });
        }

        return wrap;
    }

    // === Wrapper/Button-Injektion ===

    function getOrCreateWrapper(field) {
        var wrapper = field.closest('.wa-field-wrapper');
        if (wrapper) {
            return wrapper;
        }
        wrapper = document.createElement('div');
        wrapper.className = 'wa-field-wrapper';
        field.parentNode.insertBefore(wrapper, field);
        wrapper.appendChild(field);
        return wrapper;
    }

    function addTrigger(field, kind) {
        var wrapper = getOrCreateWrapper(field);
        if (wrapper.querySelector('.wa-field-trigger-' + kind)) {
            return;
        }

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'wa-field-trigger wa-field-trigger-' + kind;
        btn.title = kind === 'text' ? 'KI-Text-Assistent' : 'Übersetzen';
        btn.innerHTML = kind === 'text'
            ? '<i class="rex-icon fa-magic"></i>'
            : '<i class="rex-icon fa-language"></i>';
        wrapper.appendChild(btn);

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (document.querySelector('.wa-field-popover[data-for-trigger="' + kind + '-' + field.dataset.waFieldId + '"]')) {
                closeAllPopovers();
                return;
            }
            var content = kind === 'text' ? buildGeneratePopover(field) : buildTranslatePopover(field);
            var popover = createPopover(btn, content);
            popover.setAttribute('data-for-trigger', kind + '-' + field.dataset.waFieldId);
        });

        positionTriggers(wrapper);
    }

    function positionTriggers(wrapper) {
        var triggers = Array.from(wrapper.querySelectorAll('.wa-field-trigger'));
        triggers.forEach(function (btn, i) {
            btn.style.right = (6 + i * 26) + 'px';
        });
        var field = wrapper.querySelector('textarea, input');
        if (field) {
            var reserved = 6 + triggers.length * 26;
            field.style.paddingRight = reserved + 'px';
        }
    }

    var fieldIdCounter = 0;

    function scan(root) {
        root.querySelectorAll('textarea.watext, input.watext, textarea.watranslate, input.watranslate').forEach(function (field) {
            if (!field.dataset.waFieldId) {
                field.dataset.waFieldId = 'wa-field-' + (fieldIdCounter++);
            }
            if (field.classList.contains('watext')) {
                addTrigger(field, 'text');
            }
            if (field.classList.contains('watranslate')) {
                addTrigger(field, 'translate');
            }
        });
    }

    function init() {
        scan(document);

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) {
                        return;
                    }
                    if (node.matches && (node.matches('textarea.watext, input.watext, textarea.watranslate, input.watranslate'))) {
                        scan(node.parentNode || document);
                    } else if (node.querySelectorAll) {
                        scan(node);
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAllPopovers();
            }
        });
    }

    if (typeof $ !== 'undefined') {
        $(document).on('rex:ready', init);
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
