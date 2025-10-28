(function (Drupal, once) {
  'use strict';

  /**
   * Image optimization behavior for better performance.
   */
  Drupal.behaviors.sahoImageOptimization = {
    attach: function (context, settings) {

      // Add lazy loading to images that don't have it.
      once('saho-lazy-loading', 'img:not([loading])', context).forEach(function (img) {
        // Skip images that are above the fold, critical, or in modals.
        if (!img.closest('.hero-banner, .navbar, .breadcrumb, .modal, .citation-modal, .sharing-modal')) {
          img.setAttribute('loading', 'lazy');
        }
      });

      // Add WebP support detection and conversion.
      once('saho-webp-support', 'img[data-src]', context).forEach(function (img) {
        if (supportsWebP()) {
          const src = img.getAttribute('data-src');
          if (src && !src.includes('.webp')) {
            // Check if WebP version exists.
            const webpSrc = src.replace(/\.(jpg|jpeg|png)$/i, '.webp');
            testImageExists(webpSrc).then(function (exists) {
              if (exists) {
                img.src = webpSrc;
              } else {
                img.src = src;
              }
            });
          }
        } else {
          img.src = img.getAttribute('data-src');
        }
      });

      // Add intersection observer for progressive image loading.
      if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              const img = entry.target;
              if (img.getAttribute('data-src')) {
                img.src = img.getAttribute('data-src');
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
              }
            }
          });
        }, {
          rootMargin: '50px 0px'
        });

        once('saho-intersection-observer', 'img[data-src]', context).forEach(function (img) {
          imageObserver.observe(img);
        });
      }
    }
  };

  /**
   * Check if browser supports WebP format.
   */
  function supportsWebP() {
    return new Promise(function (resolve) {
      const webP = new Image();
      webP.onload = webP.onerror = function () {
        resolve(webP.height === 2);
      };
      webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
    });
  }

  /**
   * Test if an image URL exists.
   */
  function testImageExists(url) {
    return new Promise(function (resolve) {
      const img = new Image();
      img.onload = function () {
 resolve(TRUE); };
      img.onerror = function () {
 resolve(FALSE); };
      img.src = url;
    });
  }

})(Drupal, once);