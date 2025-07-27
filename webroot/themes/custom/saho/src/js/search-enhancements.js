/**
 * @file
 * SAHO search enhancements.
 * 
 * Improves search functionality for mobile devices and ensures
 * proper interaction with search button and Tools dropdown.
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
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
    const searchForms = document.querySelectorAll('form[role="search"], .saho-search-form, .saho-mobile-search');
    
    searchForms.forEach(form => {
      // Find search input and button in this form
      const searchInput = form.querySelector('input[type="search"], input[name="search_api_fulltext"]');
      const searchButton = form.querySelector('button[type="submit"]');
      
      if (searchInput) {
        // Ensure form submits when Enter key is pressed
        searchInput.addEventListener('keydown', function(e) {
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
        searchButton.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          
          // Get the input from this button's form
          const input = this.closest('form').querySelector('input[type="search"], input[name="search_api_fulltext"]');
          
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
        searchButton.addEventListener('touchend', function(e) {
          e.preventDefault();
          const form = this.closest('form');
          const input = form.querySelector('input[type="search"], input[name="search_api_fulltext"]');
          
          if (input && input.value.trim() !== '') {
            form.submit();
          } else if (input) {
            input.focus();
          }
        }, { passive: false });
      }
    });
    
    // Fix for main search box in the header (might have different selectors)
    const headerSearch = document.querySelector('.saho-search, .saho-header .search-form');
    if (headerSearch) {
      const headerInput = headerSearch.querySelector('input[type="search"], input[name="search_api_fulltext"]');
      const headerButton = headerSearch.querySelector('button[type="submit"]');
      
      if (headerInput && headerButton) {
        // Focus input when button is clicked with empty input
        headerButton.addEventListener('click', function(e) {
          if (headerInput.value.trim() === '') {
            e.preventDefault();
            headerInput.focus();
          }
        });
      }
    }
  }

  /**
   * Fixes Tools dropdown on mobile devices.
   * 
   * Ensures proper touch interaction and dropdown functionality.
   */
  function fixToolsDropdown() {
    const toolsDropdown = document.getElementById('toolsDropdown');
    
    if (!toolsDropdown) {
      return;
    }
    
    // Find dropdown menu
    const dropdownMenu = document.querySelector('[aria-labelledby="toolsDropdown"]');
    if (!dropdownMenu) {
      return;
    }
    
    // Check if we're on a touch device
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    const isMobile = window.matchMedia('(max-width: 991.98px)').matches;
    
    // Remove existing click handlers to prevent conflicts
    const oldClickHandler = toolsDropdown.onclick;
    toolsDropdown.onclick = null;
    
    // First, try to initialize with Bootstrap if available
    if (typeof bootstrap !== 'undefined') {
      // Get existing instance if any
      let dropdownInstance = bootstrap.Dropdown.getInstance(toolsDropdown);
      
      // If no instance exists, create a new one
      if (!dropdownInstance) {
        dropdownInstance = new bootstrap.Dropdown(toolsDropdown, {
          autoClose: 'outside'
        });
      }
      
      // For mobile/touch devices, add special touch handling
      if (isMobile || isTouchDevice) {
        // Add custom touch event handler
        toolsDropdown.addEventListener('touchstart', function(e) {
          // Don't let the event bubble up to document
          e.stopPropagation();
          
          // Toggle the dropdown manually
          const isExpanded = this.getAttribute('aria-expanded') === 'true';
          
          if (isExpanded) {
            dropdownInstance.hide();
          } else {
            dropdownInstance.show();
          }
        }, { passive: true });
      }
      
      // Ensure click works properly on desktop too
      toolsDropdown.addEventListener('click', function(e) {
        // Allow default Bootstrap behavior to continue
      });
    } else {
      // Fallback for when Bootstrap is not available
      // This handles both mobile and desktop
      toolsDropdown.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        
        if (isExpanded) {
          this.setAttribute('aria-expanded', 'false');
          dropdownMenu.classList.remove('show');
        } else {
          this.setAttribute('aria-expanded', 'true');
          dropdownMenu.classList.add('show');
          
          // Position the dropdown properly
          const btnRect = this.getBoundingClientRect();
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
      });
      
      // Close dropdown when clicking outside
      document.addEventListener('click', function(e) {
        if (toolsDropdown.getAttribute('aria-expanded') === 'true' && 
            !dropdownMenu.contains(e.target) && 
            e.target !== toolsDropdown) {
          toolsDropdown.setAttribute('aria-expanded', 'false');
          dropdownMenu.classList.remove('show');
        }
      });
    }
    
    // Add handlers for all dropdown items 
    const dropdownItems = document.querySelectorAll('.dropdown-menu .dropdown-item');
    dropdownItems.forEach(item => {
      item.addEventListener('touchend', function(e) {
        e.stopPropagation();
        
        // If the item has a real href that's not "#", follow it
        if (this.getAttribute('href') && this.getAttribute('href') !== '#') {
          window.location.href = this.getAttribute('href');
        }
      }, { passive: true });
    });
  }
})();