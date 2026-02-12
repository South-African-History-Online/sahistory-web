/**
 * @file
 * SAHO Sticky Header - Modern scroll behavior
 * Shows header when scrolling up, hides when scrolling down
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.sahoStickyHeader = {
    attach: function (context) {
      once('saho-sticky-header', '.saho-header', context).forEach(function (header) {

        // Disable sticky header for authenticated users (they need admin toolbar access)
        // Check for admin toolbar or toolbar-administration elements
        const hasToolbar = document.getElementById('toolbar-administration') ||
                          document.querySelector('.toolbar') ||
                          document.querySelector('#toolbar-bar') ||
                          document.body.classList.contains('toolbar-fixed') ||
                          document.body.classList.contains('toolbar-horizontal') ||
                          document.body.classList.contains('toolbar-vertical') ||
                          document.body.classList.contains('user-logged-in');

        if (hasToolbar) {
          return;
        }

        let lastScrollY = window.scrollY;
        let ticking = false;
        const scrollThreshold = 5; // Minimum scroll distance to trigger
        const headerHeight = header.offsetHeight;

        // Add initial classes
        header.classList.add('saho-header--sticky');

        /**
         * Update header state based on scroll position and direction
         */
        function updateHeaderState(scrollY) {
          const scrollDelta = scrollY - lastScrollY;

          // Only update if scroll exceeds threshold
          if (Math.abs(scrollDelta) < scrollThreshold) {
            return;
          }

          // Scrolling down - hide header
          if (scrollDelta > 0 && scrollY > headerHeight) {
            header.classList.add('saho-header--hidden');
            header.classList.remove('saho-header--visible');
          }
          // Scrolling up - show header
          else if (scrollDelta < 0) {
            header.classList.remove('saho-header--hidden');
            header.classList.add('saho-header--visible');
          }

          // At top of page - always show header
          if (scrollY <= headerHeight) {
            header.classList.remove('saho-header--hidden', 'saho-header--visible');
            header.classList.add('saho-header--at-top');
          } else {
            header.classList.remove('saho-header--at-top');
          }

          lastScrollY = scrollY;
        }

        /**
         * Request animation frame for smooth 60fps updates
         */
        function requestTick() {
          if (!ticking) {
            window.requestAnimationFrame(function() {
              updateHeaderState(window.scrollY);
              ticking = false;
            });
            ticking = true;
          }
        }

        /**
         * Passive scroll event listener for best performance
         */
        window.addEventListener('scroll', requestTick, { passive: true });

        /**
         * Handle mobile menu opening - always show header when menu opens
         */
        const mobileMenuTrigger = document.getElementById('sahoMobileMenu');
        if (mobileMenuTrigger) {
          mobileMenuTrigger.addEventListener('show.bs.offcanvas', function() {
            header.classList.remove('saho-header--hidden');
            header.classList.add('saho-header--visible');
          });
        }

        /**
         * Recalculate header height on window resize
         */
        let resizeTimeout;
        window.addEventListener('resize', function() {
          clearTimeout(resizeTimeout);
          resizeTimeout = setTimeout(function() {
            // Recalculate if needed
            updateHeaderState(window.scrollY);
          }, 250);
        }, { passive: true });

        // Initial state
        updateHeaderState(window.scrollY);
      });
    }
  };

})(Drupal, once);
