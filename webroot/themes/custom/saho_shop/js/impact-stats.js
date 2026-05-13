/**
 * @file
 * Animate the SAHO Shop "Why your support matters" impact-stat numbers
 * from 0 to their final value on scroll-in.
 *
 * The server-rendered HTML already contains the final value, so this is
 * pure progressive enhancement - if the JS never runs, or the user
 * prefers reduced motion, the correct number stays put. Animation is
 * one-shot per stat (the IntersectionObserver disconnects on first hit).
 */

((Drupal, once) => {
  const DURATION_MS = 1500;

  // Format with non-breaking thin space groupings to match SA convention
  // (e.g. "60 000") rather than the comma grouping toLocaleString gives.
  const formatNumber = (n) =>
    n.toLocaleString('en-ZA').replace(/,/g, ' ').replace(/ /g, ' ');

  const animateCount = (el, target, suffix) => {
    const start = performance.now();
    const tick = (now) => {
      const t = Math.min(1, (now - start) / DURATION_MS);
      // easeOutCubic - decelerates as it approaches the target
      const eased = 1 - Math.pow(1 - t, 3);
      el.textContent = formatNumber(Math.round(eased * target)) + suffix;
      if (t < 1) {
        window.requestAnimationFrame(tick);
      } else {
        // Final paint at the exact target value to avoid rounding drift.
        el.textContent = formatNumber(target) + suffix;
      }
    };
    window.requestAnimationFrame(tick);
  };

  Drupal.behaviors.sahoShopImpactStats = {
    attach(context) {
      const prefersReducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
      ).matches;

      // Reduced-motion users: leave the server-rendered final value
      // untouched and skip the animation entirely.
      if (prefersReducedMotion) {
        return;
      }

      // Older browsers without IntersectionObserver: also skip and leave
      // the final value in place.
      if (typeof window.IntersectionObserver === 'undefined') {
        return;
      }

      once('saho-impact-stat', '[data-count-target]', context).forEach((el) => {
        const target = parseInt(el.dataset.countTarget, 10);
        const suffix = el.dataset.countSuffix || '';
        if (Number.isNaN(target)) {
          return;
        }

        // Reset to 0 so the animation has somewhere to start from.
        el.textContent = '0' + suffix;

        const io = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                animateCount(el, target, suffix);
                io.disconnect();
              }
            });
          },
          {
            // Fire just after the stat enters the viewport so the animation
            // is visible (not playing out off-screen as the user scrolls in).
            rootMargin: '0px 0px -10% 0px',
            threshold: 0,
          }
        );

        io.observe(el);
      });
    },
  };
})(Drupal, once);
