/**
 * @file
 * SAHO results view toggle - switches search results between the index
 * table and the card grid.
 *
 * Markup contract: a wrapper [data-saho-view-toggle] containing two
 * button[role="tab"][data-view="table|grid"], with result containers
 * .saho-results--table and .saho-results--grid inside the closest
 * [data-saho-results] ancestor (document as fallback). The chosen layout
 * persists in localStorage and in the "layout" URL query param.
 */

((Drupal, once) => {
  const STORAGE_KEY = 'saho.resultsLayout';
  const VIEWS = ['table', 'grid'];

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
   * Shows the container matching the view and hides the other.
   */
  function applyView(toggle, view) {
    const scope = toggle.closest('[data-saho-results]') || document;
    const containers = {
      table: scope.querySelector('.saho-results--table'),
      grid: scope.querySelector('.saho-results--grid'),
    };

    toggle.querySelectorAll('button[data-view]').forEach((button) => {
      const active = button.getAttribute('data-view') === view;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    VIEWS.forEach((name) => {
      const container = containers[name];
      if (!container) {
        return;
      }
      if (name === view) {
        container.removeAttribute('hidden');
      } else {
        container.setAttribute('hidden', '');
      }
    });
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
   * Wires one toggle: restores the stored choice, then handles clicks.
   */
  function initToggle(toggle) {
    const stored = readStoredView();
    if (VIEWS.includes(stored) && stored !== defaultView(toggle)) {
      applyView(toggle, stored);
    }

    toggle.querySelectorAll('button[data-view]').forEach((button) => {
      button.addEventListener('click', () => {
        const view = button.getAttribute('data-view');
        if (!VIEWS.includes(view)) {
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
