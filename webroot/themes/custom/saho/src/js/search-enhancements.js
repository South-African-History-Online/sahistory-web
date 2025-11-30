/**
 * @file
 * SAHO search enhancements.
 *
 * Improves search functionality for mobile devices and ensures
 * proper interaction with search button and Tools dropdown.
 */

(() => {
  document.addEventListener('DOMContentLoaded', () => {
    enhanceMobileSearch();
    fixToolsDropdown();
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
        searchInput.setAttribute('autocomplete', 'off');
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
})();
