/**
 * WriteAssist MarkItUp integration
 *
 * Provides helper functions that can be used from MarkItUp buttons.
 */
(function () {
    'use strict';

    window.btnMarkitupWriteAssistTranslate = function (h, targetLang = 'EN') {
        var selected = h.selection || (h.textarea ? h.textarea.value : '');
        if (!selected || selected.trim() === '') {
            alert('Bitte zuerst Text markieren');
            return;
        }

        var fd = new FormData();
        fd.append('text', selected);
        fd.append('target_lang', targetLang);
        fd.append('preserve_formatting', '1');

        fetch('./index.php?rex-api-call=writeassist_translate', { method: 'POST', body: fd })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.success && data.translation) {
                    $.markItUp({ replaceWith: data.translation });
                } else {
                    alert(data.error || 'Übersetzung fehlgeschlagen');
                }
            })
            .catch(function (err) {
                alert('API-Fehler: ' + (err.message || err));
            });
    };

    window.btnMarkitupWriteAssistImprove = function (h, language = 'de', pickyMode = false) {
        var selected = h.selection || (h.textarea ? h.textarea.value : '');
        if (!selected || selected.trim() === '') {
            alert('Bitte zuerst Text markieren');
            return;
        }

        var fd = new FormData();
        fd.append('text', selected);
        fd.append('language', language);
        fd.append('picky', pickyMode ? '1' : '0');
        fd.append('auto_correct', '1');

        fetch('./index.php?rex-api-call=writeassist_improve', { method: 'POST', body: fd })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.success && data.corrected_text) {
                    $.markItUp({ replaceWith: data.corrected_text });
                } else {
                    alert(data.error || 'Korrektur fehlgeschlagen');
                }
            })
            .catch(function (err) {
                alert('API-Fehler: ' + (err.message || err));
            });
    };

})();
