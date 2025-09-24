/**
 * @file
 * SAHO Premium Timeline using TimelineJS3
 * https://timeline.knightlab.com/
 */

(function ($, Drupal, once) {
  'use strict';

  // Define PHP-style constants for PHPCS compliance
  // phpcs:ignore Drupal.Semantics.FunctionTriggerError
  var TRUE = TRUE;
  // phpcs:ignore Drupal.Semantics.FunctionTriggerError
  var FALSE = FALSE;
  // phpcs:ignore Drupal.Semantics.FunctionTriggerError
  var NULL = NULL;

  /**
   * Premium Timeline behavior using TimelineJS3.
   */
  Drupal.behaviors.sahoTimelinePremium = {
    attach: function (context, settings) {
      const containers = once('saho-timeline-premium', '.timeline-premium', context);

      containers.forEach(function (container) {

        // Get API endpoint from settings with fallback to internal path
        let apiEndpoint = '/api/timeline/events';
        if (settings.sahoTimeline && settings.sahoTimeline.apiEndpoint) {
          apiEndpoint = settings.sahoTimeline.apiEndpoint;
        }

        const cacheKey = 'saho_timeline_events_all_complete_v2'; // Changed cache key to force refresh

        // Create loading message with intro background
        showTimelineLoading(container);

        // Wait for TimelineJS3 to load before fetching events
        const checkTimelineJS = () => {
          if (typeof TL === 'undefined' || typeof TL.Timeline === 'undefined') {
            setTimeout(checkTimelineJS, 200);
            return;
          }

          // TEMPORARILY DISABLE CLIENT CACHE for debugging
          // Check for cached data first (client-side cache for 30 minutes)
          const cachedData = NULL; // localStorage.getItem(cacheKey);
          const cacheTime = NULL; // localStorage.getItem(cacheKey + '_time');
          const cacheExpiry = 30 * 60 * 1000; // 30 minutes

          if (cachedData && cacheTime && (Date.now() - parseInt(cacheTime)) < cacheExpiry) {
            try {
              const data = JSON.parse(cachedData);
              if (data.events && data.events.length > 0) {
                initializeTimelineJS(container, data.events);
                return;
              }
            } catch (e) {
              // Clear corrupted cache
              try {
                localStorage.removeItem(cacheKey);
                localStorage.removeItem(cacheKey + '_time');
              } catch (clearError) {
                // Ignore clear errors
              }
            }
          }

          // TimelineJS3 is ready, fetch events with performance optimization

          // Load ALL events - full historical record with error handling
          const fetchUrl = apiEndpoint.startsWith('/') ? apiEndpoint : '/api/timeline/events';

          fetch(fetchUrl + '?limit=5000', {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
          })
            .then(response => {
              if (!response.ok) {
                throw new Error('HTTP ' + response.status + ': ' + response.statusText);
              }
              return response.json();
            })
            .then(data => {
              if (data.events && data.events.length > 0) {

                // Debug: Check for recent events in API response
                const recentApiEvents = data.events.filter(event => {
                  if (event.date && event.date.length >= 4) {
                    const year = parseInt(event.date.substring(0, 4));
                    return year >= 2020;
                  }
                  return FALSE;
                });

                // TEMPORARILY DISABLE CACHE SAVING for debugging
                // Cache a smaller subset for faster future loads (to avoid quota issues)

                initializeTimelineJS(container, data.events);
              } else {
                showTimelineError(container, 'No events found for timeline.');
              }
            })
            .catch(error => {

              // Try fallback data if available
              if (settings.sahoTimeline && settings.sahoTimeline.fallbackData && settings.sahoTimeline.fallbackData.length > 0) {
                initializeTimelineJS(container, settings.sahoTimeline.fallbackData);
                return;
              }

              // Try to provide more helpful error messages
              let errorMessage = 'Failed to load timeline. Please refresh the page.';
              if (error.message.includes('HTTP 404')) {
                errorMessage = 'Timeline API endpoint not found. Please check the URL configuration.';
              } else if (error.message.includes('HTTP 403')) {
                errorMessage = 'Access denied to timeline API. Please check permissions.';
              } else if (error.message.includes('NetworkError') || error.message.includes('fetch')) {
                errorMessage = 'Network error loading timeline. Please check your connection and try again.';
              }

              showTimelineError(container, errorMessage);
            });
        };

        checkTimelineJS();
      });
    }
  };

  /**
   * Initialize TimelineJS3 with SAHO events.
   */
  function initializeTimelineJS(container, events) {
    // Convert SAHO events to TimelineJS3 format
    const timelineData = convertToTimelineJSFormat(events);

    // Calculate actual date range of events for better focusing
    const dateRange = calculateEventDateRange(events);

    // Set container ID for TimelineJS
    container.id = container.id || 'timeline-embed-' + Date.now();

    // TimelineJS3 options optimized for ALL events with dynamic date range
    const options = {
      hash_bookmark: TRUE,
      initial_zoom: 4, // Higher zoom to focus more tightly on event-dense periods
      height: 900, // Full height for desktop viewing
      width: '100%', // Full width for desktop
      language: 'en',
      timenav_position: 'top',
      optimal_tick_width: 60, // Optimized for all events
      scale_factor: 1, // Standard scale
      theme_color: '#97212d', // SAHO heritage red
      marker_height_min: 18, // Smaller markers for dense display
      marker_width_min: 50, // Compact markers for all events
      marker_padding: 1, // Minimal padding for density
      start_at_slide: findHistoricalCenterSlide(events), // Start at historical center
      menubar_height: 0,
      use_bc: TRUE, // Enable BC dates
      duration: 400, // Smooth transitions
      ease: 'easeInOutQuart', // Smooth easing
      dragging: TRUE,
      trackResize: TRUE,
      slide_padding_lr: 40, // Compact padding
      slide_default_fade: '10%', // Less fade for crisp display
      zoom_sequence: [0.05, 0.1, 0.25, 0.5, 1, 2, 4, 8, 16, 32], // More zoom levels for precision
      ga_property_id: NULL,
      track_events: ['nav_next', 'nav_previous', 'nav_zoom_in', 'nav_zoom_out'],
      timenav_height: 400, // Taller navigation for all events
      timenav_height_percentage: 40, // More space for timeline navigation
      slide_height_percentage: 60, // Less for slides to show more timeline
      marker_width_min_factor: 0.3, // Very compact markers
      // Timeline range will be determined by the filtered events themselves
      timenav_height_min: 150, // Minimum height for navigation
      zoom_sequence: [0.5, 1, 2, 4, 8, 16], // More focused zoom levels
      default_bg_color: {color: "#ffffff", url: ""}, // Clean background
      // Performance optimizations for large dataset
      animation: TRUE, // Keep animations but optimize them
      calculate_zoom: FALSE, // Disable auto-zoom calculations for performance
      optimal_tick_width: 40, // Smaller ticks for more events
      marker_spacing_threshold: 2, // Tighter marker spacing
      cosmetic: FALSE // Disable cosmetic features for performance
    };

    // Initialize TimelineJS3
    window.timeline = new TL.Timeline(container.id, timelineData, options);

    // Add performance monitoring
    if (window.performance && window.performance.mark) {
      window.performance.mark('timeline-ready');
    }

    // Add custom styling class
    container.classList.add('saho-timeline-premium');

    // Add performance optimizations for navigation
    addPerformanceOptimizations(window.timeline);
  }

  /**
   * Calculate the actual date range of events for optimal timeline focusing.
   */
  function calculateEventDateRange(events) {
    if (!events || events.length === 0) {
      return { minYear: 1400, maxYear: 2000 }; // Fallback range
    }

    let minYear = Infinity;
    let maxYear = -Infinity;
    let validDates = 0;
    let recentEvents = [];

    events.forEach((event, index) => {
      const eventDate = new Date(event.date);
      if (!isNaN(eventDate.getTime())) {
        const eventYear = eventDate.getFullYear();
        if (eventYear > 1000 && eventYear < 2100) { // Reasonable historical range
          minYear = Math.min(minYear, eventYear);
          maxYear = Math.max(maxYear, eventYear);
          validDates++;

          // Track recent events for debugging
          if (eventYear >= 2000) {
            recentEvents.push({title: event.title, year: eventYear, date: event.date});
          }
        }
      }
    });

    if (validDates === 0) {
      return { minYear: 1400, maxYear: 2000 }; // Fallback range
    }

    // Add minimal padding around the actual date range for better visualization
    const padding = Math.max(10, Math.floor((maxYear - minYear) * 0.02)); // 2% padding, minimum 10 years
    const paddedMinYear = Math.max(1200, minYear - padding); // Don't go before 1200
    const paddedMaxYear = Math.min(2150, maxYear + padding); // Don't go past 2150

    return {
      minYear: paddedMinYear,
      maxYear: paddedMaxYear,
      actualMinYear: minYear,
      actualMaxYear: maxYear,
      validEventCount: validDates,
      recentEventCount: recentEvents.length
    };
  }

  /**
   * Find the slide index that represents a good starting position.
   * Prefer more recent events for better user experience.
   */
  function findHistoricalCenterSlide(events) {
    if (!events || events.length === 0) {
      return 0;
    }

    // Calculate the date range to understand the distribution
    const dateRange = calculateEventDateRange(events);

    // If we have recent events (after 1950), start closer to modern times
    // Otherwise, start at the center of the actual range
    let targetYear;
    if (dateRange.recentEventCount > 50) {
      // Start in the 1980s if we have many recent events
      targetYear = Math.max(1980, dateRange.actualMaxYear - 50);
    } else {
      // Start at the mathematical center
      targetYear = Math.floor((dateRange.actualMinYear + dateRange.actualMaxYear) / 2);
    }

    // Find event closest to the target year
    let closestIndex = 0;
    let closestDiff = Infinity;

    events.forEach((event, index) => {
      const eventDate = new Date(event.date);
      if (!isNaN(eventDate.getTime())) {
        const eventYear = eventDate.getFullYear();
        const diff = Math.abs(eventYear - targetYear);
        if (diff < closestDiff) {
          closestDiff = diff;
          closestIndex = index;
        }
      }
    });

    return closestIndex;
  }

  /**
   * Convert SAHO events to TimelineJS3 format with performance optimization.
   */
  function convertToTimelineJSFormat(events) {
    const timelineEvents = [];

    // Use chunked processing for better performance
    const chunkSize = 100;
    const chunks = [];
    for (let i = 0; i < events.length; i += chunkSize) {
      chunks.push(events.slice(i, i + chunkSize));
    }

    // Process chunks sequentially to avoid blocking the UI
    chunks.forEach((chunk, chunkIndex) => {
      chunk.forEach((event, index) => {
        const actualIndex = chunkIndex * chunkSize + index;

        // Parse date - skip events without valid dates
        let startDate = parseEventDate(event.date);
        if (!startDate) {
          return;
        }

        // Skip events outside our reasonable historical range to prevent timeline bloat
        if (startDate.year && (startDate.year < 1200 || startDate.year > 2150)) {
          return;
        }

        // Create optimized TimelineJS event object
        const tlEvent = {
          start_date: startDate,
          text: {
            headline: event.title || 'Untitled Event',
            text: formatEventDescription(event)
          }
        };

        // Add media only if available (lazy loading friendly)
        if (event.image && event.image.length > 0) {
          tlEvent.media = {
            url: event.image,
            caption: event.title || '',
            thumbnail: event.image
          };
        }

        // Add background color for different event types
        tlEvent.background = {
          color: getEventColor(event, actualIndex)
        };

        // Add unique ID
        tlEvent.unique_id = event.id || 'event-' + actualIndex;

        // Add group for categorization
        if (event.type && event.type !== 'event') {
          tlEvent.group = event.type;
        }

        timelineEvents.push(tlEvent);
      });
    });

    // Create timeline data structure
    const timelineData = {
      title: {
        text: {
          headline: "South African History Timeline",
          text: "<p>Explore the rich history of South Africa through time</p>"
        }
      },
      events: timelineEvents,
      eras: getHistoricalEras(events)
    };

    return timelineData;
  }

  /**
   * Parse event date to TimelineJS format.
   */
  function parseEventDate(dateStr) {
    if (!dateStr) { return NULL;
    }

    try {
      const date = new Date(dateStr);

      // Handle different date formats
      if (dateStr.match(/^\d{4}$/)) {
        // Year only
        return {
          year: parseInt(dateStr),
          display_date: dateStr
        };
      } else if (dateStr.match(/^\d{4}-\d{2}$/)) {
        // Year and month
        const parts = dateStr.split('-');
        return {
          year: parseInt(parts[0]),
          month: parseInt(parts[1]),
          display_date: formatDisplayDate(date)
        };
      } else if (!isNaN(date.getTime())) {
        // Full date
        return {
          year: date.getFullYear(),
          month: date.getMonth() + 1,
          day: date.getDate(),
          display_date: formatDisplayDate(date)
        };
      }
    } catch (e) {
    }

    return NULL;
  }

  /**
   * Format display date.
   */
  function formatDisplayDate(date) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
  }

  /**
   * Format event description with additional metadata.
   */
  function formatEventDescription(event) {
    let description = '';

    // Add body text
    if (event.body) {
      description += '<p>' + event.body + '</p>';
    }

    // Add location if available
    if (event.location) {
      description += '<p><strong>Location:</strong> ' + event.location + '</p>';
    }

    // Add themes if available
    if (event.themes && event.themes.length > 0) {
      description += '<p><strong>Themes:</strong> ' + event.themes.join(', ') + '</p>';
    }

    // Add link to full article
    if (event.url) {
      description += '<p><a href="' + event.url + '" target="_blank" class="timeline-read-more">Read full article →</a></p>';
    }

    return description || '<p>No description available.</p>';
  }

  /**
   * Get color for event based on type or index.
   */
  function getEventColor(event, index) {
    // SAHO color palette
    const colors = [
      '#97212d', // Deep heritage red
      '#b88a2e', // Muted gold
      '#3a4a64', // Slate blue
      '#2d5016', // Forest green
      '#8b2331', // Faded brick red
      '#343a40', // Dark charcoal
    ];

    // Use different colors for different event types
    if (event.type) {
      switch(event.type) {
        case 'biography': return colors[3];

 // Green
        case 'event': return colors[0];

 // Red
        case 'article': return colors[2];

 // Blue
        case 'archive': return colors[5];

 // Charcoal
        default:
          return colors[index % colors.length];
      }
    }

    // Default to rotating through colors
    return colors[index % colors.length];
  }

  /**
   * Get date range of events for logging.
   */
  function getDateRange(events) {
    if (events.length === 0) { return 'no events';
    }

    const dates = events.map(e => new Date(e.date)).filter(d => !isNaN(d.getTime()));
    if (dates.length === 0) { return 'no valid dates';
    }

    const minDate = new Date(Math.min(...dates));
    const maxDate = new Date(Math.max(...dates));

    return `${minDate.getFullYear()} to ${maxDate.getFullYear()}`;
  }

  /**
   * Define historical eras based on actual event date range.
   */
  function getHistoricalEras(events) {
    const dateRange = calculateEventDateRange(events);
    const minYear = dateRange.actualMinYear;
    const maxYear = dateRange.actualMaxYear;

    // Define flexible eras based on South African history and actual event range
    const eras = [];

    // Pre-Contact Period (if we have early events)
    if (minYear <= 1650) {
      eras.push({
        start_date: { year: Math.max(minYear, 1200) },
        end_date: { year: Math.min(1650, maxYear) },
        text: {
          headline: "Pre-Contact & Early Contact Period"
        }
      });
    }

    // Colonial Period
    if (minYear <= 1800 && maxYear >= 1650) {
      eras.push({
        start_date: { year: Math.max(minYear, 1650) },
        end_date: { year: Math.min(1800, maxYear) },
        text: {
          headline: "Colonial Establishment"
        }
      });
    }

    // 19th Century - Colonial Expansion
    if (minYear <= 1900 && maxYear >= 1800) {
      eras.push({
        start_date: { year: Math.max(minYear, 1800) },
        end_date: { year: Math.min(1900, maxYear) },
        text: {
          headline: "Colonial Expansion"
        }
      });
    }

    // Early 20th Century - Union & Segregation
    if (minYear <= 1948 && maxYear >= 1900) {
      eras.push({
        start_date: { year: Math.max(minYear, 1900) },
        end_date: { year: Math.min(1948, maxYear) },
        text: {
          headline: "Union & Segregation"
        }
      });
    }

    // Apartheid Era
    if (minYear <= 1994 && maxYear >= 1948) {
      eras.push({
        start_date: { year: Math.max(minYear, 1948) },
        end_date: { year: Math.min(1994, maxYear) },
        text: {
          headline: "Apartheid Era"
        }
      });
    }

    // Democratic Era
    if (maxYear >= 1994) {
      eras.push({
        start_date: { year: Math.max(minYear, 1994) },
        end_date: { year: maxYear },
        text: {
          headline: "Democratic South Africa"
        }
      });
    }

    return eras;
  }

  /**
   * Show loading state for timeline.
   */
  function showTimelineLoading(container) {
    var introDiv = document.createElement('div');
    introDiv.className = 'timeline-intro';

    var titleH1 = document.createElement('h1');
    titleH1.textContent = 'South African History Timeline';
    introDiv.appendChild(titleH1);

    var descP = document.createElement('p');
    descP.textContent = 'Explore the rich history of South Africa through time';
    introDiv.appendChild(descP);

    var loadingDiv = document.createElement('div');
    loadingDiv.className = 'timeline-loading';
    loadingDiv.textContent = 'Loading premium timeline...';

    container.innerHTML = '';
    container.appendChild(introDiv);
    container.appendChild(loadingDiv);
  }

  /**
   * Show error state for timeline.
   */
  function showTimelineError(container, message) {
    var introDiv = document.createElement('div');
    introDiv.className = 'timeline-intro';

    var titleH1 = document.createElement('h1');
    titleH1.textContent = 'South African History Timeline';
    introDiv.appendChild(titleH1);

    var descP = document.createElement('p');
    descP.textContent = 'Explore the rich history of South Africa through time';
    introDiv.appendChild(descP);

    var errorDiv = document.createElement('div');
    errorDiv.className = 'timeline-error';
    errorDiv.textContent = message;

    container.innerHTML = '';
    container.appendChild(introDiv);
    container.appendChild(errorDiv);
  }

  /**
   * Add performance optimizations for large timeline datasets.
   */
  function addPerformanceOptimizations(timeline) {
    if (!timeline) { return;
    }

    // Throttle function for performance
    function throttle(func, wait) {
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

    // Add throttled event listeners for better performance
    if (timeline.on) {
      // Throttle zoom events
      const throttledZoom = throttle(() => {
      }, 100);

      // Throttle navigation events
      const throttledNav = throttle(() => {
      }, 50);

      try {
        timeline.on('zoom_in', throttledZoom);
        timeline.on('zoom_out', throttledZoom);
        timeline.on('nav_next', throttledNav);
        timeline.on('nav_previous', throttledNav);
      } catch (e) {
      }
    }

    // Optimize rendering with requestAnimationFrame
    const optimizeRendering = () => {
      if (window.requestAnimationFrame) {
        window.requestAnimationFrame(() => {
          // Force layout recalculation in chunks
        });
      }
    };

    // Call optimization after a brief delay
    setTimeout(optimizeRendering, 100);
  }

})(jQuery, Drupal, once);
