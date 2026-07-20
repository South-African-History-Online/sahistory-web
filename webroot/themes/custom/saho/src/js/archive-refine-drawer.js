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

        const show = document.querySelector('[data-saho-refine-show]');

        // Live count: with autosubmit suppressed the server-rendered
        // "Show N records" freezes while selections change, so each change
        // re-fetches the narrowed count via the same lightweight saho_lite
        // response the load-more flow uses. The form serializes exactly as
        // a native submit would, so the count always matches what SHOW
        // will deliver.
        let countTimer = null;
        let countRequest = 0;
        const updateCount = () => {
          const form = drawerBody.querySelector('form');
          if (!form || !show) {
            return;
          }
          window.clearTimeout(countTimer);
          countTimer = window.setTimeout(() => {
            const params = new URLSearchParams(new FormData(form));
            params.set('saho_lite', '1');
            const action = form.getAttribute('action') || window.location.pathname;
            const request = ++countRequest;
            fetch(`${action}?${params.toString()}`, { headers: { Accept: 'text/html' } })
              .then((response) => response.text())
              .then((text) => {
                if (request !== countRequest) {
                  return; // A newer selection superseded this fetch.
                }
                const match = text.match(/([\d,]+)\s+records/i);
                show.textContent = match
                  ? Drupal.t('Show @count records', { '@count': match[1] })
                  : Drupal.t('Show records');
              })
              .catch(() => {
                show.textContent = Drupal.t('Show records');
              });
          }, 450);
        };

        // Capture-phase interception keeps BEF's change handlers from
        // firing (and full-page reloading) per checkbox tap while the
        // form is inside the drawer.
        drawerBody.addEventListener(
          'change',
          (event) => {
            if (MOBILE.matches) {
              event.stopPropagation();
              updateCount();
            }
          },
          true
        );

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
