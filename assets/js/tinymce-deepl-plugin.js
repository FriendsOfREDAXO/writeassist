/**
 * WriteAssist Translation Plugin for TinyMCE
 * 
 * Adds a translate button that translates selected text via DeepL API
 */
(function() {
    'use strict';

    const setup = function(editor, url) {
        
        // Available target languages
        const languages = [
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

        // Translate the selected text
        const translateSelection = function(targetLang) {
            // Get content as HTML to preserve formatting (bold, italic, links, etc.)
            const selectedHtml = editor.selection.getContent({ format: 'html' });
            
            if (!selectedHtml || selectedHtml.trim() === '') {
                editor.notificationManager.open({
                    text: 'Bitte zuerst Text markieren',
                    type: 'warning',
                    timeout: 3000
                });
                return;
            }

            // Show loading notification
            const loadingNotification = editor.notificationManager.open({
                text: 'Übersetze...',
                type: 'info',
                closeButton: false
            });

            // Call WriteAssist API with HTML content
            const formData = new FormData();
            formData.append('text', selectedHtml);
            formData.append('target_lang', targetLang);
            formData.append('preserve_formatting', '1');

            fetch('./index.php?rex-api-call=writeassist_translate', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                loadingNotification.close();
                
                if (data.success && data.translation) {
                    // Replace selection with translation
                    editor.selection.setContent(data.translation);
                    
                    editor.notificationManager.open({
                        text: 'Übersetzt nach ' + targetLang,
                        type: 'success',
                        timeout: 2000
                    });
                } else {
                    editor.notificationManager.open({
                        text: data.error || 'Übersetzungsfehler',
                        type: 'error',
                        timeout: 5000
                    });
                }
            })
            .catch(function(error) {
                loadingNotification.close();
                editor.notificationManager.open({
                    text: 'API-Fehler: ' + error.message,
                    type: 'error',
                    timeout: 5000
                });
            });
        };

        // Register menu button with language submenu
        editor.ui.registry.addMenuButton('writeassist_translate', {
            icon: 'translate',
            tooltip: 'Übersetzen (DeepL / KI)',
            fetch: function(callback) {
                const items = languages.map(function(lang) {
                    return {
                        type: 'menuitem',
                        text: lang.name + ' (' + lang.code + ')',
                        onAction: function() {
                            translateSelection(lang.code);
                        }
                    };
                });
                callback(items);
            }
        });

        // Register simple button for quick translation to configured default
        editor.ui.registry.addButton('writeassist_translate_quick', {
            icon: 'translate',
            tooltip: 'Nach Englisch übersetzen',
            onAction: function() {
                translateSelection('EN');
            }
        });

        // Add keyboard shortcut (Ctrl+Shift+T)
        editor.addShortcut('ctrl+shift+t', 'Übersetzen nach Englisch', function() {
            translateSelection('EN');
        });

        // Register custom translate icon if not available
        if (!editor.ui.registry.getAll().icons.translate) {
            editor.ui.registry.addIcon('translate', '<svg width="24" height="24" viewBox="0 0 24 24"><path d="M12.87 15.07l-2.54-2.51.03-.03A17.52 17.52 0 0014.07 6H17V4h-7V2H8v2H1v2h11.17C11.5 7.92 10.44 9.75 9 11.35 8.07 10.32 7.3 9.19 6.69 8h-2c.73 1.63 1.73 3.17 2.98 4.56l-5.09 5.02L4 19l5-5 3.11 3.11.76-2.04zM18.5 10h-2L12 22h2l1.12-3h4.75L21 22h2l-4.5-12zm-2.62 7l1.62-4.33L19.12 17h-3.24z"/></svg>');
        }
    };

    // Register plugin
    if (typeof tinymce !== 'undefined') {
        tinymce.PluginManager.add('writeassist_translate', setup);
    }
})();
