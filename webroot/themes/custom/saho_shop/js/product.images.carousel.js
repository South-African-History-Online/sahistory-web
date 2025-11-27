/**
 * @file
 * Product Images Carousel implementation using Fancyapps Carousel.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.productImagesCarousel = {
    attach: function (context, settings) {
      // Check if carousel is enabled via theme settings
      if (!settings.saho_shop || settings.saho_shop.productImageDisplay !== 'carousel') {
        return;
      }

      // Get carousel settings
      const carouselSettings = settings.saho_shop;
      const plugins = [];

      // Check and add Arrows plugin if enabled
      if (carouselSettings.carouselArrows && typeof Arrows !== 'undefined') {
        plugins.push(Arrows);
      }

      // Check and add Dots plugin if enabled
      if (carouselSettings.carouselDots && typeof Dots !== 'undefined') {
        plugins.push(Dots);
      }

      // Check and add Thumbs plugin if enabled
      if (carouselSettings.carouselThumbs && typeof Thumbs !== 'undefined') {
        plugins.push(Thumbs);
      }

      // Check and add Zoomable plugin if panzoom functionality is enabled
      if (carouselSettings.carouselFunctionality === 'panzoom' && typeof Zoomable !== 'undefined') {
        plugins.push(Zoomable);
      }

      // Find product carousel containers
      const carouselContainers = once('product-images-carousel', '.f-carousel.product-carousel', context);

      carouselContainers.forEach(function(container, index) {
        // Get all carousel slides
        const slides = container.querySelectorAll('.f-carousel__slide');

        if (slides.length === 0) {
          return;
        }

        // Don't initialize carousel if only one slide (unless panzoom or fancybox is enabled)
        if (slides.length === 1 && carouselSettings.carouselFunctionality === 'basic') {
          return;
        }

        try {
          // Initialize carousel with selected plugins
          Carousel(container, {
          }, plugins).init();

          // Initialize Fancybox if fancybox functionality is enabled
          if (carouselSettings.carouselFunctionality === 'fancybox' && typeof Fancybox !== 'undefined') {
            Fancybox.bind("[data-fancybox]", {
              // Fancybox options can be customized here
            });
          }

        } catch (error) {
          console.error(`Error initializing carousel ${container.id}:`, error);
        }
      });
    }
  };

})(Drupal, once);
