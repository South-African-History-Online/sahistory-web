(function ($, Drupal, once) {
  'use strict';

  /**
   * Featured content navigation behavior.
   */
  Drupal.behaviors.featuredContentNavigation = {
    attach: function (context, settings) {
      // Initialize category navigation using Drupal 10+ once()
      once('featured-navigation', '.saho-category-item', context).forEach(function (element) {
        $(element).on('click', function (e) {
          e.preventDefault();

          // Update active states and ARIA attributes
          $('.saho-category-item').removeClass('active').attr('aria-selected', 'false');
          $(this).addClass('active').attr('aria-selected', 'true');

          const target = $(this).attr('data-target');

          // Show/hide content sections
          $('.featured-content-section').hide().removeClass('active');

          const targetSection = $('#' + target);
          if (targetSection.length) {
            targetSection.show().addClass('active');

            // Load content if needed
            loadSectionContent(target);
          }
        });
      });

      // Initialize sort dropdown functionality using Drupal 10+ once()
      once('sort-functionality', '#sort-all', context).forEach(function (element) {
        $(element).on('change', function () {
          const sortType = $(this).val();
          sortFeaturedContent(sortType);
        });
      });

      // Load initial counts on first attach
      once('counts-loaded', '.saho-featured-articles', context).forEach(function () {
        loadCategoryCounts();
      });
    }
  };

  /**
   * Load content for a specific section
   */
  function loadSectionContent(section) {
    const container = $('#' + section + '-content');

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
    const container = $('#' + section + '-content');
    if (!container.length) {
      return;
    }

    // Show loading spinner with proper accessibility
    container.html('<div class="col-12 text-center py-5"><div class="spinner-border text-danger" role="status" aria-live="polite"><span class="visually-hidden">Loading ' + section.replace('-', ' ') + ' content...</span></div></div>');

    // Fetch section content via AJAX
    $.get('/featured/section/' + section)
      .done(function (data) {
        if (data.html) {
          container.html(data.html);
          container.addClass('loaded');
          updateCategoryCount(section, data.count);

          // Announce to screen readers
          container.attr('aria-live', 'polite');
        } else {
          container.html('<div class="col-12"><div class="alert alert-warning">No content returned from server.</div></div>');
        }
      })
      .fail(function (xhr, status, error) {
        let errorMessage = 'Unable to load content. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.error) {
          errorMessage = xhr.responseJSON.error;
        }
        container.html('<div class="col-12"><div class="alert alert-danger">' + errorMessage + '</div></div>');
      });
  }

  /**
   * Load most read content from statistics
   */
  function loadMostRead() {
    const container = $('#most-read-content');

    // Show loading spinner
    container.html('<div class="col-12 text-center py-5"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading most read content...</span></div></div>');

    // Fetch most read content via AJAX
    $.get('/featured/most-read-ajax')
      .done(function (data) {
        container.html(data.html);
        container.addClass('loaded');
        updateCategoryCount('most-read', data.count);
      })
      .fail(function (xhr, status, error) {
        container.html('<div class="col-12"><div class="alert alert-warning">Unable to load most read content. Please try again.</div></div>');
      });
  }

  /**
   * Load category counts using conditional group endpoints
   */
  function loadCategoryCounts() {
    const sections = ['staff-picks', 'africa-section', 'politics-society', 'timelines'];

    // Load counts for each section via AJAX
    sections.forEach(function (section) {
      $.get('/featured/section/' + section)
        .done(function (data) {
          updateCategoryCount(section, data.count);
        })
        .fail(function (xhr, status, error) {
          updateCategoryCount(section, '?');
        });
    });

    // Load most read count via separate endpoint
    $.get('/featured/most-read-ajax')
      .done(function (data) {
        updateCategoryCount('most-read', data.count);
      })
      .fail(function (xhr, status, error) {
        updateCategoryCount('most-read', '?');
      });
  }

  /**
   * Sort featured content based on selected criteria
   */
  function sortFeaturedContent(sortType) {
    const container = $('#all-featured-grid');
    const items = container.find('.col-lg-6').get();

    items.sort(function (a, b) {
      switch (sortType) {
        case 'title':
          const titleA = $(a).data('title') || '';
          const titleB = $(b).data('title') || '';
          return titleA.localeCompare(titleB);

        case 'type':
          const typeA = $(a).data('node-type') || '';
          const typeB = $(b).data('node-type') || '';
          return typeA.localeCompare(typeB);

        case 'recent':
        default:
          const dateA = $(a).data('updated') || 0;
          const dateB = $(b).data('updated') || 0;
          return dateB - dateA;

 // Most recent first
      }
    });

    // Re-append sorted items
    $.each(items, function (index, item) {
      container.append(item);
    });
  }

  /**
   * Update category count display
   */
  function updateCategoryCount(category, count) {
    $('#' + category + '-count').text(count);
  }

})(jQuery, Drupal, once);