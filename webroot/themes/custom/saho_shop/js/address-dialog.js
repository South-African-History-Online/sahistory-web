/**
 * @file
 * Address dialog functionality for SahoShop theme.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * Address dialog behavior.
   */
  Drupal.behaviors.saho_shopAddressDialog = {
    attach(context, settings) {
      // Check if address dialog is enabled
      if (!drupalSettings.saho_shop || !drupalSettings.saho_shop.addressDialog) {
        return;
      }

      // Define the selectors for all address book links that should open in dialogs
      const addressBookSelectors = [
        '.address-book__add-link',
        '.address-book__edit-link',
        '.address-book__delete-link'
      ];

      // Process each type of address book link
      addressBookSelectors.forEach(function(selector) {
        once('saho_shop-address-dialog', selector, context).forEach(function (link) {
          // Add AJAX dialog attributes
          link.classList.add('use-ajax');
          link.setAttribute('data-dialog-type', 'modal');

          // Determine dialog size based on link type
          let dialogClass = '';
          let dialogSize = {
            width: '80%',
            maxWidth: '800px'
          };


          // Set dialog options based on link type
          let dialogOptions = {
            width: dialogSize.width,
            maxWidth: dialogSize.maxWidth,
            height: 'auto',
            modal: true,
            draggable: false,
            resizable: false,
            autoResize: true,
            closeOnEscape: true,
            dialogClass: dialogClass,
          };

          link.setAttribute('data-dialog-options', JSON.stringify(dialogOptions));
        });
      });
    }
  };

})(Drupal, drupalSettings);
