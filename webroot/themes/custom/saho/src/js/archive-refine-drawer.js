/**
 * @file
 * Mobile refine drawer for /archives (R3 #475).
 *
 * At the 40rem breakpoint the refine rail relocates INTO the offcanvas
 * drawer (same DOM node - no duplicate form, no duplicate ids) and back
 * again on desktop. BEF autosubmit is suppressed while the form lives in
 * the drawer so a facet tap does not reload the page mid-selection; the
 * SHOW button applies everything at once.
 */
((Drupal, once) => {
  const MOBILE = window.matchMedia('(max-width: 39.9375rem)');

  Drupal.behaviors.sahoArchiveRefineDrawer = {
    attach: (context) => {
      once('saho-refine-drawer', '[data-saho-refine-source]', context).forEach((rail) => {
        const drawerBody = document.querySelector('[data-saho-refine-body]');
        if (!drawerBody) {
          return;
        }
        const layout = rail.parentElement;

        const place = () => {
          if (MOBILE.matches) {
            drawerBody.appendChild(rail);
          } else if (rail.parentElement !== layout) {
            layout.insertBefore(rail, layout.firstElementChild);
          }
        };
        place();
        MOBILE.addEventListener('change', place);

        // Capture-phase interception keeps BEF's change handlers from
        // firing (and full-page reloading) per checkbox tap while the
        // form is inside the drawer.
        drawerBody.addEventListener(
          'change',
          (event) => {
            if (MOBILE.matches) {
              event.stopPropagation();
            }
          },
          true
        );

        const show = document.querySelector('[data-saho-refine-show]');
        if (show) {
          show.addEventListener('click', () => {
            const form = drawerBody.querySelector('form');
            if (form) {
              form.submit();
            }
          });
        }
      });
    },
  };
})(Drupal, once);
