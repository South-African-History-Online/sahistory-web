/**
 * @file
 * Utility functions to display settings summaries on vertical tabs.
 */

(function ($) {

  'use strict';

  /**
   * Provide the summary information for the UPS settings vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the UPS settings summaries.
   */
  Drupal.behaviors.upsAdminFieldsetSummaries = {
    attach: function (context) {
      $('details#edit-uc-ups-credentials', context).drupalSetSummary(function (context) {
        var server = $('#edit-uc-ups-connection-address :selected', context).text().toLowerCase();
        return Drupal.t('Using UPS @role server', {'@role': server});
      });

      $('details#edit-uc-ups-markups', context).drupalSetSummary(function (context) {
        return Drupal.t('Rate markup') + ': '
          + $('#edit-uc-ups-rate-markup', context).val() + ' '
          + $('#edit-uc-ups-rate-markup-type', context).val() + '<br />'
          + Drupal.t('Weight markup') + ': '
          + $('#edit-uc-ups-weight-markup', context).val() + ' '
          + $('#edit-uc-ups-weight-markup-type', context).val();
      });

      $('details#edit-uc-ups-quote-options', context).drupalSetSummary(function (context) {
        if ($('#edit-uc-ups-insurance').is(':checked')) {
          return Drupal.t('Packages are insured');
        }
        else {
          return Drupal.t('Packages are not insured');
        }
      });
    }
  };

})(jQuery);
