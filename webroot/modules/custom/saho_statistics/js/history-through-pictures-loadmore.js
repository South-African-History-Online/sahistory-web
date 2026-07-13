/**
 * @file
 * Load-more-on-scroll for the History Through Pictures grid.
 *
 * Progressive enhancement over the pager: JS swaps it for a mono LOAD
 * MORE button that auto-fires when it nears the viewport. Each step
 * fetches the pager's own next-page URL, appends that page's grid tiles
 * and re-attaches behaviors (lightbox reload, broken-image guard). The
 * pager stays in the markup for no-JS readers and crawlers.
 */
(function (Drupal, once) {
  Drupal.behaviors.historyThroughPicturesLoadMore = {
    attach: function (context) {
      once('htp-loadmore', '[data-htp-loadmore]', context).forEach(function (wrap) {
        const grid = document.querySelector('.history-pictures-grid--gallery');
        const pager = document.querySelector('.gallery-pager');
        if (!grid || !pager) {
          return;
        }
        const nextLink = () =>
          pager.querySelector('.pager__item--next a, a[rel="next"]');
        if (!nextLink()) {
          return;
        }

        pager.classList.add('is-enhanced');
        wrap.hidden = false;
        const button = wrap.querySelector('[data-htp-loadmore-button]');
        const status = wrap.querySelector('[data-htp-loadmore-status]');
        let busy = false;

        const load = () => {
          const next = nextLink();
          if (busy || !next) {
            return;
          }
          busy = true;
          button.disabled = true;
          status.textContent = Drupal.t('Loading…');
          fetch(next.href)
            .then((r) => {
              if (!r.ok) {
                throw new Error(r.status);
              }
              return r.text();
            })
            .then((html) => {
              const doc = new DOMParser().parseFromString(html, 'text/html');
              const tiles = doc.querySelectorAll(
                '.history-pictures-grid--gallery .history-picture-item'
              );
              const added = document.createDocumentFragment();
              tiles.forEach((tile) => {
                added.appendChild(document.importNode(tile, true));
              });
              grid.appendChild(added);
              // The fetched page's pager tells us whether more remains.
              const newPager = doc.querySelector('.gallery-pager');
              pager.innerHTML = newPager ? newPager.innerHTML : '';
              Drupal.attachBehaviors(grid);
              busy = false;
              button.disabled = false;
              status.textContent = '';
              if (!nextLink()) {
                button.hidden = true;
                status.textContent = Drupal.t('End of the record.');
              }
            })
            .catch(() => {
              // Fall back to plain navigation on any fetch trouble.
              const link = nextLink();
              if (link) {
                window.location.href = link.href;
              }
            });
        };

        button.addEventListener('click', load);
        // Auto-fire when the button approaches the viewport; the button
        // stays as the accessible, explicit control.
        new IntersectionObserver(
          (entries) => {
            if (entries[0].isIntersecting) {
              load();
            }
          },
          { rootMargin: '600px 0px' }
        ).observe(wrap);
      });
    }
  };
})(Drupal, once);
