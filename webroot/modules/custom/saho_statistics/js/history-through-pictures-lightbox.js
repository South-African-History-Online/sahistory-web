/**
 * @file
 * GLightbox initialization for History Through Pictures gallery.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.historyThroughPicturesLightbox = {
    attach: function (context, settings) {
      // Initialize GLightbox for images without feature links.
      once('glightbox-init', '.history-picture-lightbox', context).forEach(function (element) {
        // Check if GLightbox is loaded.
        if (typeof GLightbox !== 'undefined') {
          const lightbox = GLightbox({
            selector: '.history-picture-lightbox',
            touchNavigation: true,
            loop: true,
            autoplayVideos: false,
            closeOnOutsideClick: true,
            skin: 'saho-lightbox',
            descPosition: 'bottom',
          });
        }
        else {
          console.warn('GLightbox library not loaded');
        }
      });
    }
  };

})(Drupal, once);
