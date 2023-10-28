/**
 * @file
 * Utility functions to display settings summaries on vertical tabs.
 */

(function ($) {

  'use strict';

  /**
   * Provide the summary information for the product settings vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the product settings summaries.
   */
  Drupal.behaviors.ucProductFieldsetSummaries = {
    attach: function (context) {
      $('details#edit-settings-uc-product', context).drupalSetSummary(function (context) {
        var vals = [];
        $('input:checked', context).next('label').each(function () {
          vals.push(Drupal.checkPlain($(this).text()));
        });
        if ($('#edit-settings-uc-product-shippable', context).is(':not(:checked)')) {
          vals.unshift(Drupal.t('Not shippable'));
        }
        return vals.join(', ');
      });
    }
  };

})(jQuery);
