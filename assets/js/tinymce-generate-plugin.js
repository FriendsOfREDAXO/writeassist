/**
 * WriteAssist – KI-Textwerkzeuge für TinyMCE
 *
 * Toolbar-Menü „writeassist_generate“ (Zauberstab) mit schnellen Aktionen für den markierten
 * Text (oder den ganzen Inhalt): Verbessern, Kürzen, Einfacher, Ausführlicher, Zusammenfassen,
 * Teaser, Überschriften, Weiterschreiben, Umschreiben im Tonfall, eigene Anweisung, gespeicherte
 * Prompts und „Neuen Text schreiben“. Das Ergebnis erscheint erst in einer Vorschau und wird per
 * „Ersetzen“ oder „Darunter einfügen“ übernommen – mit Rückgängig. Formatierung (Absätze,
 * Listen, Links, Hervorhebungen) bleibt erhalten, weil HTML gesendet und zurückgegeben wird.
 */
(function () {
    'use strict';

    var API = './index.php?rex-api-call=writeassist_generate';
    var PROMPTS_API = './index.php?rex-api-call=writeassist_prompts&action=list';

    var ACTIONS = [
        { id: 'improve', text: 'Verbessern (Rechtschreibung & Stil)', icon: 'spell-check' },
        { id: 'shorten', text: 'Kürzen', icon: 'unselected' },
        { id: 'simplify', text: 'Einfacher formulieren', icon: 'accessibility-check' },
        { id: 'expand', text: 'Ausführlicher', icon: 'paragraph' },
        { id: 'summarize', text: 'Zusammenfassen', icon: 'line' },
        { id: 'teaser', text: 'Teaser schreiben', icon: 'quote' },
        { id: 'headline', text: 'Überschriften vorschlagen', icon: 'format' },
        { id: 'continue', text: 'Weiterschreiben', icon: 'chevron-right' }
    ];
    var STYLES = [
        { value: 'professional', text: 'Sachlich' },
        { value: 'casual', text: 'Locker' },
        { value: 'simple', text: 'Einfach' },
        { value: 'formal', text: 'Formell' },
        { value: 'creative', text: 'Lebendig' },
        { value: 'concise', text: 'Prägnant' }
    ];
    var LABELS = {};
    ACTIONS.forEach(function (a) { LABELS[a.id] = a.text; });
    LABELS.rewrite = 'Umschreiben';
    LABELS.custom = 'Eigene Anweisung';
    LABELS.generate = 'Neuer Text';

    var STYLE_CSS_TEXT = ''
        + '.tox .wa-box{font:14px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;color:#222}'
        + '.tox .wa-head{display:flex;align-items:center;gap:8px;margin:0 0 10px;font-size:12px;font-weight:600;letter-spacing:.03em;text-transform:uppercase;color:#5b6570}'
        + '.tox .wa-chip{display:inline-block;padding:2px 8px;border-radius:999px;background:var(--wa-accent-light,#e8f1f8);color:var(--wa-accent-dark,#185d8c);text-transform:none;letter-spacing:0;font-weight:600}'
        + '.tox .wa-result{max-height:46vh;overflow:auto;padding:12px 14px;border:1px solid #d9e0dc;border-left:4px solid var(--wa-accent,#207ab7);border-radius:4px;background:#fff}'
        + '.tox .wa-result>:first-child{margin-top:0}.tox .wa-result>:last-child{margin-bottom:0}'
        + '.tox .wa-result a{color:var(--wa-accent-dark,#185d8c)}'
        + '.tox .wa-result p,.tox .wa-result ul,.tox .wa-result ol,.tox .wa-result h2,.tox .wa-result h3{margin:0 0 .7em}.tox .wa-result ul,.tox .wa-result ol{padding-left:1.4em}.tox .wa-result ul{list-style:disc}.tox .wa-result ol{list-style:decimal}.tox .wa-result strong,.tox .wa-result b{font-weight:700}.tox .wa-result em{font-style:italic}.tox .wa-result h2,.tox .wa-result h3{font-weight:700;font-size:1.1em}'
        + '.tox .wa-orig{margin-top:10px}.tox .wa-orig summary{cursor:pointer;color:#5b6570;font-size:12px}'
        + '.tox .wa-orig div{margin-top:6px;padding:8px 12px;border-radius:4px;background:#f4f6f5;color:#555;max-height:22vh;overflow:auto}'
        + '.tox .wa-meta{margin-top:8px;font-size:12px;color:#6b746e}'
        + '.tox .wa-loading{display:flex;flex-direction:column;align-items:center;gap:14px;padding:28px 8px;color:#5b6570}'
        + '.tox .wa-dots{display:flex;gap:6px}.tox .wa-dots span{width:9px;height:9px;border-radius:50%;background:var(--wa-accent,#207ab7);animation:wa-b 1s infinite ease-in-out}'
        + '.tox .wa-dots span:nth-child(2){animation-delay:.15s}.tox .wa-dots span:nth-child(3){animation-delay:.3s}'
        + '@keyframes wa-b{0%,80%,100%{opacity:.25;transform:translateY(0)}40%{opacity:1;transform:translateY(-4px)}}'
        + '@media (prefers-reduced-motion:reduce){.tox .wa-dots span{animation:none;opacity:.7}}'
        + '.tox .wa-error{padding:12px 14px;border-radius:4px;background:#fdecea;color:#8a1c12}';
    // TinyMCE entfernt <style> aus Dialog-Inhalten - die Stile kommen einmal in den Seitenkopf
    var STYLE_CSS = '';
    function injectStyles() {
        if (document.getElementById('writeassist-tinymce-ui')) {
            return;
        }
        var el = document.createElement('style');
        el.id = 'writeassist-tinymce-ui';
        el.textContent = STYLE_CSS_TEXT;
        document.head.appendChild(el);
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function stripTags(html) {
        var div = document.createElement('div');
        div.innerHTML = html;
        return (div.textContent || '').trim();
    }

    var setup = function (editor) {
        var savedPrompts = null;
        injectStyles();

        function loadPrompts() {
            if (savedPrompts !== null) {
                return Promise.resolve(savedPrompts);
            }
            return fetch(PROMPTS_API, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) { savedPrompts = (d && d.success && Array.isArray(d.prompts)) ? d.prompts : []; return savedPrompts; })
                .catch(function () { savedPrompts = []; return savedPrompts; });
        }

        /** Quelle: Markierung (mit Lesezeichen zum späteren Ersetzen) oder ganzer Inhalt */
        function captureSource() {
            var hasSelection = !editor.selection.isCollapsed() && stripTags(editor.selection.getContent()) !== '';
            return {
                hasSelection: hasSelection,
                bookmark: hasSelection ? editor.selection.getBookmark(2, true) : null,
                html: hasSelection ? editor.selection.getContent() : editor.getContent()
            };
        }

        function request(params) {
            var form = new FormData();
            Object.keys(params).forEach(function (k) { form.append(k, params[k] == null ? '' : params[k]); });
            form.append('format', 'html');
            return fetch(API, { method: 'POST', body: form, credentials: 'same-origin' })
                .then(function (r) { return r.json().catch(function () { return { success: false, error: 'Ungültige Antwort (HTTP ' + r.status + ')' }; }); });
        }

        function loadingBody(label) {
            return {
                type: 'panel',
                items: [{
                    type: 'htmlpanel',
                    html: STYLE_CSS + '<div class="wa-box"><div class="wa-loading"><div class="wa-dots" aria-hidden="true"><span></span><span></span><span></span></div>'
                        + '<div role="status">KI schreibt: ' + escapeHtml(label) + ' …</div></div></div>'
                }]
            };
        }

        /** Ablauf: Ladeanzeige → Vorschau mit Übernehmen / Darunter / Nochmal */
        function run(params, source, label) {
            var dialog = editor.windowManager.open({
                title: 'KI-Textwerkzeuge',
                size: 'medium',
                body: loadingBody(label),
                buttons: [{ type: 'cancel', text: 'Abbrechen' }]
            });
            var started = Date.now();

            var go = function () {
                request(params).then(function (result) {
                    if (!result || !result.success || !result.text) {
                        showError(result && result.error ? result.error : 'Die KI hat keinen Text geliefert.');
                        return;
                    }
                    showResult(result.text, ((Date.now() - started) / 1000).toFixed(1));
                }).catch(function (e) {
                    showError('Verbindung fehlgeschlagen: ' + e);
                });
            };

            function showError(message) {
                dialog.redial({
                    title: 'KI-Textwerkzeuge',
                    size: 'medium',
                    body: { type: 'panel', items: [{ type: 'htmlpanel', html: STYLE_CSS + '<div class="wa-box"><div class="wa-error">' + escapeHtml(message) + '</div></div>' }] },
                    buttons: [
                        { type: 'cancel', text: 'Schließen' },
                        { type: 'custom', name: 'retry', text: 'Erneut versuchen', primary: true }
                    ],
                    onAction: function (api, details) {
                        if (details.name === 'retry') {
                            api.redial({ title: 'KI-Textwerkzeuge', size: 'medium', body: loadingBody(label), buttons: [{ type: 'cancel', text: 'Abbrechen' }] });
                            started = Date.now();
                            go();
                        }
                    }
                });
            }

            function showResult(html, seconds) {
                var original = source.html;
                var replaceText = source.hasSelection ? 'Markierung ersetzen' : 'Inhalt ersetzen';
                var insertOnly = params.action === 'generate' || params.action === 'continue' || params.action === 'headline' || params.action === 'teaser';
                dialog.redial({
                    title: 'KI-Textwerkzeuge',
                    size: 'medium',
                    body: {
                        type: 'panel',
                        items: [{
                            type: 'htmlpanel',
                            html: STYLE_CSS + '<div class="wa-box">'
                                + '<div class="wa-head">Vorschlag <span class="wa-chip">' + escapeHtml(label) + '</span></div>'
                                + '<div class="wa-result">' + html + '</div>'
                                + (stripTags(original) !== '' && params.action !== 'generate' ? '<details class="wa-orig"><summary>Ausgangstext anzeigen</summary><div>' + original + '</div></details>' : '')
                                + '<div class="wa-meta">KI-Vorschlag in ' + seconds + ' s – bitte vor dem Übernehmen prüfen.</div>'
                                + '</div>'
                        }]
                    },
                    buttons: [
                        { type: 'custom', name: 'again', text: 'Neu erzeugen', align: 'start' },
                        { type: 'cancel', text: 'Verwerfen' },
                        { type: 'custom', name: 'below', text: insertOnly ? 'Einfügen' : 'Darunter einfügen', primary: insertOnly },
                        insertOnly ? null : { type: 'custom', name: 'replace', text: replaceText, primary: true }
                    ].filter(Boolean),
                    onAction: function (api, details) {
                        if (details.name === 'again') {
                            api.redial({ title: 'KI-Textwerkzeuge', size: 'medium', body: loadingBody(label), buttons: [{ type: 'cancel', text: 'Abbrechen' }] });
                            started = Date.now();
                            go();
                            return;
                        }
                        api.close();
                        apply(details.name, html);
                    }
                });
            }

            function apply(mode, html) {
                editor.focus();
                editor.undoManager.transact(function () {
                    if (source.hasSelection && source.bookmark) {
                        editor.selection.moveToBookmark(source.bookmark);
                    }
                    if (source.hasSelection) {
                        html = fitToSelection(html, source);
                    }
                    if (mode === 'replace') {
                        if (source.hasSelection) {
                            editor.selection.setContent(html);
                        } else {
                            editor.setContent(html);
                        }
                    } else {
                        if (source.hasSelection) {
                            editor.selection.collapse(false);
                        } else if (params.action !== 'generate') {
                            editor.selection.select(editor.getBody(), true);
                            editor.selection.collapse(false);
                        }
                        editor.insertContent(html);
                    }
                });
                editor.nodeChanged();
                editor.notificationManager.open({ text: 'KI-Text übernommen – mit Rückgängig (Strg/⌘+Z) zurücknehmbar.', type: 'success', timeout: 2500 });
            }

            go();
        }

        /**
         * Ergebnis passend zur Markierung einsetzen: Liegt die Markierung innerhalb EINES Absatzes
         * (kein Block im markierten HTML), wird ein einzelner <p> zu Inline-Text; mehrere Blöcke
         * ersetzen den ganzen umgebenden Absatz statt darin verschachtelt zu landen (<p><p>).
         */
        function fitToSelection(html, source) {
            if (/<(p|ul|ol|h[1-6]|div|blockquote|table)\b/i.test(source.html)) {
                return html;
            }
            var single = html.match(/^\s*<p>([\s\S]*?)<\/p>\s*$/i);
            if (single && !/<\/?(p|ul|ol|h[1-6]|div|blockquote|table)\b/i.test(single[1])) {
                return single[1];
            }
            if (/<(p|ul|ol|h[1-6]|div|blockquote|table)\b/i.test(html)) {
                var dom = editor.dom;
                var start = dom.getParent(editor.selection.getStart(), dom.isBlock);
                var end = dom.getParent(editor.selection.getEnd(), dom.isBlock);
                if (start && start === end && start !== editor.getBody()) {
                    editor.selection.select(start);
                }
            }
            return html;
        }

        function quick(action) {
            var source = captureSource();
            if (stripTags(source.html) === '') {
                editor.notificationManager.open({ text: 'Bitte zuerst Text markieren oder schreiben – oder „Neuen Text schreiben“ wählen.', type: 'info', timeout: 3500 });
                return;
            }
            run({ action: action, text: source.html }, source, LABELS[action]);
        }

        function rewriteDialog() {
            var source = captureSource();
            if (stripTags(source.html) === '') {
                quick('rewrite');
                return;
            }
            editor.windowManager.open({
                title: 'Umschreiben',
                body: { type: 'panel', items: [
                    { type: 'selectbox', name: 'style', label: 'Tonfall', items: STYLES },
                    { type: 'textarea', name: 'prompt', label: 'Zusätzliche Hinweise (optional)', placeholder: 'z. B. „für Eltern von Jugendspielern“, „mit Aufzählung“' }
                ] },
                initialData: { style: 'professional', prompt: '' },
                buttons: [{ type: 'cancel', text: 'Abbrechen' }, { type: 'submit', text: 'Umschreiben', primary: true }],
                onSubmit: function (api) {
                    var d = api.getData();
                    api.close();
                    run({ action: 'rewrite', text: source.html, style: d.style, prompt: d.prompt }, source, 'Umschreiben – ' + (STYLES.filter(function (s) { return s.value === d.style; })[0] || {}).text);
                }
            });
        }

        function customDialog(preset) {
            var source = captureSource();
            editor.windowManager.open({
                title: 'Eigene Anweisung',
                body: { type: 'panel', items: [
                    { type: 'textarea', name: 'prompt', label: source.hasSelection ? 'Was soll mit dem markierten Text passieren?' : 'Was soll mit dem Text passieren?', placeholder: 'z. B. „Formuliere als Einladung an alle Vereine“' },
                    { type: 'htmlpanel', html: '<p style="margin:6px 0 0;color:#6b746e;font-size:12px">' + (source.hasSelection ? 'Bezieht sich auf die Markierung.' : (stripTags(source.html) !== '' ? 'Bezieht sich auf den ganzen Inhalt.' : 'Der Editor ist leer – die KI schreibt neu.')) + '</p>' }
                ] },
                initialData: { prompt: preset || '' },
                buttons: [{ type: 'cancel', text: 'Abbrechen' }, { type: 'submit', text: 'Ausführen', primary: true }],
                onSubmit: function (api) {
                    var d = api.getData();
                    if (!d.prompt || !d.prompt.trim()) {
                        return;
                    }
                    api.close();
                    var empty = stripTags(source.html) === '';
                    run({ action: 'custom', text: empty ? '' : source.html, prompt: d.prompt }, source, LABELS.custom);
                }
            });
        }

        function generateDialog() {
            var source = captureSource();
            editor.windowManager.open({
                title: 'Neuen Text schreiben',
                body: { type: 'panel', items: [
                    { type: 'textarea', name: 'text', label: 'Thema / Stichpunkte', placeholder: 'z. B. „Anmeldung Hallenkreismeisterschaft U13, Meldeschluss 15.11., Formular im Download-Bereich“' },
                    { type: 'selectbox', name: 'type', label: 'Art', items: [
                        { value: 'paragraph', text: 'Absatz' },
                        { value: 'intro', text: 'Einleitung' },
                        { value: 'bullet_points', text: 'Stichpunkte' },
                        { value: 'headline', text: 'Überschriften-Vorschläge' }
                    ] },
                    { type: 'selectbox', name: 'style', label: 'Tonfall', items: STYLES },
                    { type: 'textarea', name: 'prompt', label: 'Zusätzliche Hinweise (optional)' }
                ] },
                initialData: { text: source.hasSelection ? stripTags(source.html) : '', type: 'paragraph', style: 'professional', prompt: '' },
                buttons: [{ type: 'cancel', text: 'Abbrechen' }, { type: 'submit', text: 'Schreiben', primary: true }],
                onSubmit: function (api) {
                    var d = api.getData();
                    if (!d.text || !d.text.trim()) {
                        return;
                    }
                    api.close();
                    var tone = (STYLES.filter(function (s) { return s.value === d.style; })[0] || {}).text;
                    run({ action: 'generate', text: d.text, type: d.type, prompt: (d.prompt ? d.prompt + '\n' : '') + 'Tonfall: ' + tone + '.' }, { hasSelection: source.hasSelection, bookmark: source.bookmark, html: '' }, LABELS.generate);
                }
            });
        }

        function menuItems(callback) {
            var items = ACTIONS.map(function (a) {
                return { type: 'menuitem', text: a.text, icon: a.icon, onAction: function () { quick(a.id); } };
            });
            items.push({ type: 'menuitem', text: 'Umschreiben im Tonfall …', icon: 'edit-block', onAction: rewriteDialog });
            items.push({ type: 'separator' });
            items.push({ type: 'menuitem', text: 'Eigene Anweisung …', icon: 'comment', onAction: function () { customDialog(''); } });
            items.push({ type: 'menuitem', text: 'Neuen Text schreiben …', icon: 'new-document', onAction: generateDialog });
            loadPrompts().then(function (prompts) {
                if (prompts.length > 0) {
                    items.push({
                        type: 'nestedmenuitem', text: 'Gespeicherte Prompts', icon: 'bookmark',
                        getSubmenuItems: function () {
                            return prompts.map(function (p) {
                                return { type: 'menuitem', text: p.title, onAction: function () { customDialog(p.content); } };
                            });
                        }
                    });
                }
                callback(items);
            });
        }

        editor.ui.registry.addIcon('wa-magic', '<svg width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M7.5 5.6 10 7 8.6 4.5 10 2 7.5 3.4 5 2l1.4 2.5L5 7zm12 9.8L17 14l1.4 2.5L17 19l2.5-1.4L22 19l-1.4-2.5L22 14zM22 2l-2.5 1.4L17 2l1.4 2.5L17 7l2.5-1.4L22 7l-1.4-2.5zm-7.63 5.29a1 1 0 0 0-1.41 0L1.29 18.96a1 1 0 0 0 0 1.41l2.34 2.34a1 1 0 0 0 1.41 0L16.7 11.05a1 1 0 0 0 0-1.41l-2.33-2.35zm-1.03 5.49-2.12-2.12 2.44-2.44 2.12 2.12-2.44 2.44z"/></svg>');

        // Toolbar: Menü-Knopf (Name bleibt „writeassist_generate“ – bestehende Profile funktionieren weiter)
        editor.ui.registry.addMenuButton('writeassist_generate', {
            icon: 'wa-magic',
            text: 'KI',
            tooltip: 'KI-Textwerkzeuge',
            fetch: menuItems
        });

        // Menüleiste/Kontextmenü
        editor.ui.registry.addNestedMenuItem('writeassist_generate_menu', {
            text: 'KI-Textwerkzeuge',
            icon: 'wa-magic',
            getSubmenuItems: function () {
                return ACTIONS.map(function (a) {
                    return { type: 'menuitem', text: a.text, onAction: function () { quick(a.id); } };
                }).concat([
                    { type: 'menuitem', text: 'Umschreiben im Tonfall …', onAction: rewriteDialog },
                    { type: 'menuitem', text: 'Eigene Anweisung …', onAction: function () { customDialog(''); } }
                ]);
            }
        });
        editor.ui.registry.addMenuItem('writeassist_rewrite', {
            text: 'KI: Umschreiben …',
            icon: 'wa-magic',
            onAction: rewriteDialog
        });
        // Kontextmenü (Rechtsklick) bei markiertem Text – im Profil unter „contextmenu“ als „writeassist_context“ eintragen
        editor.ui.registry.addContextMenu('writeassist_context', {
            update: function () {
                return editor.selection.isCollapsed() ? '' : 'writeassist_generate_menu';
            }
        });
        // Tastenkürzel: Strg/⌘+Shift+K öffnet „Eigene Anweisung“
        editor.addShortcut('meta+shift+k', 'KI: Eigene Anweisung', function () { customDialog(''); });
    };

    var meta = {
        name: 'WriteAssist KI-Textwerkzeuge',
        url: 'https://github.com/FriendsOfREDAXO/writeassist'
    };

    if (typeof tinymce !== 'undefined') {
        tinymce.PluginManager.add('writeassist_generate', function (editor) {
            setup(editor);
            return { getMetadata: function () { return meta; } };
        });
    }
})();
