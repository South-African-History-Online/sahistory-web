/**
 * @file
 * Mobile-specific enhancements for SAHO site.
 * 
 * Provides fixes for mobile search functionality and Tools dropdown.
 */

(function () {
  'use strict';

  // Execute as soon as DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileEnhancements);
  } else {
    initMobileEnhancements();
  }

  /**
   * Initialize all mobile enhancements.
   */
  function initMobileEnhancements() {
    // Fix for mobile search form Enter key submission
    fixMobileSearchForms();
    
    // Fix for Tools dropdown on mobile
    fixMobileToolsDropdown();

    // Add mutation observer to handle dynamically loaded content
    setupMutationObserver();
  }

  /**
   * Fixes mobile search form submission via Enter key and button click.
   * Enhanced to handle all search forms across the site.
   */
  function fixMobileSearchForms() {
    // Target all possible search forms across the site
    const searchFormSelectors = [
      'form[role="search"]',
      '.saho-search',
      '.saho-mobile-search',
      'form[action="/search"]',
      '.search-form',
      '.saho-search-form'
    ];
    
    const searchForms = document.querySelectorAll(searchFormSelectors.join(', '));
    
    searchForms.forEach(form => {
      // Skip forms we've already processed
      if (form.hasAttribute('data-mobile-enhanced')) {
        return;
      }
      
      const searchInput = form.querySelector('input[type="search"], input[type="text"][name="search_api_fulltext"]');
      const searchButton = form.querySelector('button[type="submit"], input[type="submit"]');
      
      if (searchInput) {
        // Handle both keydown and keypress events for maximum compatibility
        ['keydown', 'keypress'].forEach(eventType => {
          searchInput.addEventListener(eventType, function(e) {
            if (e.key === 'Enter') {
              e.preventDefault();
              e.stopPropagation();
              
              // For iOS, blur the input to hide keyboard before submission
              searchInput.blur();
              
              // Small delay to ensure blur happens before submission on iOS
              setTimeout(() => {
                if (searchInput.value.trim() !== '') {
                  form.submit();
                }
              }, 10);
            }
          });
        });
        
        // Make sure search input has proper attributes for mobile
        searchInput.setAttribute('enterkeyhint', 'search');
        searchInput.setAttribute('autocapitalize', 'off');
      }
      
      if (searchButton) {
        // Add multiple event listeners for different mobile scenarios
        ['click', 'touchend'].forEach(eventType => {
          searchButton.addEventListener(eventType, function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const input = this.closest('form').querySelector('input[type="search"], input[type="text"][name="search_api_fulltext"]');
            
            // For iOS, blur the input to hide keyboard
            if (input) {
              input.blur();
            }
            
            // Small delay for iOS
            setTimeout(() => {
              if (!input || input.value.trim() !== '') {
                this.closest('form').submit();
              } else if (input) {
                input.focus();
              }
            }, 10);
          }, { passive: false });
        });
      }
      
      // Mark form as processed
      form.setAttribute('data-mobile-enhanced', 'true');
    });
  }

  /**
   * Fixes Tools dropdown functionality on mobile devices.
   * Enhanced to handle any dropdown with data-bs-toggle="dropdown".
   */
  function fixMobileToolsDropdown() {
    // Target both specific Tools dropdown and any dropdown toggles
    const dropdownToggles = document.querySelectorAll('.tools-dropdown, [data-bs-toggle="dropdown"]');
    
    dropdownToggles.forEach(toggle => {
      // Skip toggles we've already processed
      if (toggle.hasAttribute('data-mobile-enhanced')) {
        return;
      }
      
      // Check if we're on a touch device
      const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
      
      if (isTouch) {
        // Find the dropdown menu
        let dropdownMenu;
        
        // Check if the toggle itself is the dropdown button
        if (toggle.hasAttribute('data-bs-toggle') && toggle.getAttribute('data-bs-toggle') === 'dropdown') {
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
          // Create a manual toggle system for reliable mobile operation
          const toggleDropdown = function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            
            // Close all other open dropdowns first
            document.querySelectorAll('[data-bs-toggle="dropdown"][aria-expanded="true"]').forEach(otherToggle => {
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
              dropdownMenu.style.top = btnRect.bottom + 'px';
              
              // Check if right-aligned or left-aligned
              if (dropdownMenu.classList.contains('dropdown-menu-end') || 
                  dropdownMenu.classList.contains('dropdown-menu-right')) {
                dropdownMenu.style.right = (window.innerWidth - btnRect.right) + 'px';
                dropdownMenu.style.left = 'auto';
              } else {
                dropdownMenu.style.left = btnRect.left + 'px';
                dropdownMenu.style.right = 'auto';
              }
            }
          };
          
          // Add multiple event handlers for better mobile compatibility
          ['click', 'touchend'].forEach(eventType => {
            toggle.addEventListener(eventType, toggleDropdown, { passive: false });
          });
          
          // Prevent dropdown items from closing the dropdown unexpectedly
          dropdownMenu.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
              e.stopPropagation();
            });
          });
        }
      }
      
      // Mark toggle as processed
      toggle.setAttribute('data-mobile-enhanced', 'true');
    });
    
    // Document-level handler to close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
      const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
      openDropdowns.forEach(menu => {
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
    const observer = new MutationObserver(function(mutations) {
      let needsSearchFormFix = false;
      let needsDropdownFix = false;
      
      mutations.forEach(mutation => {
        if (mutation.type === 'childList' && mutation.addedNodes.length) {
          mutation.addedNodes.forEach(node => {
            if (node.nodeType === 1) { // Element node
              // Check for search forms
              if (node.matches('form[role="search"], .saho-search, .saho-mobile-search') || 
                  node.querySelector('form[role="search"], .saho-search, .saho-mobile-search')) {
                needsSearchFormFix = true;
              }
              
              // Check for dropdowns
              if (node.hasAttribute('data-bs-toggle') || 
                  node.querySelector('[data-bs-toggle="dropdown"]')) {
                needsDropdownFix = true;
              }
            }
          });
        }
      });
      
      // Apply fixes if needed
      if (needsSearchFormFix) {
        fixMobileSearchForms();
      }
      
      if (needsDropdownFix) {
        fixMobileToolsDropdown();
      }
    });
    
    // Start observing the document with the configured parameters
    observer.observe(document.body, { childList: true, subtree: true });
  }
})();