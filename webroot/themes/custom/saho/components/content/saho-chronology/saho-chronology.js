/**
 * @file
 * Decade-index scrollspy for the chronology register.
 *
 * An IntersectionObserver over decade-boundary entries highlights the
 * decade currently in view on the rail index. No scroll listeners.
 */
((Drupal, once) => {
  Drupal.behaviors.sahoChronology = {
    attach: (context) => {
      once('saho-chronology', '[data-saho-chronology]', context).forEach((root) => {
        const index = document.querySelector('[data-saho-chron-index]');
        if (!index) {
          return;
        }

        // At mobile the rail stacks BELOW the article, so the index
        // relocates above the chronology as the sticky decade strip
        // (R3 #477 / X-2) - same node, back again on desktop.
        const MOBILE = window.matchMedia('(max-width: 47.9375rem)');
        const home = { parent: index.parentElement, next: index.nextElementSibling };
        const place = () => {
          if (MOBILE.matches) {
            root.parentElement.insertBefore(index, root);
          } else {
            home.parent.insertBefore(index, home.next);
          }
        };
        place();
        MOBILE.addEventListener('change', place);
        const links = new Map(
          [...index.querySelectorAll('.saho-chron-index__link')].map((a) => [
            a.getAttribute('data-decade'),
            a,
          ])
        );
        const entries = [...root.querySelectorAll('[data-decade]')];
        if (!entries.length) {
          return;
        }

        const setCurrent = (decade) => {
          links.forEach((a, key) => {
            a.classList.toggle('is-current', key === decade);
          });
        };

        // The topmost visible entry wins; rootMargin biases to the band
        // just below the viewport top so the highlight tracks reading.
        const visible = new Set();
        const observer = new IntersectionObserver(
          (records) => {
            records.forEach((r) => {
              if (r.isIntersecting) {
                visible.add(r.target);
              } else {
                visible.delete(r.target);
              }
            });
            const top = entries.find((e) => visible.has(e));
            if (top) {
              setCurrent(top.getAttribute('data-decade'));
            }
          },
          { rootMargin: '-10% 0px -70% 0px' }
        );
        entries.forEach((e) => {
          observer.observe(e);
        });
      });
    },
  };
})(Drupal, once);
