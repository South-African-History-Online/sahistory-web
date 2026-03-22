/**
 * @file
 * SAHO search enhancements.
 *
 * Improves search functionality for mobile devices and ensures
 * proper interaction with search button and Tools dropdown.
 * Also manages popular/recent search chips in the search modal.
 */

(() => {
  document.addEventListener('DOMContentLoaded', () => {
    enhanceMobileSearch();
    fixToolsDropdown();
    initSearchModal();

    // ⌘K / Ctrl+K → open search modal from anywhere
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

  /**
   * Enhances search functionality for mobile devices.
   *
   * Ensures search button works properly and Enter key submits the search form.
   */
  function enhanceMobileSearch() {
    // Get all search forms on the page
    const searchForms = document.querySelectorAll(
      'form[role="search"], .saho-search-form, .saho-mobile-search'
    );

    searchForms.forEach((form) => {
      // Find search input and button in this form
      const searchInput = form.querySelector(
        'input[type="search"], input[name="search_api_fulltext"]'
      );
      const searchButton = form.querySelector('button[type="submit"]');

      if (searchInput) {
        // Ensure form submits when Enter key is pressed
        searchInput.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') {
            e.preventDefault();

            // Only submit if there is actual search text
            if (searchInput.value.trim() !== '') {
              // Submit the form directly (avoids event propagation issues)
              form.submit();
            }
          }
        });

        // Make search input mobile-friendly
        searchInput.setAttribute('enterkeyhint', 'search');
        searchInput.setAttribute('autocapitalize', 'none');
      }

      if (searchButton) {
        // Fix for search button click on mobile
        searchButton.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          // Get the input from this button's form
          const input = this.closest('form').querySelector(
            'input[type="search"], input[name="search_api_fulltext"]'
          );

          // Only submit if there's search text
          if (input && input.value.trim() !== '') {
            // Direct form submission
            this.closest('form').submit();
          } else if (input) {
            // Focus input if empty
            input.focus();
          }
        });

        // Add touchend handler for iOS/Safari compatibility
        searchButton.addEventListener(
          'touchend',
          function (e) {
            e.preventDefault();
            const form = this.closest('form');
            const input = form.querySelector(
              'input[type="search"], input[name="search_api_fulltext"]'
            );

            if (input && input.value.trim() !== '') {
              form.submit();
            } else if (input) {
              input.focus();
            }
          },
          { passive: false }
        );
      }
    });

    // Fix for main search box in the header (might have different selectors)
    const headerSearch = document.querySelector('.saho-search, .saho-header .search-form');
    if (headerSearch) {
      const headerInput = headerSearch.querySelector(
        'input[type="search"], input[name="search_api_fulltext"]'
      );
      const headerButton = headerSearch.querySelector('button[type="submit"]');

      if (headerInput && headerButton) {
        // Focus input when button is clicked with empty input
        headerButton.addEventListener('click', (e) => {
          if (headerInput.value.trim() === '') {
            e.preventDefault();
            headerInput.focus();
          }
        });
      }
    }
  }

  /**
   * Tools dropdown - let Bootstrap handle it natively.
   *
   * Bootstrap 5 dropdowns work correctly out of the box.
   * We only need to ensure the dropdown is properly initialized.
   */
  function fixToolsDropdown() {
    // Nothing to do - Bootstrap handles this via data-bs-toggle="dropdown"
    // Just ensure Bootstrap is loaded and will auto-initialize
  }

  // ── Search modal: popular chips + recent searches (localStorage) ────────

  const STORAGE_KEY = 'sahoSearchHistory';
  const MAX_HISTORY = 5;

  /**
   * Initialise search modal with recent-search behaviour.
   */
  function initSearchModal() {
    const modal = document.getElementById('mobileSearchModal');
    if (!modal) {
      return;
    }

    // Render chips whenever the modal opens
    modal.addEventListener('shown.bs.modal', () => {
      renderPopularChips();
      renderRecentChips();
    });

    // Save query to history before navigation.
    // Use capture phase so these fire BEFORE enhanceMobileSearch's handlers,
    // which call form.submit() directly (bypassing the submit event).
    const form = document.getElementById('sahoSearchModalForm');
    if (form) {
      const modalInput = form.querySelector('input[name="search_api_fulltext"]');

      // Capture Enter keypress on the input.
      if (modalInput) {
        modalInput.addEventListener(
          'keydown',
          (e) => {
            if (e.key === 'Enter' && modalInput.value.trim()) {
              saveSearchQuery(modalInput.value.trim());
            }
          },
          true // capture phase — fires before bubbling handlers
        );
      }

      // Capture submit button click.
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.addEventListener(
          'click',
          () => {
            if (modalInput?.value.trim()) {
              saveSearchQuery(modalInput.value.trim());
            }
          },
          true // capture phase
        );
      }

      // Also catch native form submit as a safety net.
      form.addEventListener('submit', () => {
        if (modalInput?.value.trim()) {
          saveSearchQuery(modalInput.value.trim());
        }
      });
    }

    // Chip click inside recent panel: fill input and submit
    const recentChips = document.getElementById('sahoSearchRecentChips');
    if (recentChips) {
      recentChips.addEventListener('click', (e) => {
        const chip = e.target.closest('.saho-search-chip--recent[data-query]');
        if (!chip) {
          return;
        }
        e.preventDefault();
        const query = chip.dataset.query;
        const input = modal.querySelector('input[name="search_api_fulltext"]');
        if (input) {
          input.value = query;
        }
        // Navigate to search results
        window.location.href = `/search?search_api_fulltext=${encodeURIComponent(query)}`;
      });
    }
  }

  /**
   * Persist a search query to localStorage (max 5, most-recent first).
   *
   * @param {string} query
   */
  function saveSearchQuery(query) {
    if (!query) {
      return;
    }
    let history = loadHistory();
    // Remove duplicate if already present, then prepend
    history = history.filter((q) => q.toLowerCase() !== query.toLowerCase());
    history.unshift(query);
    history = history.slice(0, MAX_HISTORY);
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(history));
    } catch (_) {
      // localStorage unavailable (private mode etc.) — silently skip
    }
  }

  /**
   * Load search history from localStorage.
   *
   * @return {string[]}
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
      // Ignore parse errors
    }
    return [];
  }

  /**
   * Replace popular-search chips with real data from drupalSettings.
   *
   * If drupalSettings.sahoSearch.popularSearches is populated (by
   * saho_statistics_page_attachments()), swap out the hardcoded Twig chips
   * with live data. If empty (no tracking data yet), the Twig fallback chips
   * remain untouched.
   */
  function renderPopularChips() {
    const popular =
      (typeof drupalSettings !== 'undefined' &&
        drupalSettings.sahoSearch &&
        drupalSettings.sahoSearch.popularSearches) ||
      [];

    if (popular.length === 0) {
      // No real data yet — keep Twig fallback chips as-is.
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
          `<span aria-hidden="true" style="opacity:0.6;font-size:0.75rem;">×</span>` +
          '</button>'
      )
      .join('');

    container.hidden = false;
  }

  /**
   * Escape a string for use in HTML attribute values.
   *
   * @param {string} str
   * @return {string}
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
   * @return {string}
   */
  function escapeHtml(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
})();
