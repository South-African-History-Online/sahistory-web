/**
 * @file
 * SAHO Premium Timeline using TimelineJS3
 * https://timeline.knightlab.com/
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Premium Timeline behavior using TimelineJS3.
   */
  Drupal.behaviors.sahoTimelinePremium = {
    attach: function (context, settings) {
      const containers = once('saho-timeline-premium', '.timeline-premium', context);

      containers.forEach(function (container) {
        console.log('Initializing TimelineJS3 premium timeline...');

        // Get API endpoint from settings
        const apiEndpoint = (settings.sahoTimeline && settings.sahoTimeline.apiEndpoint) ? settings.sahoTimeline.apiEndpoint : '/api/timeline/events';

        // Create loading message with intro background
        container.innerHTML = `
          <div class="timeline-intro">
            <h1>South African History Timeline</h1>
            <p>Explore the rich history of South Africa through time</p>
          </div>
          <div class="timeline-loading">Loading premium timeline...</div>
        `;

        // Wait for TimelineJS3 to load before fetching events
        const checkTimelineJS = () => {
          if (typeof TL === 'undefined' || typeof TL.Timeline === 'undefined') {
            console.log('Waiting for TimelineJS3 to load...');
            setTimeout(checkTimelineJS, 200);
            return;
          }

          // TimelineJS3 is ready, fetch events (max safe limit without UTF-8 issues)
          fetch(apiEndpoint + '?limit=550')
            .then(response => response.json())
            .then(data => {
              if (data.events && data.events.length > 0) {
                initializeTimelineJS(container, data.events);
              } else {
                container.innerHTML = `
                  <div class="timeline-intro">
                    <h1>South African History Timeline</h1>
                    <p>Explore the rich history of South Africa through time</p>
                  </div>
                  <div class="timeline-error">No events found for timeline.</div>
                `;
              }
            })
            .catch(error => {
              console.error('Error loading timeline events:', error);
              container.innerHTML = `
                <div class="timeline-intro">
                  <h1>South African History Timeline</h1>
                  <p>Explore the rich history of South Africa through time</p>
                </div>
                <div class="timeline-error">Failed to load timeline. Please refresh the page.</div>
              `;
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

    // Set container ID for TimelineJS
    container.id = container.id || 'timeline-embed-' + Date.now();

    // TimelineJS3 options optimized for maximum 550 events display
    const options = {
      hash_bookmark: true,
      initial_zoom: 0, // Start fully zoomed out to see all events
      height: 750, // Even taller for better navigation
      language: 'en',
      timenav_position: 'bottom',
      optimal_tick_width: 40, // Much smaller for dense events
      scale_factor: 1, // Minimal scale factor for maximum density
      theme_color: '#97212d', // SAHO heritage red
      marker_height_min: 20, // Very small markers
      marker_width_min: 60, // Compact marker width
      marker_padding: 2, // Minimal padding
      start_at_slide: Math.floor(events.length / 2), // Start in middle of timeline
      menubar_height: 0,
      use_bc: true,
      duration: 600, // Fast transitions
      ease: 'easeInOutQuint',
      dragging: true,
      trackResize: true,
      slide_padding_lr: 60,
      slide_default_fade: '30%',
      zoom_sequence: [0.1, 0.25, 0.5, 1, 2, 4, 8, 16, 32, 64, 128], // Even more zoom out
      ga_property_id: null,
      track_events: ['nav_next', 'nav_previous', 'nav_zoom_in', 'nav_zoom_out'],
      timenav_height: 200, // Taller navigation for dense events
      timenav_height_percentage: 30, // More space for navigation
      slide_height_percentage: 70, // Less for slides to see more timeline
      marker_width_min_factor: 0.5 // Allow very small markers
    };

    // Initialize TimelineJS3
    window.timeline = new TL.Timeline(container.id, timelineData, options);

    console.log('TimelineJS3 initialized with', events.length, 'events');
    console.log('Timeline spans from', getDateRange(events));

    // Add custom styling class
    container.classList.add('saho-timeline-premium');
  }

  /**
   * Convert SAHO events to TimelineJS3 format.
   */
  function convertToTimelineJSFormat(events) {
    const timelineEvents = [];

    // Process each event
    events.forEach((event, index) => {
      // Parse date
      let startDate = parseEventDate(event.date);
      if (!startDate) { return; // Skip events without valid dates
      }

      // Create TimelineJS event object
      const tlEvent = {
        start_date: startDate,
        text: {
          headline: event.title || 'Untitled Event',
          text: formatEventDescription(event)
        }
      };

      // Add media if available
      if (event.image) {
        tlEvent.media = {
          url: event.image,
          caption: event.title,
          thumbnail: event.image
        };
      }

      // Add background color for different event types
      tlEvent.background = {
        color: getEventColor(event, index)
      };

      // Add unique ID
      tlEvent.unique_id = event.id || 'event-' + index;

      // Add group for categorization
      if (event.type) {
        tlEvent.group = event.type;
      }

      timelineEvents.push(tlEvent);
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
      eras: getHistoricalEras()
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
      console.warn('Could not parse date:', dateStr);
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
        default: return colors[index % colors.length];
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
   * Define historical eras for the timeline.
   */
  function getHistoricalEras() {
    return [
      {
        start_date: { year: 1400 },
        end_date: { year: 1650 },
        text: {
          headline: "Early Exploration & Contact"
        }
      },
      {
        start_date: { year: 1650 },
        end_date: { year: 1800 },
        text: {
          headline: "Colonial Establishment"
        }
      },
      {
        start_date: { year: 1800 },
        end_date: { year: 1900 },
        text: {
          headline: "Colonial Expansion"
        }
      },
      {
        start_date: { year: 1900 },
        end_date: { year: 1948 },
        text: {
          headline: "Union & Segregation"
        }
      },
      {
        start_date: { year: 1948 },
        end_date: { year: 1994 },
        text: {
          headline: "Apartheid Era"
        }
      },
      {
        start_date: { year: 1994 },
        end_date: { year: 2025 },
        text: {
          headline: "Democratic South Africa"
        }
      }
    ];
  }

})(jQuery, Drupal, once);