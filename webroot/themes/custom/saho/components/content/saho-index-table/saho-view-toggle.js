/**
 * @file
 * SAHO results view toggle - switches search results between the index
 * table and the card grid.
 *
 * Markup contract: a wrapper [data-saho-view-toggle] containing
 * button[role="tab"][data-view="<name>"] tabs, with a result container
 * .saho-results--<name> per tab inside the closest [data-saho-results]
 * ancestor (document as fallback). The available views are derived from
 * the buttons present (table/grid everywhere; map on landings that embed
 * one). The chosen layout persists in localStorage and in the "layout"
 * URL query param; a stored choice only applies where its tab exists.
 */

((Drupal, once) => {
  const STORAGE_KEY = 'saho.resultsLayout';

  /**
   * Reads the stored layout choice; null in private mode or when unset.
   */
  function readStoredView() {
    try {
      return window.localStorage.getItem(STORAGE_KEY);
    } catch (_e) {
      return null;
    }
  }

  /**
   * Persists the layout choice; silently ignored in private mode.
   */
  function storeView(view) {
    try {
      window.localStorage.setItem(STORAGE_KEY, view);
    } catch (_e) {
      // localStorage unavailable (private mode); nothing to do.
    }
  }

  /**
   * Reflects the layout in the URL so copied links keep it (no reload).
   */
  function updateUrl(view) {
    try {
      const url = new URL(window.location.href);
      url.searchParams.set('layout', view);
      window.history.replaceState(window.history.state, '', url);
    } catch (_e) {
      // history or URL unavailable; the toggle still works without it.
    }
  }

  /**
   * Returns the view names this toggle offers (from its buttons).
   */
  function availableViews(toggle) {
    return Array.from(toggle.querySelectorAll('button[data-view]')).map((button) =>
      button.getAttribute('data-view')
    );
  }

  /**
   * Shows the container matching the view and hides the others.
   */
  function applyView(toggle, view) {
    const scope = toggle.closest('[data-saho-results]') || document;

    toggle.querySelectorAll('button[data-view]').forEach((button) => {
      const active = button.getAttribute('data-view') === view;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    availableViews(toggle).forEach((name) => {
      const container = scope.querySelector(`.saho-results--${name}`);
      if (!container) {
        return;
      }
      if (name === view) {
        container.removeAttribute('hidden');
      } else {
        container.setAttribute('hidden', '');
      }
    });

    // The pager drives the paged layouts only; the map is unpaged.
    const pager = scope.querySelector('.saho-search-pager');
    if (pager) {
      if (view === 'map') {
        pager.setAttribute('hidden', '');
      } else {
        pager.removeAttribute('hidden');
      }
    }

    // Leaflet sizes itself at init; a map revealed from a hidden pane has a
    // zero-size viewport until it hears a resize.
    if (view === 'map') {
      window.setTimeout(() => {
        window.dispatchEvent(new Event('resize'));
      }, 0);
    }
  }

  /**
   * Returns the server-rendered default view for a toggle.
   */
  function defaultView(toggle) {
    const active = toggle.querySelector(
      'button[data-view].is-active, button[data-view][aria-selected="true"]'
    );
    return active ? active.getAttribute('data-view') : 'table';
  }

  /**
   * True when the result container for a view exists in this scope.
   *
   * The map pane only renders under ?layout=map (it is megabytes of
   * location markup) - its tab can exist without its pane.
   */
  function paneExists(toggle, view) {
    const scope = toggle.closest('[data-saho-results]') || document;
    return !!scope.querySelector(`.saho-results--${view}`);
  }

  /**
   * Wires one toggle: restores the stored choice, then handles clicks.
   */
  function initToggle(toggle) {
    const views = availableViews(toggle);
    const stored = readStoredView();
    // A stored choice only auto-applies when its pane is present - a
    // remembered "map" must never navigate the reader by surprise.
    if (views.includes(stored) && stored !== defaultView(toggle) && paneExists(toggle, stored)) {
      applyView(toggle, stored);
    }

    toggle.querySelectorAll('button[data-view]').forEach((button) => {
      button.addEventListener('click', () => {
        const view = button.getAttribute('data-view');
        if (!views.includes(view)) {
          return;
        }
        if (!paneExists(toggle, view)) {
          // Server-rendered layout: navigate, keeping the active filters.
          try {
            const url = new URL(window.location.href);
            url.searchParams.set('layout', view);
            window.location.assign(url);
          } catch (_e) {
            window.location.search = `layout=${view}`;
          }
          return;
        }
        applyView(toggle, view);
        storeView(view);
        updateUrl(view);
      });
    });
  }

  Drupal.behaviors.sahoViewToggle = {
    attach: (context) => {
      once('saho-view-toggle', '[data-saho-view-toggle]', context).forEach((toggle) => {
        initToggle(toggle);
      });
    },
  };
})(Drupal, once);
