/* WriteAssist – Settings Page JS */
$(document).on('rex:ready', function () {

    // === Yes/No selects -> visual toggle switches ===
    // Ersetzt sichtbar die einfachen Ja/Nein-<select>-Felder durch einen Toggle-Switch,
    // ohne die zugrunde liegenden Felder (und damit rex_config_form's Save-Logik) anzufassen:
    // der Switch spiegelt nur den Wert des verborgenen <select> und stoesst dessen change-Event an.
    $('select.wa-yesno-toggle').each(function () {
        var $select = $(this);
        if ($select.data('wa-toggled')) {
            return;
        }
        $select.data('wa-toggled', true);

        var checked = $select.val() === '1';

        var $toggle = $(
            '<label class="wa-toggle">' +
                '<input type="checkbox"' + (checked ? ' checked' : '') + '>' +
                '<span class="wa-toggle-track"></span>' +
            '</label>'
        );

        var $checkbox = $toggle.find('input[type="checkbox"]');
        $checkbox.on('change', function () {
            $select.val($checkbox.prop('checked') ? '1' : '0').trigger('change');
        });

        // Core umschliesst jedes <select> mit einem eigenen ".rex-select-style"-Wrapper-Div,
        // das per Backend-Theme-CSS selbst wie eine Dropdown-Box aussieht (Rahmen + Pfeil) -
        // ein "hide()" nur auf dem <select> reicht daher nicht, sonst bleibt die leere
        // Wrapper-Box sichtbar. Das <select> bleibt im DOM (fuer's Speichern ueber
        // rex_config_form), der komplette Wrapper wird versteckt und der Switch danach
        // eingefuegt.
        var $wrapper = $select.closest('.rex-select-style');
        var $hideTarget = $wrapper.length ? $wrapper : $select;
        $hideTarget.hide();
        $hideTarget.after($toggle);
    });

    // === Übersetzungs-Dienst <-> DeepL-Fieldset ===
    // Wenn "Text-KI" als Uebersetzungs-Dienst gewaehlt ist, wird der DeepL-Key von
    // keiner Funktion des Addons mehr benoetigt (siehe AutoTranslateService::translateText()) -
    // das DeepL-Fieldset wird dann nur ausgegraut (nicht versteckt), da der Key trotzdem
    // gueltig bleiben und z.B. nach einem spaeteren Zurueckwechseln weiterverwendet werden kann.
    var $translationProviderSelect = $('#translation-provider-select');
    var $deeplWrap = $('#wa-deepl-fieldset-wrap');
    // Kontext-Feld ist nur bei Text-KI sinnvoll (DeepL nutzt keinen freien Prompt) -
    // wird daher komplett ausgeblendet statt nur ausgegraut, siehe pages/settings.php.
    var $translationContextWrap = $('#wa-translation-context-wrap');

    function updateDeeplRelevance() {
        if (!$translationProviderSelect.length) {
            return;
        }
        var isAi = $translationProviderSelect.val() === 'ai';
        if ($deeplWrap.length) {
            $deeplWrap.toggleClass('wa-fieldset-fade', isAi);
        }
        if ($translationContextWrap.length) {
            $translationContextWrap.toggle(isAi);
        }
    }

    if ($translationProviderSelect.length) {
        updateDeeplRelevance();
        $translationProviderSelect.on('change', updateDeeplRelevance);
    }

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
