/**
 * WriteAssist TipTap integration
 * Adds toolbar buttons to tiptap which call the writeassist APIs
 */
(function () {
    'use strict';

    var fn = function (editor) {
        // Example: add a button to the toolbar if the host has a toolbar hook
        // Not all tiptap implementations have the same toolbar system. The init script can check for window.writeassistTipTapPlugin and add custom buttons.
        if (!editor) return;
        // Provide a convenience function for other scripts
        window.writeassistTipTapPluginInstance = editor;
    };
    window.tiptapPlugins = window.tiptapPlugins || [];
    window.tiptapPlugins.push(fn);
    window.writeassistTipTapPlugin = fn;
})();
