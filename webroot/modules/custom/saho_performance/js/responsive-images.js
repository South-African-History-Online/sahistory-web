(function (Drupal, once) {
  'use strict';

  /**
   * Responsive image optimization behavior.
   */
  Drupal.behaviors.sahoResponsiveImages = {
    attach: function (context, settings) {

      // Add responsive image attributes to improve performance
      once('saho-responsive-images', 'img', context).forEach(function (img) {

        // Skip if already optimized or is SVG
        if (img.hasAttribute('data-optimized') || img.src.includes('.svg')) {
          return;
        }

        // Get actual display dimensions
        const displayWidth = img.clientWidth || img.offsetWidth;
        const displayHeight = img.clientHeight || img.offsetHeight;

        // Only process if image is visible and has dimensions
        if (displayWidth === 0 || displayHeight === 0) {
          return;
        }

        // Get natural dimensions
        const naturalWidth = img.naturalWidth;
        const naturalHeight = img.naturalHeight;

        // Check if image is oversized (more than 2x the display size)
        const isOversized = naturalWidth > (displayWidth * 2) || naturalHeight > (displayHeight * 2);

        if (isOversized) {
          // Add responsive attributes
          if (!img.hasAttribute('sizes')) {
            // Calculate appropriate sizes attribute
            const vw = Math.round((displayWidth / window.innerWidth) * 100);
            img.setAttribute('sizes', '(max-width: 768px) ' + vw + 'vw, ' + displayWidth + 'px');
          }

          // Add loading optimization for below-fold images
          if (!img.hasAttribute('loading') && !isAboveFold(img)) {
            img.setAttribute('loading', 'lazy');
          }

          // Add decoding attribute for better performance
          if (!img.hasAttribute('decoding')) {
            img.setAttribute('decoding', 'async');
          }
        }

        // Mark as optimized
        img.setAttribute('data-optimized', 'true');
      });

      // Optimize WebP delivery
      once('saho-webp-optimization', 'img[src*=".jpg"], img[src*=".jpeg"], img[src*=".png"]', context).forEach(function (img) {

        // Skip if already has WebP source or is being handled by picture element
        if (img.closest('picture') || img.hasAttribute('data-webp-checked')) {
          return;
        }

        const originalSrc = img.src;
        const webpSrc = originalSrc.replace(/\.(jpe?g|png)$/i, '.webp');

        // Test if WebP version exists
        testImageExists(webpSrc).then(function(exists) {
          if (exists && supportsWebP()) {
            img.src = webpSrc;
          }
        });

        img.setAttribute('data-webp-checked', 'true');
      });
    }
  };

  /**
   * Check if element is above the fold
   */
  function isAboveFold(element) {
    const rect = element.getBoundingClientRect();
    return rect.top < window.innerHeight;
  }

  /**
   * Test if an image URL exists
   */
  function testImageExists(url) {
    return new Promise(function(resolve) {
      const img = new Image();
      img.onload = function() { resolve(true); };
      img.onerror = function() { resolve(false); };
      img.src = url;
    });
  }

  /**
   * Check if browser supports WebP format
   */
  function supportsWebP() {
    return new Promise(function(resolve) {
      const webP = new Image();
      webP.onload = webP.onerror = function () {
        resolve(webP.height === 2);
      };
      webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
    });
  }

})(Drupal, once);