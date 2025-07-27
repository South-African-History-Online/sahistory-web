/**
 * @file
 * SAHO advanced search functionality.
 * 
 * Provides enhanced features for the global search including:
 * - View toggling (list/grid)
 * - Search history tracking
 * - "Did you mean" suggestions
 * - Related search terms
 * - Active filters management
 * - Sorting functionality
 */

(function () {
  'use strict';

  // Main initialization when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on a search page
    if (document.querySelector('.saho-search-view') || document.querySelector('.search-results')) {
      initViewToggle();
      initSearchHistory();
      initActiveFilters();
      initSorting(); // Initialize sorting functionality
      generateRelatedSearches();
      checkSpellingSuggestions();
    }
    
    // Direct application to ensure the view toggle works
    setTimeout(function() {
      const savedView = localStorage.getItem('saho-search-view-preference');
      if (savedView === 'grid') {
        const gridViewBtn = document.getElementById('grid-view-btn');
        if (gridViewBtn) {
          gridViewBtn.click();
        }
      }
    }, 100);
  });

  /**
   * Initialize view toggle functionality (list/grid view).
   */
  function initViewToggle() {
    const listViewBtn = document.getElementById('list-view-btn');
    const gridViewBtn = document.getElementById('grid-view-btn');
    let resultsContainer = document.getElementById('search-results-container');
    
    // Fallback for search results container if ID not found
    if (!resultsContainer) {
      const resultContainers = document.querySelectorAll('.saho-search-results, .search-results');
      if (resultContainers.length > 0) {
        resultsContainer = resultContainers[0];
      }
    }
    
    if (!listViewBtn || !gridViewBtn || !resultsContainer) {
      console.log('View toggle elements not found');
      return;
    }
    
    // Force apply the initial list view class
    if (!resultsContainer.classList.contains('list-view') && !resultsContainer.classList.contains('grid-view')) {
      resultsContainer.classList.add('list-view');
    }
    
    // Load user preference if saved
    const savedView = localStorage.getItem('saho-search-view-preference');
    if (savedView === 'grid') {
      activateGridView();
    } else {
      activateListView(); // Ensure list view is active by default
    }
    
    // List view click handler
    listViewBtn.addEventListener('click', function(e) {
      e.preventDefault();
      activateListView();
      localStorage.setItem('saho-search-view-preference', 'list');
    });
    
    // Grid view click handler
    gridViewBtn.addEventListener('click', function(e) {
      e.preventDefault();
      activateGridView();
      localStorage.setItem('saho-search-view-preference', 'grid');
    });
    
    // Helper functions to activate views
    function activateListView() {
      resultsContainer.classList.remove('grid-view');
      resultsContainer.classList.add('list-view');
      gridViewBtn.classList.remove('active');
      listViewBtn.classList.add('active');
      
      // Apply list view styles to all search results
      document.querySelectorAll('.search-result').forEach(item => {
        item.style.width = '100%';
        item.style.display = 'block';
        item.style.boxShadow = 'none';
        item.style.borderRadius = '0';
        item.style.margin = '0';
        item.style.border = '0';
        item.style.borderBottom = '1px solid rgba(0, 0, 0, 0.08)';
      });
      
      // Update item list container
      const itemList = resultsContainer.querySelector('.item-list');
      if (itemList) {
        itemList.style.display = 'block';
        itemList.style.padding = '0';
      }
    }
    
    function activateGridView() {
      resultsContainer.classList.remove('list-view');
      resultsContainer.classList.add('grid-view');
      listViewBtn.classList.remove('active');
      gridViewBtn.classList.add('active');
      
      // Apply grid view styles to all search results
      const searchResults = document.querySelectorAll('.search-result');
      searchResults.forEach(item => {
        item.style.display = 'flex';
        item.style.flexDirection = 'column';
        item.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.1)';
        item.style.borderRadius = '8px';
        item.style.margin = '0 0 16px 0';
        item.style.border = '1px solid rgba(0, 0, 0, 0.1)';
        item.style.height = '100%';
      });
      
      // Update item list container
      const itemList = resultsContainer.querySelector('.item-list');
      if (itemList) {
        itemList.style.display = 'grid';
        itemList.style.gridTemplateColumns = 'repeat(auto-fill, minmax(300px, 1fr))';
        itemList.style.gap = '16px';
        itemList.style.padding = '16px';
      }
    }
  }

  /**
   * Initialize sorting functionality for search results.
   */
  function initSorting() {
    const sortDropdown = document.getElementById('sortDropdown');
    const sortOptions = document.querySelectorAll('.sort-option');
    
    if (!sortDropdown || sortOptions.length === 0) {
      console.log('Sort dropdown elements not found');
      return;
    }
    
    // Get current sort parameter from URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentSort = urlParams.get('sort');
    
    // Set active sort option based on URL parameter
    if (currentSort) {
      sortOptions.forEach(option => {
        if (option.getAttribute('data-sort') === currentSort) {
          option.classList.add('active');
          sortDropdown.innerHTML = option.textContent + ' <span class="caret"></span>';
        } else {
          option.classList.remove('active');
        }
      });
    }
    
    // Add click handlers to sort options
    sortOptions.forEach(option => {
      option.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Get sort value from data attribute
        const sortValue = this.getAttribute('data-sort');
        
        // Update URL with sort parameter
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('sort', sortValue);
        
        // Update active class
        sortOptions.forEach(opt => opt.classList.remove('active'));
        this.classList.add('active');
        
        // Update dropdown button text
        sortDropdown.innerHTML = this.textContent + ' <span class="caret"></span>';
        
        // Apply sorting and reload page
        window.location.href = window.location.pathname + '?' + urlParams.toString();
      });
    });
    
    // Direct event handler for the dropdown button
    sortDropdown.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      // Find the dropdown menu
      const dropdownMenu = document.querySelector('[aria-labelledby="sortDropdown"]');
      if (dropdownMenu) {
        // Toggle the 'show' class
        dropdownMenu.classList.toggle('show');
        
        // Position the dropdown properly
        const btnRect = this.getBoundingClientRect();
        dropdownMenu.style.top = (btnRect.bottom + window.scrollY) + 'px';
        dropdownMenu.style.right = (window.innerWidth - btnRect.right) + 'px';
        dropdownMenu.style.display = dropdownMenu.classList.contains('show') ? 'block' : 'none';
      }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
      if (!e.target.matches('#sortDropdown') && !e.target.closest('.dropdown-menu')) {
        const dropdownMenu = document.querySelector('[aria-labelledby="sortDropdown"]');
        if (dropdownMenu && dropdownMenu.classList.contains('show')) {
          dropdownMenu.classList.remove('show');
          dropdownMenu.style.display = 'none';
        }
      }
    });
  }

  /**
   * Initialize search history tracking and display.
   */
  function initSearchHistory() {
    // Search history elements
    const searchHistoryContainer = document.querySelector('.saho-search-history');
    const searchHistoryList = document.getElementById('recent-searches-list');
    const clearHistoryBtn = document.getElementById('clear-search-history');
    
    if (!searchHistoryContainer || !searchHistoryList) {
      return;
    }
    
    // Get current search query if present
    const currentQuery = getCurrentSearchQuery();
    
    // Load and display search history
    const searchHistory = loadSearchHistory();
    
    // If we have a history, show the container
    if (searchHistory.length > 0) {
      searchHistoryContainer.classList.remove('d-none');
      renderSearchHistory(searchHistory);
    }
    
    // Add current search to history if it exists
    if (currentQuery && currentQuery.trim() !== '') {
      addToSearchHistory(currentQuery);
    }
    
    // Clear history button click handler
    if (clearHistoryBtn) {
      clearHistoryBtn.addEventListener('click', function() {
        clearSearchHistory();
        searchHistoryContainer.classList.add('d-none');
      });
    }
    
    // Helper functions for search history
    function getCurrentSearchQuery() {
      // Try to get from URL query parameter
      const urlParams = new URLSearchParams(window.location.search);
      return urlParams.get('search_api_fulltext') || '';
    }
    
    function loadSearchHistory() {
      const savedHistory = localStorage.getItem('saho-search-history');
      return savedHistory ? JSON.parse(savedHistory) : [];
    }
    
    function saveSearchHistory(history) {
      // Keep only the last 5 searches
      const trimmedHistory = history.slice(0, 5);
      localStorage.setItem('saho-search-history', JSON.stringify(trimmedHistory));
      return trimmedHistory;
    }
    
    function addToSearchHistory(query) {
      let history = loadSearchHistory();
      
      // Don't add duplicates; instead move to top
      history = history.filter(item => item.toLowerCase() !== query.toLowerCase());
      
      // Add new query to the beginning
      history.unshift(query);
      
      // Save updated history
      history = saveSearchHistory(history);
      
      // Update the display
      renderSearchHistory(history);
      searchHistoryContainer.classList.remove('d-none');
    }
    
    function renderSearchHistory(history) {
      searchHistoryList.innerHTML = '';
      
      history.forEach(query => {
        const item = document.createElement('div');
        item.className = 'search-history-item d-flex align-items-center border-bottom py-2';
        
        // Create link to search
        const link = document.createElement('a');
        link.href = `?search_api_fulltext=${encodeURIComponent(query)}`;
        link.className = 'search-history-link flex-grow-1';
        link.textContent = query;
        
        // Create icon
        const icon = document.createElement('span');
        icon.className = 'search-history-icon me-2';
        icon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16"><path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022l-.074.997zm2.004.45a7.003 7.003 0 0 0-.985-.299l.219-.976c.383.086.76.2 1.126.342l-.36.933zm1.37.71a7.01 7.01 0 0 0-.439-.27l.493-.87a8.025 8.025 0 0 1 .979.654l-.615.789a6.996 6.996 0 0 0-.418-.302zm1.834 1.79a6.99 6.99 0 0 0-.653-.796l.724-.69c.27.285.52.59.747.91l-.818.576zm.744 1.352a7.08 7.08 0 0 0-.214-.468l.893-.45a7.976 7.976 0 0 1 .45 1.088l-.95.313a7.023 7.023 0 0 0-.179-.483zm.53 2.507a6.991 6.991 0 0 0-.1-1.025l.985-.17c.067.386.106.778.116 1.17l-1 .025zm-.131 1.538c.033-.17.06-.339.081-.51l.993.123a7.957 7.957 0 0 1-.23 1.155l-.964-.267c.046-.165.086-.332.12-.501zm-.952 2.379c.184-.29.346-.594.486-.908l.914.405c-.16.36-.345.706-.555 1.038l-.845-.535zm-.964 1.205c.122-.122.239-.248.35-.378l.758.653a8.073 8.073 0 0 1-.401.432l-.707-.707z"/><path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0v1z"/><path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5z"/></svg>';
        
        // Add to item
        item.appendChild(icon);
        item.appendChild(link);
        
        // Add to list
        searchHistoryList.appendChild(item);
      });
    }
    
    function clearSearchHistory() {
      localStorage.removeItem('saho-search-history');
      searchHistoryList.innerHTML = '';
    }
  }

  /**
   * Initialize active filters display and management.
   */
  function initActiveFilters() {
    const activeFiltersContainer = document.getElementById('active-search-filters');
    const resetFiltersButton = document.getElementById('reset-search-filters');
    
    if (!activeFiltersContainer) {
      return;
    }
    
    // Get all active filters from URL
    const urlParams = new URLSearchParams(window.location.search);
    let hasActiveFilters = false;
    
    // Skip the search query parameter as it's not a filter
    urlParams.forEach((value, key) => {
      if (key !== 'search_api_fulltext' && key !== 'page') {
        hasActiveFilters = true;
        addActiveFilterTag(key, value);
      }
    });
    
    // Show reset filters button if we have filters
    if (resetFiltersButton) {
      if (hasActiveFilters) {
        resetFiltersButton.classList.remove('d-none');
        
        resetFiltersButton.addEventListener('click', function() {
          // Keep only the search query parameter
          const searchQuery = urlParams.get('search_api_fulltext');
          let newUrl = window.location.pathname;
          
          if (searchQuery) {
            newUrl += `?search_api_fulltext=${encodeURIComponent(searchQuery)}`;
          }
          
          window.location.href = newUrl;
        });
      } else {
        resetFiltersButton.classList.add('d-none');
      }
    }
    
    /**
     * Add a filter tag to the active filters container.
     */
    function addActiveFilterTag(key, value) {
      // Skip empty values
      if (!value || value.trim() === '') {
        return;
      }
      
      // Create filter tag
      const tag = document.createElement('span');
      tag.className = 'badge bg-light text-dark border me-2 mb-2';
      
      // Format the filter name for display
      let filterName = key.replace(/_/g, ' ').replace('f[', '').replace(']', '');
      filterName = filterName.charAt(0).toUpperCase() + filterName.slice(1);
      
      // Create the tag content
      tag.innerHTML = `${filterName}: ${value} <a href="#" class="ms-2 remove-filter" data-filter-key="${key}">×</a>`;
      
      // Add to container
      activeFiltersContainer.appendChild(tag);
      
      // Add click handler to remove button
      const removeBtn = tag.querySelector('.remove-filter');
      if (removeBtn) {
        removeBtn.addEventListener('click', function(e) {
          e.preventDefault();
          
          // Remove this filter from URL and reload
          const filterKey = this.getAttribute('data-filter-key');
          urlParams.delete(filterKey);
          
          let newUrl = window.location.pathname;
          if (urlParams.toString()) {
            newUrl += `?${urlParams.toString()}`;
          }
          
          window.location.href = newUrl;
        });
      }
    }
  }

  /**
   * Generate related search terms based on current search.
   */
  function generateRelatedSearches() {
    const relatedSearchesContainer = document.getElementById('related-searches-container');
    
    if (!relatedSearchesContainer) {
      return;
    }
    
    // Get current search query
    const urlParams = new URLSearchParams(window.location.search);
    const currentQuery = urlParams.get('search_api_fulltext');
    
    if (!currentQuery || currentQuery.trim() === '') {
      return;
    }
    
    // In a real implementation, you would fetch related terms from an API
    // For this example, we'll simulate with some static terms based on the query
    
    // Some example related terms based on common SA history topics
    const relatedTermsMap = {
      'apartheid': ['segregation', 'racism', 'civil rights', 'anc', 'mandela'],
      'mandela': ['nelson mandela', 'robben island', 'anc', 'freedom', 'president'],
      'anc': ['african national congress', 'mandela', 'freedom charter', 'struggle'],
      'freedom': ['liberation', 'democracy', 'rights', 'struggle', 'equality'],
      'struggle': ['resistance', 'liberation', 'movement', 'freedom', 'protest'],
      'nelson': ['mandela', 'leadership', 'freedom fighter', 'president', 'anti-apartheid'],
      'nelson mandela': ['mandela', 'anc', 'robben island', 'president', 'anti-apartheid']
    };
    
    // Helper function to find related terms
    function findRelatedTerms(query) {
      const terms = [];
      const queryLower = query.toLowerCase();
      
      // Check for direct matches in our map
      if (relatedTermsMap[queryLower]) {
        return relatedTermsMap[queryLower];
      }
      
      // Check for partial matches
      Object.keys(relatedTermsMap).forEach(key => {
        if (queryLower.includes(key) || key.includes(queryLower)) {
          terms.push(...relatedTermsMap[key]);
        }
      });
      
      // Add some generic terms if we don't have enough
      if (terms.length < 3) {
        terms.push('history', 'south africa', 'archives');
      }
      
      // Remove duplicates and the original query
      return [...new Set(terms)]
        .filter(term => term.toLowerCase() !== queryLower)
        .slice(0, 5); // Limit to 5 terms
    }
    
    // Get related terms
    const relatedTerms = findRelatedTerms(currentQuery);
    
    // Clear container first
    relatedSearchesContainer.innerHTML = '';
    
    // Add related terms to container
    relatedTerms.forEach(term => {
      const link = document.createElement('a');
      link.href = `?search_api_fulltext=${encodeURIComponent(term)}`;
      link.className = 'badge rounded-pill bg-light text-dark border';
      link.textContent = term;
      
      relatedSearchesContainer.appendChild(link);
    });
  }

  /**
   * Check for potential spelling suggestions for the search query.
   */
  function checkSpellingSuggestions() {
    const suggestionContainer = document.getElementById('search-spelling-suggestion');
    const suggestionLink = document.getElementById('search-spelling-link');
    
    if (!suggestionContainer || !suggestionLink) {
      return;
    }
    
    // Get current search query
    const urlParams = new URLSearchParams(window.location.search);
    const currentQuery = urlParams.get('search_api_fulltext');
    
    if (!currentQuery || currentQuery.trim() === '') {
      return;
    }
    
    // In a real implementation, you would use a spelling API
    // For this example, we'll use a simple map of common misspellings
    const spellingCorrections = {
      'aparteid': 'apartheid',
      'apartide': 'apartheid',
      'aparthied': 'apartheid',
      'mandla': 'mandela',
      'mandella': 'mandela',
      'madela': 'mandela',
      'madiba': 'mandela',
      'ancc': 'anc',
      'afican': 'african',
      'freedoom': 'freedom',
      'freedum': 'freedom',
      'fredom': 'freedom',
      'nelson madela': 'nelson mandela',
      'nelson mandella': 'nelson mandela'
    };
    
    // Check if we have a correction for this query
    const queryLower = currentQuery.toLowerCase();
    let correctedQuery = null;
    
    // Check for direct matches
    if (spellingCorrections[queryLower]) {
      correctedQuery = spellingCorrections[queryLower];
    } else {
      // Check for partial matches in words
      const words = queryLower.split(' ');
      for (let i = 0; i < words.length; i++) {
        if (spellingCorrections[words[i]]) {
          words[i] = spellingCorrections[words[i]];
          correctedQuery = words.join(' ');
          break;
        }
      }
    }
    
    // Display spelling suggestion if found
    if (correctedQuery && correctedQuery !== queryLower) {
      suggestionLink.textContent = correctedQuery;
      suggestionLink.href = `?search_api_fulltext=${encodeURIComponent(correctedQuery)}`;
      suggestionContainer.classList.remove('d-none');
    }
  }
})();