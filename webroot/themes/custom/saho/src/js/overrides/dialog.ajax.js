/**
 * @file
 * Extends the Drupal AJAX functionality to integrate the dialog API.
 */

(($, Drupal) => {
  /**
   * Initialize dialogs for Ajax purposes.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behaviors for dialog ajax functionality.
   */
  Drupal.behaviors.dialog = {
    attach: function attach(context, settings) {
      const $context = $(context);

      if (!$('#drupal-modal').length) {
        $(
          '<div id="drupal-modal" class="modal fade" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content"></div></div></div>',
        )
          .hide()
          .appendTo('body');
      }

      const $dialog = $context.closest('.modal-content');
      if ($dialog.length) {
        const dialogSettings = $dialog.closest('.modal').data('settings');
        if (dialogSettings && dialogSettings.drupalAutoButtons) {
          $dialog.trigger('dialogButtonsChange');
        }
      }

      const originalClose = settings.dialog.close;

      settings.dialog.close = (event) => {
        for (
          // eslint-disable-next-line
          var _len = arguments.length,
            args = Array(_len > 1 ? _len - 1 : 0),
            _key = 1;
          _key < _len;
          _key++
        ) {
          // eslint-disable-next-line
          args[_key - 1] = arguments[_key];
        }

        // eslint-disable-next-line
        originalClose.apply(settings.dialog, [event].concat(args));
        $(event.target).remove();
      };
    },
    prepareDialogButtons: function prepareDialogButtons($dialog) {
      const buttons = [];
      const $buttons = $dialog.find(
        '.form-actions input[type=submit], .form-actions button[type=submit], .form-actions a.button',
      );
      // eslint-disable-next-line func-names
      $buttons.each(function () {
        const $originalButton = $(this);
        this.style.display = 'none';
        buttons.push({
          text: $originalButton.html() || $originalButton.attr('value'),
          class: $originalButton.attr('class'),
          click: function click(e) {
            if ($originalButton[0].tagName === 'A') {
              $originalButton[0].click();
            } else {
              $originalButton
                .trigger('mousedown')
                .trigger('mouseup')
                .trigger('click');
              e.preventDefault();
            }
          },
        });
      });
      return buttons;
    },
  };

  // eslint-disable-next-line
  Drupal.AjaxCommands.prototype.openDialogByUrl = (ajax, response, status) => {
    const settings = $.extend(response.settings, {});

    const elementSettings = {
      progress: {
        type: 'throbber',
      },
      dialogType: '_modal',
      dialog: response.dialogOptions,
      url: settings.url,
    };

    const dialogUrlAjax = Drupal.ajax(elementSettings);
    dialogUrlAjax.execute();
  };

  Drupal.AjaxCommands.prototype.openDialog = (ajax, response, status) => {
    if (!response.selector) {
      return false;
    }
    let $dialog = $(response.selector);
    if (!$dialog.length) {
      $dialog = $(
        `<div id="${response.selector.replace(
          /^#/,
          '',
        )}" class="modal fade" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content"></div></div></div>`,
      )
        .hide()
        .appendTo('body');
    }

    if (!ajax.wrapper) {
      response.selector = `${response.selector.toString()} .modal-content`;
    }

    response.command = 'insert';
    response.method = 'html';

    // Do some extra things here, set Drupal.autocomplete options to render
    // autocomplete box inside the modal.
    if (
      Drupal.autocomplete !== undefined &&
      Drupal.autocomplete.options !== undefined
    ) {
      Drupal.autocomplete.options.appendTo = response.selector;
    }

    if (
      response.dialogOptions.modalDialogWrapBody === undefined ||
      response.dialogOptions.modalDialogWrapBody === true ||
      response.dialogOptions.modalDialogWrapBody === 'true'
    ) {
      response.data = `<div class="modal-body">${response.data}</div>`;
    }

    ajax.commands.insert(ajax, response, status);

    if (
      !response.dialogOptions.drupalAutoButtons ||
      response.dialogOptions.drupalAutoButtons !== 'false'
    ) {
      response.dialogOptions.drupalAutoButtons = true;
      if (
        response.dialogOptions.buttons === undefined ||
        response.dialogOptions.buttons.length <= 0
      ) {
        response.dialogOptions.buttons =
          Drupal.behaviors.dialog.prepareDialogButtons($dialog);
      }
    } else {
      response.dialogOptions.drupalAutoButtons = false;
    }

    $dialog.on('dialogButtonsChange', () => {
      const buttons = Drupal.behaviors.dialog.prepareDialogButtons($dialog);

      const dialog = Drupal.dialog($dialog.get(0));
      dialog.updateButtons(buttons);
    });

    response.dialogOptions = response.dialogOptions || {};
    const dialog = Drupal.dialog($dialog.get(0), response.dialogOptions);
    if (response.dialogOptions.modal) {
      dialog.showModal();
    } else {
      dialog.show();
    }

    $dialog.parent().find('.ui-dialog-buttonset').addClass('form-actions');
  };

  // eslint-disable-next-line
  Drupal.AjaxCommands.prototype.closeDialog = (ajax, response, status) => {
    const $dialog = $(response.selector);
    if ($dialog.length) {
      Drupal.dialog($dialog.get(0)).close();
    }

    $dialog.off('dialogButtonsChange');
  };

  // eslint-disable-next-line
  Drupal.AjaxCommands.prototype.setDialogOption = (ajax, response, status) => {
    const $dialog = $(response.selector);
    if ($dialog.length) {
      $dialog.dialog('option', response.optionName, response.optionValue);
    }
  };

  // eslint-disable-next-line
  $(window).on("dialog:aftercreate", (e, dialog, $element, settings) => {
    // eslint-disable-next-line
    $element.on("click.dialog", ".dialog-cancel", (e) => {
      dialog.close('cancel');
      e.preventDefault();
      e.stopPropagation();
    });
  });

  $(window).on('dialog:beforeclose', (e, dialog, $element) => {
    $element.off('.dialog');

    // Do some extra things here, set Drupal.autocomplete options to render
    // autocomplete box inside the modal.
    if (
      Drupal.autocomplete !== undefined &&
      Drupal.autocomplete.options !== undefined
    ) {
      if (Drupal.autocomplete.options.appendTo !== undefined) {
        delete Drupal.autocomplete.options.appendTo;
      }
    }
  });
})(jQuery, Drupal);
