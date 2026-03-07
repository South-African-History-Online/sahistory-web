/**
 * @file
 * SAHO Header scroll tracker.
 *
 * Tracks whether the nav bar has scrolled out of view and toggles
 * body.saho--scrolled accordingly. This controls desktop hamburger
 * visibility ONLY — no layout changes, zero CLS.
 *
 * The body padding-top: 60px offset lives in CSS (.saho-header-wrapper)
 * and never changes, so there is no Cumulative Layout Shift from this JS.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.sahoHeader = {
    attach: function (context) {
      once('saho-header', 'body', context).forEach(function (body) {
        // Skip for authenticated users — admin toolbar changes layout.
        if (body.classList.contains('user-logged-in')) {
          return;
        }

        var navBar = document.querySelector('.saho-nav-bar');
        if (!navBar) {
          return;
        }

        var navBarBottom = 0;
        var ticking = false;

        function getNavBarBottom() {
          navBarBottom = navBar.offsetTop + navBar.offsetHeight;
        }

        function update() {
          body.classList.toggle('saho--scrolled', window.scrollY >= navBarBottom);
        }

        // Recalculate nav bar position when viewport or content changes.
        var ro = new ResizeObserver(function () {
          getNavBarBottom();
          update();
        });
        ro.observe(navBar);

        window.addEventListener('scroll', function () {
          if (!ticking) {
            requestAnimationFrame(function () {
              update();
              ticking = false;
            });
            ticking = true;
          }
        }, { passive: true });

        // Set initial state synchronously before first paint.
        getNavBarBottom();
        update();
      });
    }
  };

})(Drupal, once);
