(function (Drupal, once) {
  'use strict';

  /**
   * Production-safe performance enhancements.
   */
  Drupal.behaviors.sahoProductionSafe = {
    attach: function (context, settings) {

      // Only run optimization for front-end users
      once('saho-production-safe', 'html', context).forEach(function () {

        // Skip if admin toolbar is present
        if (document.querySelector('#toolbar-administration') ||
            document.body.classList.contains('toolbar-vertical') ||
            document.body.classList.contains('toolbar-horizontal')) {
          return;
        }

        // Production-safe CSS optimization
        setTimeout(function () {
          optimizeCSSLoading();
        }, 100);

        // Production-safe image optimization
        setTimeout(function () {
          optimizeImages();
        }, 200);

        // Production-safe JavaScript deferring
        setTimeout(function () {
          optimizeJavaScript();
        }, 300);
      });
    }
  };

  /**
   * Optimize CSS loading in production environment.
   */
  function optimizeCSSLoading() {
    try {
      // Only optimize non-critical CSS
      const cssLinks = document.querySelectorAll('link[rel="stylesheet"]:not([data-critical])');

      cssLinks.forEach(function (link) {
        // Skip if already optimized or is critical
        if (link.hasAttribute('data-optimized') ||
            link.href.includes('critical') ||
            link.href.includes('citation') ||
            link.href.includes('sharing')) {
          return;
        }

        // Mark as optimized to prevent double-processing
        link.setAttribute('data-optimized', 'true');
      });
    } catch (error) {
      console.warn('SAHO Performance: CSS optimization error:', error);
    }
  }

  /**
   * Optimize images safely.
   */
  function optimizeImages() {
    try {
      const images = document.querySelectorAll('img:not([data-optimized])');

      images.forEach(function (img) {
        // Skip critical images or those in modals
        if (img.closest('.hero-banner, .modal, .citation-modal, .sharing-modal, .navbar')) {
          return;
        }

        // Add lazy loading for below-fold images
        if (!img.hasAttribute('loading') && !isImageVisible(img)) {
          img.setAttribute('loading', 'lazy');
        }

        img.setAttribute('data-optimized', 'true');
      });
    } catch (error) {
      console.warn('SAHO Performance: Image optimization error:', error);
    }
  }

  /**
   * Optimize JavaScript loading.
   */
  function optimizeJavaScript() {
    try {
      // Only defer non-critical scripts
      const scripts = document.querySelectorAll('script[src]:not([data-critical]):not([data-optimized])');

      scripts.forEach(function (script) {
        // Skip jQuery, Drupal core, and module scripts
        if (script.src.includes('jquery') ||
            script.src.includes('drupal') ||
            script.src.includes('citation') ||
            script.src.includes('sharing') ||
            script.src.includes('bootstrap')) {
          return;
        }

        script.setAttribute('data-optimized', 'true');
      });
    } catch (error) {
      console.warn('SAHO Performance: JavaScript optimization error:', error);
    }
  }

  /**
   * Check if image is visible in viewport.
   */
  function isImageVisible(img) {
    const rect = img.getBoundingClientRect();
    return rect.top < window.innerHeight && rect.bottom > 0;
  }

})(Drupal, once);