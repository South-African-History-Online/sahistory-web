/**
 * @file
 * Simple Fancybox implementation for product images.
 */

(function (Drupal, once) {
  'use strict';

    Drupal.behaviors.productImagesFancybox = {
    attach: function (context, settings) {
      // Check if lightbox is enabled via theme settings
      if (!settings.saho_shop || settings.saho_shop.productImageDisplay !== 'lightbox') {
        return;
      }

      // Find elements with data-fancybox attribute in fancybox template
      const galleryItems = once('product-images-fancybox', '.product-images--fancybox [data-fancybox="product-gallery"]', context);

      if (galleryItems.length === 0) {
        return;
      }

      // Get theme setting
      const lightboxTheme = settings.saho_shop?.productImageLightboxTheme || 'dark';

      // Initialize Fancybox with simple configuration
      Fancybox.bind('[data-fancybox="product-gallery"]', {
        loop: true,
        buttons: ['close', 'fullscreen'],
        arrows: true,
        infobar: false,
        toolbar: false,
        clickContent: false,
        clickSlide: false,
        clickOutside: 'close',
        theme: lightboxTheme,
        keyboard: {
          Escape: 'close',
          ArrowRight: 'next',
          ArrowLeft: 'prev'
        }
      });
    }
  };

})(Drupal, once);
