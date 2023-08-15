/**
 * @file
 * Asset Injector applies Ace Editor to simplify work.
 */

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.assetInjector = {
    attach: function (context, settings) {
      if (typeof ace == 'undefined' || typeof ace.edit != 'function') {
        return;
      }
      $('body').addClass('ace-editor');
      $('.ace-editor').once('ace-editor-added').each(function () {
        var textarea = $(this).parent().siblings().find('textarea');
        var mode = $(textarea).attr('data-ace-mode');

        if (mode) {
          $(textarea).css('position', 'absolute')
            .css('width', "1px")
            .css('height', "1px")
            .css('opacity', 0)
            .attr('tabindex', -1);

          var editor = ace.edit(this);
          editor.getSession().setMode('ace/mode/' + mode);
          editor.getSession().setTabSize(2);

          editor.getSession().on('change', function () {
            textarea.val(editor.getSession().getValue());
          });

          $('.resizable').resizable({
            resize: function (event, ui) {
              editor.resize();
            }
          });

          editor.setValue(textarea.val());
          editor.resize();

          // When the form fails to validate because the text area is required,
          // shift the focus to the editor.
          textarea.on('focus', function () {
            editor.textInput.focus()
          })
        }
      });
    }
  };
})(jQuery, Drupal);
