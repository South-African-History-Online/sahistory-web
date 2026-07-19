/**
 * @file
 * SAHO results load-more - appends the next page of results on scroll.
 *
 * Progressive enhancement over the server pager: without JS the full pager
 * navigates as usual (and its links stay in the HTML for crawlers). With JS
 * the pager hides and a mono LOAD MORE control plus an auto-loading sentinel
 * take its place inside .saho-search-pager - the map layout hides that
 * container, so scroll-loading pauses there for free. Each fetch pulls the
 * next pager URL, extracts the same result containers from the response and
 * appends: table rows join the sortable index table, cards join the grid,
 * so the table/cards toggle stays in sync at any depth.
 */

((Drupal, once) => {
  const NEXT_SELECTOR =
    '.saho-search-pager li[class*="--next"] a, .saho-search-pager a[rel="next"]';

  function nextHref(root) {
    const link = root.querySelector(NEXT_SELECTOR);
    return link ? link.href : null;
  }

  function totalLabel(doc) {
    const el = doc.querySelector('.saho-landing-head__count, [data-saho-result-total]');
    return el ? el.textContent.trim() : '';
  }

  function appendResults(scope, sourceScope) {
    const tbody = scope.querySelector('.saho-results--table tbody');
    const grid = scope.querySelector('.saho-results--grid .saho-card-grid');
    let appended = 0;

    if (tbody) {
      sourceScope.querySelectorAll('.saho-results--table tbody tr').forEach((row) => {
        tbody.appendChild(document.importNode(row, true));
        appended++;
      });
    }
    if (grid) {
      const cards = sourceScope.querySelectorAll('.saho-results--grid .saho-card-grid > *');
      cards.forEach((card) => {
        grid.appendChild(document.importNode(card, true));
      });
      appended = Math.max(appended, cards.length);
    }
    return appended;
  }

  function loadedCount(scope) {
    const tbody = scope.querySelector('.saho-results--table tbody');
    if (tbody) {
      return tbody.rows.length;
    }
    const grid = scope.querySelector('.saho-results--grid .saho-card-grid');
    return grid ? grid.children.length : 0;
  }

  function initLoadMore(scope) {
    const pagerWrap = scope.querySelector('.saho-search-pager');
    if (!pagerWrap) {
      return;
    }
    const state = { next: nextHref(pagerWrap), busy: false };
    if (!state.next) {
      return;
    }

    pagerWrap.classList.add('is-enhanced');

    const controls = document.createElement('div');
    controls.className = 'saho-load-more';

    const status = document.createElement('p');
    status.className = 'saho-load-more__status';
    status.setAttribute('aria-live', 'polite');

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'saho-load-more__btn';
    button.textContent = Drupal.t('Load more');

    const sentinel = document.createElement('div');
    sentinel.className = 'saho-load-more__sentinel';
    sentinel.setAttribute('aria-hidden', 'true');

    controls.append(status, button, sentinel);
    pagerWrap.appendChild(controls);

    const total = totalLabel(document);
    const updateStatus = () => {
      const shown = loadedCount(scope);
      status.textContent = total
        ? Drupal.t('Showing @shown of @total', { '@shown': shown, '@total': total })
        : Drupal.t('@shown loaded', { '@shown': shown });
    };
    updateStatus();

    const finish = () => {
      observer.disconnect();
      button.remove();
      sentinel.remove();
      status.classList.add('is-end');
      status.textContent = Drupal.t('End of the register - @shown records', {
        '@shown': loadedCount(scope),
      });
    };

    const load = async () => {
      if (state.busy || !state.next) {
        return;
      }
      state.busy = true;
      button.disabled = true;
      button.textContent = Drupal.t('Loading…');
      try {
        const response = await fetch(state.next);
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
        const sourceScope = doc.querySelector('[data-saho-results]');
        if (!sourceScope || appendResults(scope, sourceScope) === 0) {
          finish();
          return;
        }
        state.next = nextHref(sourceScope);
        updateStatus();
        if (!state.next) {
          finish();
          return;
        }
        button.disabled = false;
        button.textContent = Drupal.t('Load more');
        // Re-observing fires an immediate notification with the current
        // state - without it the loop stalls whenever the sentinel never
        // left the observation margin during the append.
        observer.unobserve(sentinel);
        observer.observe(sentinel);
      } catch (_e) {
        // Network hiccup: leave the button armed for a manual retry.
        button.disabled = false;
        button.textContent = Drupal.t('Load more');
      } finally {
        state.busy = false;
      }
    };

    button.addEventListener('click', load);

    // Auto-load as the sentinel nears the viewport. A hidden pager container
    // (map layout) never intersects, pausing the loop until the reader
    // returns to table or cards.
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
          load();
        }
      },
      { rootMargin: '600px 0px' }
    );
    observer.observe(sentinel);
  }

  Drupal.behaviors.sahoLoadMore = {
    attach: (context) => {
      once('saho-load-more', '[data-saho-results]', context).forEach((scope) => {
        initLoadMore(scope);
      });
    },
  };
})(Drupal, once);
