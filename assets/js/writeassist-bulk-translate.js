/* WriteAssist – Bulk Translate JS */
$(document).on('rex:ready', function () {
    var $startBtn   = $('#wa-bulk-start');
    var $cancelBtn  = $('#wa-bulk-cancel');
    var $form       = $('#wa-bulk-form-panel');
    var $progress   = $('#wa-bulk-progress');
    var $bar        = $('#wa-bulk-bar');
    var $progressText = $('#wa-bulk-progress-text');
    var $result     = $('#wa-bulk-result');

    if (!$startBtn.length) return;

    var BATCH_SIZE = 5;
    var cancelled = false;

    function post(data) {
        return $.ajax({
            url: 'index.php?rex-api-call=writeassist_bulk_translate',
            method: 'POST',
            data: data,
            dataType: 'json',
            timeout: 120000
        });
    }

    function setProgress(done, total) {
        var percent = total > 0 ? Math.round((done / total) * 100) : 100;
        $bar.css('width', percent + '%');
        $progressText.text(done + ' / ' + total);
    }

    function finish(summaryHtml) {
        $progress.hide();
        $form.find('select, input').prop('disabled', false);
        $startBtn.prop('disabled', false);
        $result.html(summaryHtml).show();
    }

    function showError(message) {
        finish(
            '<div class="alert alert-danger"><i class="rex-icon fa-exclamation-triangle"></i> '
            + $('<span>').text(message).html()
            + '</div>'
        );
    }

    function runBatches(sourceClang, items, skipped) {
        var total     = items.length;
        var doneCount = 0;
        var translated = 0;
        var errors      = 0;
        var log         = [];

        function nextBatch() {
            if (cancelled) {
                finish(
                    '<div class="alert alert-warning"><i class="rex-icon fa-info-circle"></i> Abgebrochen nach '
                    + doneCount + ' von ' + total + ' Einträgen. <strong>' + translated + '</strong> übersetzt, '
                    + '<strong>' + skipped + '</strong> übersprungen, '
                    + '<strong>' + errors + '</strong> Fehler.</div>'
                );
                return;
            }

            if (0 === items.length) {
                var html = '<div class="panel panel-success">'
                    + '<div class="panel-heading"><strong><i class="rex-icon fa-check"></i> Übersetzung abgeschlossen</strong></div>'
                    + '<div class="panel-body">'
                    + '<p><strong>' + translated + '</strong> übersetzt &nbsp;|&nbsp; '
                    + '<strong>' + skipped + '</strong> übersprungen &nbsp;|&nbsp; '
                    + '<strong>' + errors + '</strong> Fehler</p>';

                if (log.length > 0) {
                    html += '<details><summary>Fehler-Details (' + log.length + ')</summary><ul>';
                    $.each(log, function (i, msg) {
                        html += '<li>' + $('<span>').text(msg).html() + '</li>';
                    });
                    html += '</ul></details>';
                }

                html += '</div></div>';
                finish(html);
                return;
            }

            var batch = items.splice(0, BATCH_SIZE);

            post({
                action:       'translate_batch',
                source_clang: sourceClang,
                items:        JSON.stringify(batch)
            })
            .done(function (data) {
                if (!data.success) {
                    showError(data.error || 'Unbekannter Fehler');
                    return;
                }

                doneCount  += batch.length;
                translated += data.translated;
                errors     += data.errors;
                if (data.log && data.log.length > 0) {
                    log = log.concat(data.log);
                }

                setProgress(doneCount, total);
                nextBatch();
            })
            .fail(function (xhr, status, err) {
                showError('Verbindungsfehler: ' + String(err || status));
            });
        }

        nextBatch();
    }

    $startBtn.on('click', function () {
        var sourceClang       = $('#wa-bulk-source-clang').val();
        var doArticles        = $('#wa-bulk-articles').is(':checked');
        var doCategories      = $('#wa-bulk-categories').is(':checked');
        var onlyUntranslated  = $('#wa-bulk-only-untranslated').is(':checked') ? 1 : 0;
        var seoTitle          = $('#wa-bulk-seo-title').is(':checked') ? 1 : 0;
        var seoDescription    = $('#wa-bulk-seo-description').is(':checked') ? 1 : 0;

        if (!doArticles && !doCategories) {
            showError($startBtn.data('select-hint') || 'Bitte mindestens Artikel oder Kategorien auswählen.');
            return;
        }
        var type = (doArticles && doCategories) ? 'both' : (doArticles ? 'articles' : 'categories');

        cancelled = false;
        $startBtn.prop('disabled', true);
        $form.find('select, input').prop('disabled', true);
        $result.hide();
        $progress.show();
        setProgress(0, 0);
        $progressText.text('Ermittle Einträge …');

        post({
            action:            'collect',
            source_clang:      sourceClang,
            type:              type,
            only_untranslated: onlyUntranslated,
            seo_title:         seoTitle,
            seo_description:   seoDescription
        })
        .done(function (data) {
            if (!data.success) {
                showError(data.error || 'Unbekannter Fehler');
                return;
            }

            if (0 === data.total) {
                finish('<div class="alert alert-info"><i class="rex-icon fa-info-circle"></i> Keine Einträge zu übersetzen gefunden.</div>');
                return;
            }

            setProgress(0, data.total);
            runBatches(sourceClang, data.items, data.skipped || 0);
        })
        .fail(function (xhr, status, err) {
            showError('Verbindungsfehler: ' + String(err || status));
        });
    });

    $cancelBtn.on('click', function () {
        cancelled = true;
        $cancelBtn.prop('disabled', true);
    });
});
