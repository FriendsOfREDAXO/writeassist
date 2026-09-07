/* WriteAssist – Settings Page JS */
$(document).on('rex:ready', function () {

    // === AI Provider toggle ===
    var $providerSelect = $('#ai-provider-select');

    function updateProviderVisibility() {
        var value = $providerSelect.val();
        $('.ai-provider-settings').hide();
        if (value === 'gemini') {
            $('#gemini-settings').show();
        } else if (value === 'openai') {
            $('#openai-settings').show();
        } else if (value === 'openwebui') {
            $('#openwebui-settings').show();
        } else if (value === 'ai_platform') {
            $('#ai_platform-settings').show();
        }
        // 'disabled' → all sections and test button stay hidden
        if (value === 'disabled') {
            $('#wa-test-connection-wrap').hide();
        } else {
            $('#wa-test-connection-wrap').show();
        }
    }

    // Run immediately (not deferred) so the correct section is shown on page load
    if ($providerSelect.length) {
        updateProviderVisibility();
        $providerSelect.on('change', updateProviderVisibility);
    }

    // === Test AI connection ===
    // Testet bewusst den aktuellen (ggf. noch ungespeicherten) Formularstand statt nur
    // der gespeicherten Config, damit ein Key vor dem Speichern geprueft werden kann
    // (siehe ai_chat's Provider-Einstellungsseite fuer dasselbe Muster).
    var $testBtn = $('#test-ai-connection');
    if ($testBtn.length) {
        function fieldVal(id) {
            var el = document.getElementById(id);
            return el ? el.value : '';
        }

        $testBtn.on('click', function () {
            var $result = $('#test-connection-result');
            $result.html('<i class="rex-icon fa-spinner fa-spin"></i> Teste Verbindung...');
            $result.attr('class', '');
            $testBtn.prop('disabled', true);

            var params = {
                ai_provider: $providerSelect.length ? $providerSelect.val() : '',
                gemini_api_key: fieldVal('gemini-api-key'),
                gemini_model: fieldVal('gemini-model'),
                openai_api_key: fieldVal('openai-api-key'),
                openai_model: fieldVal('openai-model'),
                openwebui_api_key: fieldVal('openwebui-api-key'),
                openwebui_base_url: fieldVal('openwebui-base-url'),
                openwebui_model: fieldVal('openwebui-model'),
                ai_platform_text_profile_id: fieldVal('ai-platform-text-profile')
            };

            $.post('index.php?rex-api-call=writeassist_ai_test', params, null, 'json')
                .done(function (data) {
                    $testBtn.prop('disabled', false);
                    if (data.success) {
                        $result.html('<span class="text-success"><i class="rex-icon fa-check"></i> ' + data.message + '</span>');
                    } else {
                        $result.html('<span class="text-danger"><i class="rex-icon fa-exclamation-triangle"></i> ' + data.message + '</span>');
                    }
                })
                .fail(function (xhr, status, err) {
                    $testBtn.prop('disabled', false);
                    $result.html('<span class="text-danger"><i class="rex-icon fa-exclamation-triangle"></i> Fehler: ' + err + '</span>');
                });
        });
    }
});
