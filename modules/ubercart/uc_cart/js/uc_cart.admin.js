/**
 * @file
 * Utility functions to display settings summaries on vertical tabs.
 */

(function ($) {

  'use strict';

  /**
   * Provide the summary information for the cart settings vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the cart settings summaries.
   */
  Drupal.behaviors.ucCartAdminFieldsetSummaries = {
    attach: function (context) {
      $('details#edit-lifetime', context).drupalSetSummary(function (context) {
        return Drupal.t('Anonymous users') + ': '
          + $('#edit-uc-cart-anon-duration', context).val() + ' '
          + $('#edit-uc-cart-anon-unit', context).val() + '<br />'
          + Drupal.t('Authenticated users') + ': '
          + $('#edit-uc-cart-auth-duration', context).val() + ' '
          + $('#edit-uc-cart-auth-unit', context).val();
      });

      $('details#edit-checkout', context).drupalSetSummary(function (context) {
        if ($('#edit-uc-checkout-enabled').is(':checked')) {
          return Drupal.t('Checkout is enabled.');
        }
        else {
          return Drupal.t('Checkout is disabled.');
        }
      });
      $('details#edit-anonymous', context).drupalSetSummary(function (context) {
        if ($('#edit-uc-checkout-anonymous').is(':checked')) {
          return Drupal.t('Anonymous checkout is enabled.');
        }
        else {
          return Drupal.t('Anonymous checkout is disabled.');
        }
      });
    }
  };

})(jQuery);
