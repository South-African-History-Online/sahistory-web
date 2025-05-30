/**
 * @file
 * Dialog API inspired by HTML5 dialog element.
 *
 * @see http://www.whatwg.org/specs/web-apps/current-work/multipage/commands.html#the-dialog-element
 */

(($, Drupal, drupalSettings) => {
  /**
   * Default dialog options.
   *
   * @type {object}
   *
   * @prop {bool} [autoOpen=true]
   * @prop {string} [dialogClasses='']
   * @prop {string} [dialogShowHeader=true]
   * @prop {string} [dialogShowHeaderTitle=true]
   * @prop {string} [dialogStatic=false]
   * @prop {string} [dialogHeadingLevel=5]
   * @prop {string} [buttonClass='btn']
   * @prop {string} [buttonPrimaryClass='btn-primary']
   * @prop {function} close
   */
  drupalSettings.dialog = {
    autoOpen: true,
    dialogClasses: '',
    dialogShowHeader: true,
    dialogShowHeaderTitle: true,
    dialogStatic: false,
    dialogHeadingLevel: 5,
    buttonClass: 'btn',
    buttonPrimaryClass: 'btn-primary',
    close: function close(event) {
      Drupal.modal(event.target).close();
      Drupal.detachBehaviors(event.target, null, 'unload');
    },
  };

  /**
   * @typedef {object} Drupal.dialog~dialogDefinition
   *
   * @prop {boolean} open
   *   Is the dialog open or not.
   * @prop {*} returnValue
   *   Return value of the dialog.
   * @prop {function} show
   *   Method to display the dialog on the page.
   * @prop {function} showModal
   *   Method to display the dialog as a modal on the page.
   * @prop {function} close
   *   Method to hide the dialog from the page.
   */

  /**
   * Polyfill HTML5 dialog element with jQueryUI.
   *
   * @param {HTMLElement} element
   *   The element that holds the dialog.
   * @param {object} options
   *   jQuery UI options to be passed to the dialog.
   *
   * @return {Drupal.dialog~dialogDefinition}
   *   The dialog instance.
   */
  Drupal.dialog = (element, options) => {
    let undef;
    const $element = $(element);
    const dialog = {
      open: false,
      returnValue: undef,
    };

    function settingIsTrue(setting) {
      return setting !== undefined && (setting === true || setting === 'true');
    }

    function updateButtons(buttons) {
      const settings = $.extend({}, drupalSettings.dialog, options);

      const modalFooter = $('<div class="modal-footer">');
      // eslint-disable-next-line func-names
      $.each(buttons, function () {
        const classes = [settings.buttonClass, settings.buttonPrimaryClass];

        const button = $('<button type="button">');
        if (this.attributes !== undefined) {
          $(button).attr(this.attributes);
        }
        $(button)
          .addClass(this.class)
          .click((e) => {
            if (this.click !== undefined) {
              this.click(e);
            }
          })
          .html(this.text);

        if (
          $(button).attr('class') &&
          !$(button)
            .attr('class')
            .match(/\bbtn-.*/)
        ) {
          $(button).addClass(classes.join(' '));
        }

        $(modalFooter).append(button);
      });
      if ($('.modal-dialog .modal-content .modal-footer', $element).length > 0) {
        $('.modal-dialog .modal-content .modal-footer', $element).remove();
      }
      if ($(modalFooter).html().length > 0) {
        $(modalFooter).appendTo($('.modal-dialog .modal-content', $element));
      }
    }

    function dispatchDialogEvent(eventType, dialog, element, settings) {
      if (typeof DrupalDialogEvent === 'undefined') {
        $(window).trigger(`dialog:${eventType}`, [dialog, $(element), settings]);
      } else {
        const event = new DrupalDialogEvent(eventType, dialog, settings || {});
        element.dispatchEvent(event);
      }
    }

    function openDialog(settings) {
      const mergedSettings = $.extend({}, drupalSettings.dialog, options, settings);

      dispatchDialogEvent('beforecreate', dialog, $element.get(0), mergedSettings);

      $(window).trigger('dialog:beforecreate', [dialog, $element, mergedSettings]);

      if (mergedSettings.dialogClasses !== undefined) {
        $('.modal-dialog', $element)
          .removeAttr('class')
          .addClass('modal-dialog')
          .addClass(mergedSettings.dialogClasses);
      }

      $($element).attr('data-settings', JSON.stringify(mergedSettings));

      // The modal dialog header.
      if (settingIsTrue(mergedSettings.dialogShowHeader)) {
        let modalHeader = '<div class="modal-header">';
        const heading = mergedSettings.dialogHeadingLevel;

        if (settingIsTrue(mergedSettings.dialogShowHeaderTitle)) {
          modalHeader += `<h${heading} class="modal-title">${mergedSettings.title}</h${heading}>`;
        }

        modalHeader += `<button type="button" class="close btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="${Drupal.t(
          'Close'
        )}"><span aria-hidden="true" class="visually-hidden">&times;</span></button>`;

        $(modalHeader).prependTo($('.modal-dialog .modal-content', $element));
      }

      if (settingIsTrue(mergedSettings.dialogStatic)) {
        $($element).attr('data-bs-backdrop', 'static');
        $($element).attr('data-bs-keyboard', 'false');
      }

      // Non-modal configuration: Remove backdrop and allow interaction with the page.
      if (!mergedSettings.modal) {
        $($element).attr('data-bs-backdrop', 'false');
        $($element).attr('data-bs-keyboard', 'true');

        // Set pointer-events: none; for non-modal dialogs to allow interaction with the page.
        $element.css('pointer-events', 'none');
        $element.find('.modal-dialog').css('pointer-events', 'auto'); // Ensure dialog itself is still interactive
      }

      if (settingIsTrue(mergedSettings.drupalAutoButtons) && mergedSettings.buttons.length > 0) {
        updateButtons(mergedSettings.buttons);
      }

      if ($element.modal !== undefined) {
        $element.modal(mergedSettings);
        $element.modal('show');
      }

      const originalResizeSetting = mergedSettings.autoResize;
      mergedSettings.autoResize = false;
      dispatchDialogEvent('aftercreate', dialog, $element.get(0), mergedSettings);
      mergedSettings.autoResize = originalResizeSetting;
    }

    function closeDialog(value) {
      if ($element.modal !== undefined) {
        $element.modal('hide');
      }
      dialog.returnValue = value;
      dialog.open = false;
    }

    dialog.updateButtons = (buttons) => {
      updateButtons(buttons);
    };

    dialog.show = () => {
      openDialog({ modal: false });
    };
    dialog.showModal = () => {
      openDialog({ modal: true });
    };
    dialog.close = () => {
      closeDialog({});
    };

    $element.on('hide.bs.modal', () => {
      dispatchDialogEvent('beforeclose', dialog, $element.get(0));
    });

    $element.on('hidden.bs.modal', () => {
      dispatchDialogEvent('afterclose', dialog, $element.get(0));
    });

    return dialog;
  };
})(jQuery, Drupal, drupalSettings);
