/**
 * @file
 * Featured content page functionality.
 */

(($, Drupal) => {
  /**
   * Featured content navigation behavior.
   */
  Drupal.behaviors.featuredContentNavigation = {
    attach: (context, _settings) => {
      // Initialize once
      $('.view-featured-content', context)
        .once('featured-init')
        .each(() => {
          initializeFeaturedContent();
        });
    },
  };

  /**
   * Initialize featured content functionality.
   */
  function initializeFeaturedContent() {
    // Category navigation
    $('.saho-category-item').on('click', function (e) {
      e.preventDefault();

      const $this = $(this);
      const target = $this.data('target');

      // Update active states
      $('.saho-category-item').removeClass('active');
      $this.addClass('active');

      // Show/hide content sections
      $('.featured-content-section').hide();
      $(`#${target}`).show();

      // Load content if needed
      loadSectionContent(target);
    });

    // Sort functionality
    $('select[id^="sort-"]').on('change', function () {
      const sortBy = this.value;
      const $section = $(this).closest('.featured-content-section');
      sortContent($section, sortBy);
    });

    // Load initial counts
    loadCategoryCounts();
  }

  /**
   * Load content for a specific section.
   */
  function loadSectionContent(section) {
    const $container = $(`#${section}-content`);

    // Skip if already loaded or is the main section
    if ($container.hasClass('loaded') || section === 'all-featured') {
      return;
    }

    // For staff picks, filter from existing content
    if (section === 'staff-picks') {
      loadStaffPicks();
      return;
    }

    // For most read, use statistics
    if (section === 'most-read') {
      loadMostRead();
      return;
    }

    // Mark as loaded
    $container.addClass('loaded');
  }

  /**
   * Load staff picks from existing content.
   */
  function loadStaffPicks() {
    const $container = $('#staff-picks-content');

    // Clone items from the main view
    const $allItems = $('#all-featured-grid .col-lg-6');
    let count = 0;

    // Clear container
    $container.empty();

    // Clone first 8 items for staff picks demo
    $allItems.each(function (index) {
      if (index < 8) {
        const $clone = $(this).clone();
        // Ensure the saho-grid-item wrapper is present
        if (!$clone.find('.saho-grid-item').length) {
          $clone.wrapInner('<div class="saho-grid-item"></div>');
        }
        $container.append($clone);
        count++;
      }
    });

    if (count > 0) {
      $container.addClass('loaded');
      updateCategoryCount('staff-picks', count);
    } else {
      $container.html(
        '<div class="col-12"><div class="alert alert-info">No staff picks available.</div></div>'
      );
    }
  }

  /**
   * Load most read content.
   */
  function loadMostRead() {
    const $container = $('#most-read-content');

    // Clone items from the main view
    const $allItems = $('#all-featured-grid .col-lg-6');
    let count = 0;

    // Clear container
    $container.empty();

    // Take every 3rd item for most read demo
    $allItems.each(function (index) {
      if (index % 3 === 0 && count < 6) {
        const $clone = $(this).clone();
        // Ensure the saho-grid-item wrapper is present
        if (!$clone.find('.saho-grid-item').length) {
          $clone.wrapInner('<div class="saho-grid-item"></div>');
        }
        $container.append($clone);
        count++;
      }
    });

    if (count > 0) {
      $container.addClass('loaded');
      updateCategoryCount('most-read', count);
    } else {
      $container.html(
        '<div class="col-12"><div class="alert alert-info">Statistics module not configured yet.</div></div>'
      );
    }
  }

  /**
   * Load category counts.
   */
  function loadCategoryCounts() {
    // Count items and set sidebar counts
    const totalCount = $('#all-featured-grid .col-lg-6').length;
    const staffPicksCount = Math.min(8, totalCount);
    const mostReadCount = Math.min(6, Math.floor(totalCount / 3));

    // Update counts with proper formatting
    updateCategoryCount('staff-picks', staffPicksCount);
    updateCategoryCount('most-read', mostReadCount);
  }

  /**
   * Update category count display.
   */
  function updateCategoryCount(category, count) {
    $(`#${category}-count`).text(count);
  }

  /**
   * Sort content within a section.
   */
  function sortContent($section, sortBy) {
    const $grid = $section.find('.saho-landing-grid');
    const $items = $grid.find('.col-lg-6');

    if ($items.length === 0) {
      return;
    }

    const sorted = $items.toArray().sort((a, b) => {
      const $a = $(a);
      const $b = $(b);

      switch (sortBy) {
        case 'title': {
          const titleA = $a.find('.node__title a, h3 a, h2 a').first().text().toLowerCase();
          const titleB = $b.find('.node__title a, h3 a, h2 a').first().text().toLowerCase();
          return titleA.localeCompare(titleB);
        }

        case 'type': {
          const typeA = $a.find('.node').attr('class') || '';
          const typeB = $b.find('.node').attr('class') || '';
          return typeA.localeCompare(typeB);
        }
        default:
          // Preserve original order (already sorted by date)
          return 0;
      }
    });

    // Re-append in sorted order
    $grid.empty().append(sorted);
  }
})(jQuery, Drupal);
