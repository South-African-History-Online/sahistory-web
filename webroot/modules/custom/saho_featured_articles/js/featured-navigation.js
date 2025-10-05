(($, Drupal) => {
  // Global state for filtering and pagination
  let currentFilters = {
    search: '',
    type: 'all',
    sort: 'recent',
  };
  const itemsPerPage = 12;
  let currentPage = 1;
  let currentView = 'grid';

  /**
   * Featured content navigation behavior.
   */
  Drupal.behaviors.featuredContentNavigation = {
    attach: (context, _settings) => {
      // Initialize category navigation once per context
      $('.saho-category-item', context)
        .once('featured-navigation')
        .each(function () {
          $(this).on('click', function (e) {
            e.preventDefault();

            // Update active states and ARIA attributes
            $('.saho-category-item').removeClass('active').attr('aria-selected', 'false');
            $(this).addClass('active').attr('aria-selected', 'true');

            const target = $(this).attr('data-target');

            // Show/hide content sections
            $('.featured-content-section').hide().removeClass('active');

            const targetSection = $(`#${target}`);
            if (targetSection.length) {
              targetSection.show().addClass('active');

              // Load content if needed
              loadSectionContent(target);
            }
          });
        });

      // Initialize search functionality
      $('#search-featured', context)
        .once('search-input')
        .on(
          'input',
          debounce(function () {
            currentFilters.search = $(this).val().toLowerCase();
            currentPage = 1;
            applyFiltersAndSort();
          }, 300)
        );

      // Clear search button
      $('#clear-search', context)
        .once('clear-search')
        .on('click', () => {
          $('#search-featured').val('');
          currentFilters.search = '';
          currentPage = 1;
          applyFiltersAndSort();
        });

      // Content type filter
      $('#filter-type', context)
        .once('filter-type')
        .on('change', function () {
          currentFilters.type = $(this).val();
          currentPage = 1;
          applyFiltersAndSort();
          updateActiveFilters();
        });

      // Initialize sort dropdown functionality
      $('#sort-all', context)
        .once('sort-functionality')
        .on('change', function () {
          currentFilters.sort = $(this).val();
          applyFiltersAndSort();
        });

      // View toggle buttons
      $('.view-options button', context)
        .once('view-toggle')
        .on('click', function () {
          $('.view-options button').removeClass('active');
          $(this).addClass('active');
          currentView = $(this).data('view');
          toggleView(currentView);
        });

      // Load more button
      $('#load-more-btn', context)
        .once('load-more')
        .on('click', () => {
          currentPage++;
          applyFiltersAndSort(false);
        });

      // Clear all filters
      $('#clear-all-filters', context)
        .once('clear-filters')
        .on('click', () => {
          $('#search-featured').val('');
          $('#filter-type').val('all');
          currentFilters = { search: '', type: 'all', sort: currentFilters.sort };
          currentPage = 1;
          applyFiltersAndSort();
          updateActiveFilters();
        });

      // Load initial counts on first attach
      if (!$('.saho-category-item', context).hasClass('counts-loaded')) {
        loadCategoryCounts();
        $('.saho-category-item', context).addClass('counts-loaded');
      }

      // Initialize pagination on first load
      if (!$('#all-featured-grid', context).hasClass('pagination-initialized')) {
        $('#all-featured-grid', context).addClass('pagination-initialized');
        applyFiltersAndSort();
      }
    },
  };

  /**
   * Load content for a specific section
   */
  function loadSectionContent(section) {
    const container = $(`#${section}-content`);

    // Skip if already loaded or is the main section
    if (!container.length || container.hasClass('loaded') || section === 'all-featured') {
      return;
    }

    // For most read, use specific endpoint
    if (section === 'most-read') {
      loadMostRead();
      return;
    }

    // For all other sections, use the generic section endpoint
    loadGenericSection(section);
  }

  /**
   * Load content for any section using the generic endpoint
   */
  function loadGenericSection(section) {
    const container = $(`#${section}-content`);
    if (!container.length) {
      return;
    }

    // Show loading spinner with proper accessibility
    container.html(
      `<div class="col-12 text-center py-5"><div class="spinner-border text-danger" role="status" aria-live="polite"><span class="visually-hidden">Loading ${section.replace('-', ' ')} content...</span></div></div>`
    );

    // Fetch section content via AJAX
    $.get(`/featured/section/${section}`)
      .done((data) => {
        if (data.html) {
          container.html(data.html);
          container.addClass('loaded');
          updateCategoryCount(section, data.count);

          // Announce to screen readers
          container.attr('aria-live', 'polite');
        } else {
          container.html(
            '<div class="col-12"><div class="alert alert-warning">No content returned from server.</div></div>'
          );
        }
      })
      .fail((xhr, _status, _error) => {
        let errorMessage = 'Unable to load content. Please try again.';
        if (xhr.responseJSON?.error) {
          errorMessage = xhr.responseJSON.error;
        }
        container.html(
          `<div class="col-12"><div class="alert alert-danger">${errorMessage}</div></div>`
        );
      });
  }

  /**
   * Load most read content from statistics
   */
  function loadMostRead() {
    const container = $('#most-read-content');

    // Show loading spinner
    container.html(
      '<div class="col-12 text-center py-5"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading most read content...</span></div></div>'
    );

    // Fetch most read content via AJAX
    $.get('/featured/most-read-ajax')
      .done((data) => {
        container.html(data.html);
        container.addClass('loaded');
        updateCategoryCount('most-read', data.count);
      })
      .fail((_xhr, _status, _error) => {
        container.html(
          '<div class="col-12"><div class="alert alert-warning">Unable to load most read content. Please try again.</div></div>'
        );
      });
  }

  /**
   * Load category counts using conditional group endpoints
   */
  function loadCategoryCounts() {
    const sections = ['staff-picks', 'africa-section', 'politics-society', 'timelines'];

    // Load counts for each section via AJAX
    sections.forEach((section) => {
      $.get(`/featured/section/${section}`)
        .done((data) => {
          updateCategoryCount(section, data.count);
        })
        .fail((_xhr, _status, _error) => {
          updateCategoryCount(section, '?');
        });
    });

    // Load most read count via separate endpoint
    $.get('/featured/most-read-ajax')
      .done((data) => {
        updateCategoryCount('most-read', data.count);
      })
      .fail((_xhr, _status, _error) => {
        updateCategoryCount('most-read', '?');
      });
  }

  /**
   * Sort featured content based on selected criteria
   */
  function _sortFeaturedContent(sortType) {
    const container = $('#all-featured-grid');
    const items = container.find('.col-lg-6').get();

    items.sort((a, b) => {
      switch (sortType) {
        case 'title': {
          const titleA = $(a).data('title') || '';
          const titleB = $(b).data('title') || '';
          return titleA.localeCompare(titleB);
        }

        case 'type': {
          const typeA = $(a).data('node-type') || '';
          const typeB = $(b).data('node-type') || '';
          return typeA.localeCompare(typeB);
        }
        default: {
          const dateA = $(a).data('updated') || 0;
          const dateB = $(b).data('updated') || 0;
          return dateB - dateA;
        }

        // Most recent first
      }
    });

    // Re-append sorted items
    $.each(items, (_index, item) => {
      container.append(item);
    });
  }

  /**
   * Update category count display
   */
  function updateCategoryCount(category, count) {
    $(`#${category}-count`).text(count);
  }

  /**
   * Apply all filters and sorting to featured content
   */
  function applyFiltersAndSort(resetPage = true) {
    if (resetPage) {
      currentPage = 1;
    }

    const container = $('#all-featured-grid');
    let items = container.find('.col-lg-6, .col-12').get();

    // Filter by search term
    if (currentFilters.search) {
      items = items.filter((item) => {
        const title = $(item).data('title') || '';
        const summary = $(item).find('.card-text').text().toLowerCase();
        return title.includes(currentFilters.search) || summary.includes(currentFilters.search);
      });
    }

    // Filter by content type
    if (currentFilters.type !== 'all') {
      items = items.filter((item) => $(item).data('node-type') === currentFilters.type);
    }

    // Sort items
    items.sort((a, b) => {
      switch (currentFilters.sort) {
        case 'title': {
          const titleA = $(a).data('title') || '';
          const titleB = $(b).data('title') || '';
          return titleA.localeCompare(titleB);
        }

        case 'title-desc': {
          const titleDescA = $(a).data('title') || '';
          const titleDescB = $(b).data('title') || '';
          return titleDescB.localeCompare(titleDescA);
        }

        case 'type': {
          const typeA = $(a).data('node-type') || '';
          const typeB = $(b).data('node-type') || '';
          return typeA.localeCompare(typeB);
        }

        case 'oldest': {
          const oldDateA = $(a).data('updated') || 0;
          const oldDateB = $(b).data('updated') || 0;
          return oldDateA - oldDateB;
        }
        default: {
          const dateA = $(a).data('updated') || 0;
          const dateB = $(b).data('updated') || 0;
          return dateB - dateA;
        }
      }
    });

    // Hide all items first
    container.find('.col-lg-6, .col-12').hide();

    // Show filtered and paginated items
    const totalItems = items.length;
    const itemsToShow = currentPage * itemsPerPage;

    if (totalItems === 0) {
      $('#no-results').show();
      $('#load-more-container').hide();
    } else {
      $('#no-results').hide();
      $('#load-more-container').show();

      items.slice(0, itemsToShow).forEach((item) => {
        $(item).show();
      });

      // Update counts
      const visibleCount = Math.min(itemsToShow, totalItems);
      $('#visible-count').text(visibleCount);
      $('#total-count').text(totalItems);
      $('#results-count').text(totalItems);

      // Show/hide load more button
      if (visibleCount >= totalItems) {
        $('#load-more-btn').hide();
      } else {
        $('#load-more-btn').show();
      }
    }

    // Reorder items in DOM
    container.empty();
    items.forEach((item) => {
      container.append(item);
    });
  }

  /**
   * Update active filters display
   */
  function updateActiveFilters() {
    const badges = [];
    let hasFilters = false;

    if (currentFilters.search) {
      badges.push(`<span class="badge bg-secondary">Search: "${currentFilters.search}"</span>`);
      hasFilters = true;
    }

    if (currentFilters.type !== 'all') {
      badges.push(`<span class="badge bg-info">Type: ${currentFilters.type}</span>`);
      hasFilters = true;
    }

    if (hasFilters) {
      $('#filter-badges').html(badges.join(''));
      $('#active-filters').show();
    } else {
      $('#active-filters').hide();
    }
  }

  /**
   * Toggle between grid and list view
   */
  function toggleView(view) {
    const grid = $('#all-featured-grid');

    if (view === 'list') {
      grid.removeClass('row').addClass('list-view');
      grid.find('.col-lg-6').removeClass('col-lg-6').addClass('col-12');
    } else {
      grid.removeClass('list-view').addClass('row');
      grid.find('.col-12').removeClass('col-12').addClass('col-lg-6');
    }
  }

  /**
   * Debounce function for search input
   */
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }
})(jQuery, Drupal);
