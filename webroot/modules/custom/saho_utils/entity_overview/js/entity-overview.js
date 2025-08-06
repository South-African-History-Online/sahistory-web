/**
 * @file
 * JavaScript behaviors for the Entity Overview block.
 * Simple, clean functionality with no AJAX complexity.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Behavior for Entity Overview block.
   */
  Drupal.behaviors.entityOverview = {
    attach: function (context, settings) {
      // Process each entity overview block once for basic initialization
      once('entity-overview-processed', '.entity-overview-block', context).forEach(function (element) {
        // Basic block initialization complete - silent initialization
      });
    }
  };

})(jQuery, Drupal, drupalSettings);