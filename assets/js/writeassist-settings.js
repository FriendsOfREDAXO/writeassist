/* WriteAssist – Settings Page JS */
$(document).on('rex:ready', function () {

    // === AI Provider toggle ===
    var $providerSelect = $('#ai-provider-select');

    function updateProviderVisibility() {
        var value = $providerSelect.val();
        $('.ai-provider-settings').hide();
        $('#' + (value === 'gemini' ? 'gemini' : 'openwebui') + '-settings').show();
    }

    if ($providerSelect.length) {
        $providerSelect.on('change', updateProviderVisibility);
        updateProviderVisibility();
    }

    // === Test AI connection ===
    var $testBtn = $('#test-ai-connection');
    if ($testBtn.length) {
        $testBtn.on('click', function () {
            var $result = $('#test-connection-result');
            $result.html('<i class="rex-icon fa-spinner fa-spin"></i> Teste Verbindung...');
            $result.attr('class', '');
            $testBtn.prop('disabled', true);

            $.getJSON('index.php?rex-api-call=writeassist_ai_test')
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
