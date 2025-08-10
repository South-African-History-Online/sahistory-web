/**
 * @file
 * SAHO Timeline JavaScript behaviors.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Initialize timeline functionality.
   */
  Drupal.behaviors.sahoTimeline = {
    attach: function (context, settings) {
      const timelines = once('saho-timeline-init', '.saho-timeline', context);

      timelines.forEach(function (timeline) {
        initializeTimeline(timeline);
      });
    }
  };

  /**
   * Initialize a timeline element.
   */
  function initializeTimeline(element) {
    const $timeline = $(element);

    // Initialize scroll animations.
    initScrollAnimations($timeline);

    // Initialize filter handlers.
    initFilterHandlers($timeline);

    // Initialize period toggles.
    initPeriodToggles($timeline);
  }

  /**
   * Initialize scroll-based animations.
   */
  function initScrollAnimations($timeline) {
    const events = $timeline.find('.saho-timeline-event');

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            $(entry.target).addClass('saho-timeline-event--visible');
            observer.unobserve(entry.target);
          }
        });
      }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
      });

      events.each(function () {
        observer.observe(this);
      });
    } else {
      // Fallback for browsers without IntersectionObserver.
      events.addClass('saho-timeline-event--visible');
    }
  }

  /**
   * Initialize filter handlers.
   */
  function initFilterHandlers($timeline) {
    const $filters = $timeline.find('.saho-timeline__filters');

    if ($filters.length) {
      $filters.on('change', 'select, input[type="checkbox"]', function () {
        applyFilters($timeline);
      });

      $filters.on('click', '.saho-timeline__filter-clear', function (e) {
        e.preventDefault();
        clearFilters($timeline);
      });
    }
  }

  /**
   * Apply active filters to the timeline.
   */
  function applyFilters($timeline) {
    const filters = getActiveFilters($timeline);
    const $loading = $timeline.find('.saho-timeline__loading');

    // Show loading indicator.
    $loading.show();

    // Make AJAX request to get filtered events.
    $.ajax({
      url: drupalSettings.sahoTimeline.apiEndpoint,
      type: 'GET',
      data: filters,
      success: function (response) {
        updateTimelineContent($timeline, response);
      },
      error: function () {
        console.error('Failed to load timeline events');
      },
      complete: function () {
        $loading.hide();
      }
    });
  }

  /**
   * Get active filter values.
   */
  function getActiveFilters($timeline) {
    const filters = {};
    const $filterContainer = $timeline.find('.saho-timeline__filters');

    $filterContainer.find('select').each(function () {
      const name = $(this).attr('name');
      const value = $(this).val();
      if (value && value !== 'all') {
        filters[name] = value;
      }
    });

    $filterContainer.find('input[type="checkbox"]:checked').each(function () {
      const name = $(this).attr('name');
      if (!filters[name]) {
        filters[name] = [];
      }
      filters[name].push($(this).val());
    });

    return filters;
  }

  /**
   * Clear all filters.
   */
  function clearFilters($timeline) {
    const $filters = $timeline.find('.saho-timeline__filters');

    $filters.find('select').val('all');
    $filters.find('input[type="checkbox"]').prop('checked', FALSE);

    applyFilters($timeline);
  }

  /**
   * Update timeline content with new events.
   */
  function updateTimelineContent($timeline, response) {
    const $content = $timeline.find('.saho-timeline__content');

    // Replace content with new events.
    if (response.html) {
      $content.html(response.html);

      // Re-attach behaviors to new content.
      Drupal.attachBehaviors($content[0]);
    }
  }

  /**
   * Initialize period toggle functionality.
   */
  function initPeriodToggles($timeline) {
    $timeline.on('click', '.saho-timeline__period-header', function () {
      const $period = $(this).parent();
      const $events = $period.find('.saho-timeline__events');

      $period.toggleClass('saho-timeline__period--collapsed');
      $events.slideToggle(300);
    });
  }

  /**
   * Helper function to format dates.
   */
  Drupal.sahoTimeline = {
    formatDate: function (dateString) {
      const date = new Date(dateString);
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      return date.toLocaleDateString('en-US', options);
    },

    /**
     * Scroll to a specific event.
     */
    scrollToEvent: function (eventId) {
      const $event = $('#' + eventId);
      if ($event.length) {
        $('html, body').animate({
          scrollTop: $event.offset().top - 100
        }, 500);

        // Highlight the event.
        $event.addClass('saho-timeline-event--highlight');
        setTimeout(function () {
          $event.removeClass('saho-timeline-event--highlight');
        }, 2000);
      }
    }
  };

})(jQuery, Drupal, once);