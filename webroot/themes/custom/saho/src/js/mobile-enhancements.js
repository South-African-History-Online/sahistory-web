/**
 * @file
 * Cross-device enhancements for SAHO site.
 *
 * Provides fixes for search functionality and Tools dropdown
 * that work across both mobile and desktop devices.
 */

(() => {
  // Detect if we're on a touch device (must be declared before use)
  const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

  // Execute as soon as DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEnhancements);
  } else {
    initEnhancements();
  }

  /**
   * Initialize all enhancements for both mobile and desktop.
   */
  function initEnhancements() {
    // Fix for search form functionality
    fixSearchForms();

    // Fix for Tools dropdown on all devices
    fixToolsDropdown();

    // Add mutation observer to handle dynamically loaded content
    setupMutationObserver();
  }

  /**
   * Fixes search form submission across all devices.
   * Enhanced to handle all search forms across the site.
   */
  function fixSearchForms() {
    // Target all possible search forms across the site
    const searchFormSelectors = [
      'form[role="search"]',
      '.saho-search',
      '.saho-mobile-search',
      'form[action="/search"]',
      '.search-form',
      '.saho-search-form',
    ];

    const searchForms = document.querySelectorAll(searchFormSelectors.join(', '));

    searchForms.forEach((form) => {
      // Skip forms we've already processed
      if (form.hasAttribute('data-enhanced')) {
        return;
      }

      const searchInput = form.querySelector(
        'input[type="search"], input[type="text"][name="search_api_fulltext"]'
      );
      const searchButton = form.querySelector('button[type="submit"], input[type="submit"]');

      if (searchInput) {
        // For all devices: Ensure empty searches don't submit
        searchInput.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') {
            if (searchInput.value.trim() === '') {
              e.preventDefault();
              e.stopPropagation();
              return false;
            }
          }
        });

        // For touch devices: Add extra handling for iOS keyboard issues
        if (isTouchDevice) {
          searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
              // For iOS, blur the input to hide keyboard before submission
              searchInput.blur();

              // Let the form submit naturally on desktop
              // On mobile, sometimes we need to force it
              if (searchInput.value.trim() !== '') {
                // Small delay for iOS
                setTimeout(() => {
                  // Only manually submit if the form hasn't already submitted
                  if (!form.classList.contains('submitted')) {
                    form.classList.add('submitted');
                    form.submit();
                  }
                }, 10);
              }
            }
          });

          // Make sure search input has proper attributes for mobile
          searchInput.setAttribute('enterkeyhint', 'search');
          searchInput.setAttribute('autocapitalize', 'off');
        }
      }

      if (searchButton) {
        // For desktop: Let the button work normally
        // For touch: Add extra handling
        if (isTouchDevice) {
          // Add touchend event listener for mobile devices
          searchButton.addEventListener(
            'touchend',
            function (e) {
              const input = this.closest('form').querySelector(
                'input[type="search"], input[type="text"][name="search_api_fulltext"]'
              );

              // Allow empty searches to focus the input
              if (input && input.value.trim() === '') {
                e.preventDefault();
                input.focus();
                return false;
              }

              // For iOS, blur the input to hide keyboard
              if (input) {
                input.blur();
              }

              // Don't prevent default on desktop - let the normal form submission happen
              // On mobile we need special handling
              if (isTouchDevice && input && input.value.trim() !== '') {
                e.preventDefault();

                // Small delay for iOS
                setTimeout(() => {
                  // Only manually submit if the form hasn't already submitted
                  if (!form.classList.contains('submitted')) {
                    form.classList.add('submitted');
                    form.submit();
                  }
                }, 10);
              }
            },
            { passive: false }
          );
        }

        // For all devices: Ensure button clicks submit the form correctly
        searchButton.addEventListener('click', function (e) {
          const input = this.closest('form').querySelector(
            'input[type="search"], input[type="text"][name="search_api_fulltext"]'
          );

          // Allow empty searches to focus the input
          if (input && input.value.trim() === '') {
            e.preventDefault();
            input.focus();
            return false;
          }

          // On desktop, let the form submit naturally
          // No need to prevent default
        });
      }

      // Add a reset handler to clear the submitted flag
      form.addEventListener('reset', () => {
        form.classList.remove('submitted');
      });

      // Mark form as processed
      form.setAttribute('data-enhanced', 'true');
    });
  }

  /**
   * Fixes Tools dropdown functionality on all devices.
   * Enhanced to handle any dropdown with data-bs-toggle="dropdown".
   */
  function fixToolsDropdown() {
    // Target both specific Tools dropdown and any dropdown toggles
    const dropdownToggles = document.querySelectorAll(
      '.tools-dropdown, [data-bs-toggle="dropdown"]'
    );

    dropdownToggles.forEach((toggle) => {
      // Skip toggles we've already processed
      if (toggle.hasAttribute('data-enhanced')) {
        return;
      }

      // Find the dropdown menu
      let dropdownMenu;

      // Check if the toggle itself is the dropdown button
      if (
        toggle.hasAttribute('data-bs-toggle') &&
        toggle.getAttribute('data-bs-toggle') === 'dropdown'
      ) {
        // Find menu by aria-labelledby if available
        if (toggle.id) {
          dropdownMenu = document.querySelector(`[aria-labelledby="${toggle.id}"]`);
        }

        // If not found, try to find the next sibling that's a dropdown menu
        if (!dropdownMenu) {
          let sibling = toggle.nextElementSibling;
          while (sibling) {
            if (sibling.classList.contains('dropdown-menu')) {
              dropdownMenu = sibling;
              break;
            }
            sibling = sibling.nextElementSibling;
          }
        }

        // If still not found, look for parent's dropdown menu
        if (!dropdownMenu && toggle.parentElement) {
          dropdownMenu = toggle.parentElement.querySelector('.dropdown-menu');
        }
      } else {
        // The toggle might be a container with the button inside
        const nestedToggle = toggle.querySelector('[data-bs-toggle="dropdown"]');
        if (nestedToggle) {
          dropdownMenu = toggle.querySelector('.dropdown-menu');
        }
      }

      if (dropdownMenu) {
        // On touch devices, we need a custom implementation
        if (isTouchDevice) {
          // Create a manual toggle system for reliable mobile operation
          const toggleDropdown = (e) => {
            // On mobile, prevent default to avoid double-triggering
            e.preventDefault();
            e.stopPropagation();

            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

            // Close all other open dropdowns first
            document
              .querySelectorAll('[data-bs-toggle="dropdown"][aria-expanded="true"]')
              .forEach((otherToggle) => {
                if (otherToggle !== toggle) {
                  otherToggle.setAttribute('aria-expanded', 'false');
                  const otherMenu = document.querySelector(`[aria-labelledby="${otherToggle.id}"]`);
                  if (otherMenu) {
                    otherMenu.classList.remove('show');
                  }
                }
              });

            if (isExpanded) {
              // Close dropdown
              toggle.setAttribute('aria-expanded', 'false');
              dropdownMenu.classList.remove('show');
            } else {
              // Open dropdown
              toggle.setAttribute('aria-expanded', 'true');
              dropdownMenu.classList.add('show');

              // Position the dropdown correctly
              const btnRect = toggle.getBoundingClientRect();
              dropdownMenu.style.top = `${btnRect.bottom}px`;

              // Check if right-aligned or left-aligned
              if (
                dropdownMenu.classList.contains('dropdown-menu-end') ||
                dropdownMenu.classList.contains('dropdown-menu-right')
              ) {
                dropdownMenu.style.right = `${window.innerWidth - btnRect.right}px`;
                dropdownMenu.style.left = 'auto';
              } else {
                dropdownMenu.style.left = `${btnRect.left}px`;
                dropdownMenu.style.right = 'auto';
              }
            }
          };

          // Add touch event for mobile
          toggle.addEventListener('touchend', toggleDropdown, { passive: false });

          // Also handle click for hybrid devices
          toggle.addEventListener('click', (e) => {
            // Only apply our custom handling on touch devices
            if (isTouchDevice) {
              toggleDropdown(e);
            }
            // On desktop, let Bootstrap handle it
          });

          // Prevent dropdown items from closing the dropdown unexpectedly on touch
          dropdownMenu.querySelectorAll('.dropdown-item').forEach((item) => {
            item.addEventListener('touchend', (e) => {
              e.stopPropagation();
            });
          });
        } else {
          // For desktop, ensure Bootstrap dropdown is initialized if available
          if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            try {
              new bootstrap.Dropdown(toggle);
            } catch (_e) {
              // Bootstrap might already have initialized this dropdown
            }
          }

          // Add backup click handler for desktop if bootstrap isn't available
          toggle.addEventListener('click', (e) => {
            // Only apply if Bootstrap isn't handling it
            if (typeof bootstrap === 'undefined') {
              e.preventDefault();

              const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

              if (isExpanded) {
                // Close dropdown
                toggle.setAttribute('aria-expanded', 'false');
                dropdownMenu.classList.remove('show');
              } else {
                // Open dropdown
                toggle.setAttribute('aria-expanded', 'true');
                dropdownMenu.classList.add('show');
              }
            }
          });
        }
      }

      // Mark toggle as processed
      toggle.setAttribute('data-enhanced', 'true');
    });

    // Document-level handler to close dropdowns when clicking outside (all devices)
    document.addEventListener('click', (e) => {
      const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
      openDropdowns.forEach((menu) => {
        // Find the associated toggle
        let toggle = null;
        if (menu.hasAttribute('aria-labelledby')) {
          toggle = document.getElementById(menu.getAttribute('aria-labelledby'));
        }

        // Close if clicking outside
        if (toggle && !menu.contains(e.target) && !toggle.contains(e.target)) {
          toggle.setAttribute('aria-expanded', 'false');
          menu.classList.remove('show');
        }
      });
    });
  }

  /**
   * Sets up a mutation observer to handle dynamically loaded content.
   */
  function setupMutationObserver() {
    // Create a mutation observer to watch for dynamically added search forms or dropdowns
    const observer = new MutationObserver((mutations) => {
      let needsSearchFormFix = false;
      let needsDropdownFix = false;

      mutations.forEach((mutation) => {
        if (mutation.type === 'childList' && mutation.addedNodes.length) {
          mutation.addedNodes.forEach((node) => {
            if (node.nodeType === 1) {
              // Element node
              // Check for search forms
              if (
                node.matches('form[role="search"], .saho-search, .saho-mobile-search') ||
                node.querySelector('form[role="search"], .saho-search, .saho-mobile-search')
              ) {
                needsSearchFormFix = true;
              }

              // Check for dropdowns
              if (
                node.hasAttribute('data-bs-toggle') ||
                node.querySelector('[data-bs-toggle="dropdown"]')
              ) {
                needsDropdownFix = true;
              }
            }
          });
        }
      });

      // Apply fixes if needed
      if (needsSearchFormFix) {
        fixSearchForms();
      }

      if (needsDropdownFix) {
        fixToolsDropdown();
      }
    });

    // Start observing the document with the configured parameters
    observer.observe(document.body, { childList: true, subtree: true });
  }
})();
