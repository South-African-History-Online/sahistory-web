(function (Drupal, once) {
  'use strict';

  /**
   * Check that essential functionality is working.
   */
  Drupal.behaviors.sahoFunctionalityCheck = {
    attach: function (context, settings) {

      // Only run this check once on page load
      once('saho-functionality-check', 'html', context).forEach(function () {

        // Give other scripts time to load, then check functionality
        setTimeout(function() {

          // Check if jQuery is available
          if (typeof jQuery === 'undefined') {
            console.warn('SAHO Performance: jQuery not available');
            return;
          }

          // Check if Bootstrap modal is available
          if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
            console.warn('SAHO Performance: Bootstrap Modal not available');
          }

          // Check if sharing functionality is available
          if (typeof Drupal.sahoSharing === 'undefined') {
            console.warn('SAHO Performance: Sharing functionality not loaded');
          }

          // Check if citation functionality is available
          if (typeof Drupal.sahoCitation === 'undefined') {
            console.warn('SAHO Performance: Citation functionality not loaded');
          }

          // Check for citation and sharing buttons
          const citationButtons = document.querySelectorAll('a[data-citation-trigger], a[href="#cite"], button[data-citation-trigger]');
          const sharingButtons = document.querySelectorAll('a[data-sharing-trigger], button[data-sharing-trigger]');

          if (citationButtons.length > 0) {
            console.log('SAHO Performance: Found ' + citationButtons.length + ' citation buttons');
          }

          if (sharingButtons.length > 0) {
            console.log('SAHO Performance: Found ' + sharingButtons.length + ' sharing buttons');
          }

        }, 2000); // Wait 2 seconds for all scripts to load
      });
    }
  };

})(Drupal, once);