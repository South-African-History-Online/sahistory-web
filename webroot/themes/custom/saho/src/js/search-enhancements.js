/**
 * @file
 * SAHO search enhancements (#453).
 *
 * The search-field SDC is a plain GET form - native submission does the
 * work. This behavior only decorates: mobile keyboard hints, an
 * empty-submit guard, the Cmd/Ctrl+K shortcut, and the search-modal
 * popular/recent chips (localStorage).
 */

((Drupal, once) => {
  const STORAGE_KEY = 'sahoSearchHistory';
  const MAX_HISTORY = 5;

  Drupal.behaviors.sahoSearchEnhancements = {
    attach(context) {
      // Decorate the SDC search forms: mobile hints + empty-submit guard.
      // Native submits stay native so real `submit` events keep firing.
      once('saho-search-form', '.saho-search__form', context).forEach((form) => {
        const input = form.querySelector('input[name="search_api_fulltext"]');
        if (!input) {
          return;
        }
        input.setAttribute('enterkeyhint', 'search');
        input.setAttribute('autocapitalize', 'none');
        form.addEventListener('submit', (e) => {
          if (input.value.trim() === '') {
            e.preventDefault();
            input.focus();
          }
        });
      });

      // Cmd/Ctrl+K opens the search modal from anywhere. `bootstrap` is a
      // bundled global (src/js/_bootstrap.js in the deferred bundle), not an
      // attachable library - feature-detect with a trigger-click fallback.
      once('saho-search-cmdk', 'body', context).forEach(() => {
        document.addEventListener('keydown', (e) => {
          if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            const modalEl = document.getElementById('mobileSearchModal');
            if (!modalEl) {
              return;
            }
            if (typeof bootstrap !== 'undefined') {
              bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else {
              document.querySelector('[data-bs-target="#mobileSearchModal"]')?.click();
            }
          }
        });
      });

      // Search modal: chips + history.
      once('saho-search-modal', '#mobileSearchModal', context).forEach((modal) => {
        initSearchModal(modal);
      });
    },
  };

  /**
   * Initialise the search modal: chip rendering + history saving.
   *
   * @param {HTMLElement} modal
   *   The #mobileSearchModal element.
   */
  function initSearchModal(modal) {
    // Swap the Twig fallback chips for live data at attach time - waiting
    // for the modal to open makes the fallback flash before the jump.
    renderPopularChips();
    renderRecentChips();

    // Re-render on open so recent chips stay fresh within a page's life.
    modal.addEventListener('shown.bs.modal', () => {
      renderPopularChips();
      renderRecentChips();
    });

    // One history saver: the native (bubbling) submit event covers Enter,
    // the submit button and the mobile "go" key alike.
    const form = document.getElementById('sahoSearchModalForm');
    const modalInput = form ? form.querySelector('input[name="search_api_fulltext"]') : null;
    if (form && modalInput) {
      form.addEventListener('submit', () => {
        if (modalInput.value.trim()) {
          saveSearchQuery(modalInput.value.trim());
        }
      });
    }

    // Recent chip click: navigate to the stored query.
    const recentChips = document.getElementById('sahoSearchRecentChips');
    if (recentChips) {
      recentChips.addEventListener('click', (e) => {
        const chip = e.target.closest('.saho-search-chip--recent[data-query]');
        if (!chip) {
          return;
        }
        e.preventDefault();
        const query = chip.dataset.query;
        if (modalInput) {
          modalInput.value = query;
        }
        window.location.href = `/search?search_api_fulltext=${encodeURIComponent(query)}`;
      });
    }
  }

  /**
   * Persist a search query to localStorage (max 5, most-recent first).
   *
   * @param {string} query
   *   The search query to store.
   */
  function saveSearchQuery(query) {
    if (!query) {
      return;
    }
    let history = loadHistory();
    history = history.filter((q) => q.toLowerCase() !== query.toLowerCase());
    history.unshift(query);
    history = history.slice(0, MAX_HISTORY);
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(history));
    } catch (_) {
      // localStorage unavailable (private mode etc.) - silently skip.
    }
  }

  /**
   * Load search history from localStorage.
   *
   * @return {string[]}
   *   The stored queries, most recent first.
   */
  function loadHistory() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
          return parsed.filter((q) => typeof q === 'string' && q.length > 0);
        }
      }
    } catch (_) {
      // Ignore parse errors.
    }
    return [];
  }

  /**
   * Replace popular-search chips with real data from drupalSettings.
   *
   * If drupalSettings.sahoSearch.popularSearches is populated (by
   * saho_statistics), swap out the hardcoded Twig chips. If empty, the
   * Twig fallback chips remain untouched.
   */
  function renderPopularChips() {
    const popular =
      (typeof drupalSettings !== 'undefined' &&
        drupalSettings.sahoSearch &&
        drupalSettings.sahoSearch.popularSearches) ||
      [];

    if (popular.length === 0) {
      return;
    }

    const container = document.getElementById('sahoPopularChips');
    if (!container) {
      return;
    }

    container.innerHTML = popular
      .map(
        (item) =>
          `<a href="${escapeAttr(item.url)}" class="saho-search-chip">${escapeHtml(item.label)}</a>`
      )
      .join('');
  }

  /**
   * Render recent-search chips from localStorage into the modal.
   */
  function renderRecentChips() {
    const container = document.getElementById('sahoSearchRecent');
    const chipsEl = document.getElementById('sahoSearchRecentChips');
    if (!container || !chipsEl) {
      return;
    }

    const history = loadHistory();
    if (history.length === 0) {
      container.hidden = true;
      return;
    }

    chipsEl.innerHTML = history
      .map(
        (q) =>
          `<button type="button" class="saho-search-chip saho-search-chip--recent" data-query="${escapeAttr(q)}" aria-label="Search for ${escapeAttr(q)}">` +
          `${escapeHtml(q)}` +
          `<span class="saho-chip-dismiss" aria-hidden="true">×</span>` +
          '</button>'
      )
      .join('');

    container.hidden = false;
  }

  /**
   * Escape a string for use in HTML attribute values.
   *
   * @param {string} str
   *   The raw string.
   *
   * @return {string}
   *   The escaped string.
   */
  function escapeAttr(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  /**
   * Escape a string for use in HTML text content.
   *
   * @param {string} str
   *   The raw string.
   *
   * @return {string}
   *   The escaped string.
   */
  function escapeHtml(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
})(Drupal, once);
