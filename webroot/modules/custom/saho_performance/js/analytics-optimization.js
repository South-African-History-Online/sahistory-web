(function (Drupal, once) {
  'use strict';

  /**
   * Optimize Google Analytics loading for better performance.
   */
  Drupal.behaviors.sahoAnalyticsOptimization = {
    attach: function (context, settings) {

      // Defer Google Analytics until user interaction or page load complete
      once('saho-analytics-defer', 'html', context).forEach(function () {

        // Check if GA is already loaded
        if (window.gtag || window.ga || document.querySelector('[src*="googletagmanager"]')) {
          return;
        }

        let analyticsLoaded = false;

        // Function to load analytics
        function loadAnalytics() {
          if (analyticsLoaded) return;
          analyticsLoaded = true;

          // Create the GA script tag
          const script = document.createElement('script');
          script.async = true;
          script.src = 'https://www.googletagmanager.com/gtag/js?id=G-W91HQEGETK';
          document.head.appendChild(script);

          // Initialize gtag when script loads
          script.onload = function() {
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-W91HQEGETK', {
              send_page_view: true
            });
            window.gtag = gtag;
          };
        }

        // Load analytics on user interaction
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];

        function triggerAnalytics() {
          loadAnalytics();
          // Remove event listeners after first interaction
          events.forEach(function(event) {
            document.removeEventListener(event, triggerAnalytics, {passive: true});
          });
        }

        // Add event listeners for user interaction
        events.forEach(function(event) {
          document.addEventListener(event, triggerAnalytics, {passive: true});
        });

        // Fallback: load after 10 seconds if no interaction
        setTimeout(function() {
          if (!analyticsLoaded) {
            loadAnalytics();
          }
        }, 10000);
      });
    }
  };

})(Drupal, once);