/* WriteAssist – Bulk Translate JS */
$(document).on('rex:ready', function () {
    var $startBtn   = $('#wa-bulk-start');
    var $form       = $('#wa-bulk-form-panel');
    var $progress   = $('#wa-bulk-progress');
    var $result     = $('#wa-bulk-result');

    if (!$startBtn.length) return;

    $startBtn.on('click', function () {
        var sourceClang       = $('#wa-bulk-source-clang').val();
        var type              = $('input[name="wa-bulk-type"]:checked').val();
        var onlyUntranslated  = $('#wa-bulk-only-untranslated').is(':checked') ? 1 : 0;

        $startBtn.prop('disabled', true);
        $form.find('select, input').prop('disabled', true);
        $progress.show();
        $result.hide();

        $.ajax({
            url: 'index.php',
            method: 'POST',
            data: {
                'rex-api-call': 'writeassist_bulk_translate',
                source_clang:      sourceClang,
                type:              type,
                only_untranslated: onlyUntranslated
            },
            dataType: 'json',
            timeout: 300000 // 5 min
        })
        .done(function (data) {
            $progress.hide();
            $form.find('select, input').prop('disabled', false);
            $startBtn.prop('disabled', false);
            $('#wa-bulk-spinner').removeClass('fa-spin');

            if (data.success) {
                var html = '<div class="panel panel-success">'
                    + '<div class="panel-heading"><strong><i class="rex-icon fa-check"></i> Übersetzung abgeschlossen</strong></div>'
                    + '<div class="panel-body">'
                    + '<p><strong>' + data.translated + '</strong> Namen übersetzt &nbsp;|&nbsp; '
                    + '<strong>' + data.skipped   + '</strong> übersprungen &nbsp;|&nbsp; '
                    + '<strong>' + data.errors    + '</strong> Fehler</p>';

                if (data.log && data.log.length > 0) {
                    html += '<details><summary>Fehler-Details (' + data.log.length + ')</summary><ul>';
                    $.each(data.log, function (i, msg) {
                        html += '<li>' + $('<span>').text(msg).html() + '</li>';
                    });
                    html += '</ul></details>';
                }

                html += '</div></div>';
                $result.html(html).show();
            } else {
                $result.html(
                    '<div class="alert alert-danger"><i class="rex-icon fa-exclamation-triangle"></i> '
                    + $('<span>').text(data.error || 'Unbekannter Fehler').html()
                    + '</div>'
                ).show();
            }
        })
        .fail(function (xhr, status, err) {
            $progress.hide();
            $form.find('select, input').prop('disabled', false);
            $startBtn.prop('disabled', false);
            $result.html(
                '<div class="alert alert-danger"><i class="rex-icon fa-exclamation-triangle"></i> Verbindungsfehler: '
                + $('<span>').text(String(err || status)).html()
                + '</div>'
            ).show();
        });
    });
});
