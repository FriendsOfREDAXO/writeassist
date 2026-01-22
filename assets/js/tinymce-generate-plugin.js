/**
 * WriteAssist AI Generation Plugin for TinyMCE
 * 
 * Adds AI generation capabilities (Gemini/OpenWebUI) to TinyMCE
 */
(function() {
    'use strict';

    const setup = function(editor, url) {
        
        // Open the AI Generator Dialog
        const openGeneratorDialog = function(initialAction = null) {
            const selection = editor.selection.getContent({ format: 'text' });
            const hasSelection = selection && selection.trim().length > 0;
            
            // Default action logic
            let defaultAction = 'generate';
            if (hasSelection) {
                defaultAction = 'rewrite';
            }
            if (initialAction) {
                defaultAction = initialAction;
            }

            const dialogConfig = {
                title: 'AI Text Generator',
                body: {
                    type: 'panel',
                    items: [
                        {
                            type: 'selectbox',
                            name: 'action',
                            label: 'Aktion',
                            items: [
                                { value: 'generate', text: 'Text generieren (Neues Thema)' },
                                { value: 'rewrite', text: 'Umschreiben / Verbessern' },
                                { value: 'summarize', text: 'Zusammenfassen' },
                                { value: 'expand', text: 'Erweitern' },
                                { value: 'custom', text: 'Eigener Prompt / Anweisung' }
                            ]
                        },
                        {
                            type: 'selectbox',
                            name: 'style',
                            label: 'Stil / Tonfall',
                            items: [
                                { value: 'professional', text: 'Professionell' },
                                { value: 'casual', text: 'Locker' },
                                { value: 'simple', text: 'Einfach' },
                                { value: 'formal', text: 'Formell' },
                                { value: 'creative', text: 'Kreativ' },
                                { value: 'concise', text: 'Prägnant' }
                            ]
                        },
                        {
                            type: 'textarea',
                            name: 'text',
                            label: hasSelection ? 'Ausgewählter Text (Kontext)' : 'Thema / Stichworte',
                            placeholder: hasSelection ? '...' : 'Worüber soll geschrieben werden?',
                            enabled: true 
                        },
                        {
                            type: 'textarea',
                            name: 'prompt', // This is 'instructions' for generate, or 'prompt' for custom
                            label: 'Zusätzliche Anweisungen / Prompt',
                            placeholder: 'z.B.: "Schreibe im Du-Stil", "Fasse dich kurz", "Verwende Listen"'
                        }
                    ]
                },
                initialData: {
                    action: defaultAction,
                    style: 'professional',
                    text: selection, // Pre-fill with selection
                    prompt: ''
                },
                buttons: [
                    {
                        type: 'cancel',
                        text: 'Abbrechen'
                    },
                    {
                        type: 'submit',
                        text: 'Generieren',
                        primary: true
                    }
                ],
                onSubmit: function(api) {
                    const data = api.getData();
                    
                    // Show loading state (TinyMCE dialogs can be blocked)
                    api.block('Generiere Text... Bitte warten.');
                    
                    const formData = new FormData();
                    formData.append('action', data.action);
                    formData.append('text', data.text);
                    formData.append('style', data.style);
                    formData.append('prompt', data.prompt);
                    // Type default to paragraph for the plugin
                    formData.append('type', 'paragraph'); 
                    
                    fetch('./index.php?rex-api-call=writeassist_generate', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        api.unblock();
                        
                        if (result.success && result.text) {
                            
                            // Insert logic
                            if (data.action === 'generate') {
                                editor.insertContent(result.text);
                            } else {
                                // For rewrite/edit, typically replace selection
                                if (hasSelection) {
                                    editor.selection.setContent(result.text);
                                } else {
                                    // Fallback if they cleared selection but kept action
                                    editor.insertContent(result.text);
                                }
                            }
                            
                            api.close();
                            
                            editor.notificationManager.open({
                                text: 'Text erfolgreich generiert',
                                type: 'success',
                                timeout: 2000
                            });
                        } else {
                            editor.notificationManager.open({
                                text: result.error || 'Generierungsfehler',
                                type: 'error'
                            });
                        }
                    })
                    .catch(error => {
                        api.unblock();
                        editor.notificationManager.open({
                            text: 'API Fehler: ' + error,
                            type: 'error'
                        });
                    });
                },
                onChange: function(api, details) {
                    if (details.name === 'action') {
                        const data = api.getData();
                        const action = data.action;
                        
                        // Dynamic logic for UI labels could go here if redrawing was easier
                        // TinyMCE 5/6 dialogs are somewhat static in structure
                    }
                }
            };

            editor.windowManager.open(dialogConfig);
        };

        // Register Button
        editor.ui.registry.addButton('writeassist_generate', {
            icon: 'magic', // Built-in icon
            tooltip: 'AI Text Generator',
            onAction: function() {
                 openGeneratorDialog();
            }
        });

        // Register Menu items
        editor.ui.registry.addMenuItem('writeassist_generate_menu', {
            text: 'AI Text Generator',
            icon: 'magic',
            onAction: function() {
                openGeneratorDialog();
            }
        });
        
        // Context menu for quick actions
        editor.ui.registry.addMenuItem('writeassist_rewrite', {
            text: 'KI: Umschreiben',
            icon: 'edit-block',
            onAction: function() {
                 openGeneratorDialog('rewrite');
            }
        });

        // Add to context menu if selection exists
        editor.ui.registry.addContextMenu('writeassist_context', {
            update: function(element) {
                return !editor.selection.isCollapsed() ? 'writeassist_rewrite' : '';
            }
        });
    };

    // Metadata
    const meta = {
        name: 'WriteAssist AI Generator', 
        url: 'https://github.com/FriendsOfREDAXO/writeassist'
    };

    // Register based on TinyMCE version (REDAXO uses tinymce 5 or 6 via addon)
    // Global `tinymce` object
    if (typeof tinymce !== 'undefined') {
        tinymce.PluginManager.add('writeassist_generate', setup);
    }

})();
