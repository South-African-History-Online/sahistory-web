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

    // Fix for Offcanvas menu on mobile
    fixOffcanvasMenu();

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
   *
   * Bootstrap 5 handles dropdowns natively via data-bs-toggle="dropdown".
   * This function is now a no-op - let Bootstrap do its job.
   */
  function fixToolsDropdown() {
    // Bootstrap 5 dropdowns work correctly out of the box.
    // The dropdown is initialized in main.script.js with:
    //   new bootstrap.Dropdown(dropdownToggleEl, { autoClose: 'outside' })
    //
    // No custom handling needed - this was causing positioning issues.
  }

  /**
   * Fixes Offcanvas menu functionality on mobile devices.
   * Enhanced to handle Bootstrap Offcanvas with fallback.
   */
  function fixOffcanvasMenu() {
    // Target all offcanvas toggles
    const offcanvasToggles = document.querySelectorAll('[data-bs-toggle="offcanvas"]');

    offcanvasToggles.forEach((toggle) => {
      // Skip toggles we've already processed
      if (toggle.hasAttribute('data-enhanced')) {
        return;
      }

      // Find the offcanvas menu
      const targetId = toggle.getAttribute('data-bs-target');
      if (!targetId) {
        return;
      }

      const offcanvasMenu = document.querySelector(targetId);
      if (!offcanvasMenu) {
        return;
      }

      // For touch devices, we need custom implementation for reliability
      if (isTouchDevice) {
        // Manual toggle implementation for mobile
        const toggleOffcanvas = (e) => {
          e.preventDefault();
          e.stopPropagation();

          const isOpen = offcanvasMenu.classList.contains('show');

          if (isOpen) {
            // Close offcanvas
            offcanvasMenu.classList.remove('show');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('modal-open');

            // Remove backdrop
            const backdrop = document.querySelector('.offcanvas-backdrop');
            if (backdrop) {
              backdrop.remove();
            }
          } else {
            // Open offcanvas
            offcanvasMenu.classList.add('show');
            toggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('modal-open');

            // Add backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'offcanvas-backdrop fade show';
            backdrop.style.cssText =
              'position:fixed;top:0;left:0;z-index:1040;width:100vw;height:100vh;background-color:rgba(0,0,0,0.5)';
            document.body.appendChild(backdrop);

            // Close on backdrop click
            backdrop.addEventListener('click', () => {
              offcanvasMenu.classList.remove('show');
              toggle.setAttribute('aria-expanded', 'false');
              document.body.classList.remove('modal-open');
              backdrop.remove();
            });
          }
        };

        // Add touch event for mobile
        toggle.addEventListener('touchend', toggleOffcanvas, { passive: false });

        // Also handle click for hybrid devices
        toggle.addEventListener('click', (e) => {
          if (isTouchDevice) {
            toggleOffcanvas(e);
          }
        });

        // Handle close button in offcanvas
        const closeButton = offcanvasMenu.querySelector('[data-bs-dismiss="offcanvas"]');
        if (closeButton) {
          closeButton.addEventListener('click', (e) => {
            e.preventDefault();
            offcanvasMenu.classList.remove('show');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('modal-open');

            const backdrop = document.querySelector('.offcanvas-backdrop');
            if (backdrop) {
              backdrop.remove();
            }
          });
        }
      } else {
        // For desktop, ensure Bootstrap Offcanvas is initialized if available
        if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
          try {
            new bootstrap.Offcanvas(offcanvasMenu);
          } catch (_e) {
            // Offcanvas might already be initialized
          }
        } else {
          // Fallback click handler if Bootstrap isn't available
          toggle.addEventListener('click', (e) => {
            e.preventDefault();

            const isOpen = offcanvasMenu.classList.contains('show');

            if (isOpen) {
              offcanvasMenu.classList.remove('show');
              toggle.setAttribute('aria-expanded', 'false');
            } else {
              offcanvasMenu.classList.add('show');
              toggle.setAttribute('aria-expanded', 'true');
            }
          });
        }
      }

      // Mark toggle as processed
      toggle.setAttribute('data-enhanced', 'true');
    });

    // Document-level handler to close offcanvas when clicking outside (ESC key)
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const openOffcanvas = document.querySelector('.offcanvas.show');
        if (openOffcanvas) {
          openOffcanvas.classList.remove('show');
          document.body.classList.remove('modal-open');

          const backdrop = document.querySelector('.offcanvas-backdrop');
          if (backdrop) {
            backdrop.remove();
          }

          // Find and update the toggle button
          const toggle = document.querySelector(`[data-bs-target="#${openOffcanvas.id}"]`);
          if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
          }
        }
      }
    });
  }

  /**
   * Sets up a mutation observer to handle dynamically loaded content.
   */
  function setupMutationObserver() {
    // Create a mutation observer to watch for dynamically added search forms or offcanvas
    const observer = new MutationObserver((mutations) => {
      let needsSearchFormFix = false;
      let needsOffcanvasFix = false;

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

              // Check for offcanvas
              if (
                node.hasAttribute('data-bs-toggle') ||
                node.querySelector('[data-bs-toggle="offcanvas"]')
              ) {
                needsOffcanvasFix = true;
              }
            }
          });
        }
      });

      // Apply fixes if needed
      if (needsSearchFormFix) {
        fixSearchForms();
      }

      if (needsOffcanvasFix) {
        fixOffcanvasMenu();
      }
    });

    // Start observing the document with the configured parameters
    observer.observe(document.body, { childList: true, subtree: true });
  }
})();
