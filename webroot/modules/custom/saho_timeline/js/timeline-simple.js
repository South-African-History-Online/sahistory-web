/**
 * @file
 * Simple timeline fallback implementation.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.sahoTimelineSimple = {
    attach: function (context, settings) {
      // Only initialize if the interactive version hasn't already initialized
      const containers = once('saho-timeline-simple', '.timeline-interactive:not(.timeline-initialized)', context);
      
      containers.forEach(function(container) {
        container.classList.add('timeline-simple-mode');
        // Simple implementation that just loads and displays events
        const apiEndpoint = settings.sahoTimeline?.apiEndpoint || '/api/timeline/events';
        
        // Create simple structure
        container.innerHTML = `
          <div class="timeline-simple">
            <div class="timeline-header">
              <h2>South African History Timeline</h2>
              <div class="timeline-stats"></div>
            </div>
            <div class="timeline-filters-simple">
              <input type="text" class="timeline-search" placeholder="Search events...">
              <button class="timeline-filter-btn" data-period="all">All Time</button>
              <button class="timeline-filter-btn" data-period="pre-1500">Pre-1500</button>
              <button class="timeline-filter-btn" data-period="1500-1650">1500-1650</button>
              <button class="timeline-filter-btn" data-period="1650-1800">1650-1800</button>
              <button class="timeline-filter-btn" data-period="1800-1900">1800-1900</button>
              <button class="timeline-filter-btn" data-period="1900-1950">1900-1950</button>
              <button class="timeline-filter-btn" data-period="1950-1990">Apartheid Era</button>
              <button class="timeline-filter-btn" data-period="1990-2025">Democratic Era</button>
            </div>
            <div class="timeline-events-list">
              <div class="loading">Loading timeline events...</div>
            </div>
          </div>
        `;
        
        // Load events
        loadTimelineEvents(container, apiEndpoint);
        
        // Setup search
        const searchInput = container.querySelector('.timeline-search');
        let searchTimeout;
        searchInput.addEventListener('input', function() {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => {
            filterEvents(container, searchInput.value);
          }, 300);
        });
        
        // Setup period filters
        container.querySelectorAll('.timeline-filter-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            const period = this.dataset.period;
            filterByPeriod(container, period);
            
            // Update active state
            container.querySelectorAll('.timeline-filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
          });
        });
      });
    }
  };
  
  function loadTimelineEvents(container, endpoint) {
    fetch(endpoint + '?limit=550')
      .then(response => response.json())
      .then(data => {
        const eventsContainer = container.querySelector('.timeline-events-list');
        const statsContainer = container.querySelector('.timeline-stats');
        
        if (data.events && data.events.length > 0) {
          // Sort events by date
          data.events.sort((a, b) => (a.date || '').localeCompare(b.date || ''));
          
          // Group by year
          const eventsByYear = {};
          data.events.forEach(event => {
            const year = event.date ? event.date.substring(0, 4) : 'Unknown';
            if (!eventsByYear[year]) {
              eventsByYear[year] = [];
            }
            eventsByYear[year].push(event);
          });
          
          // Display stats
          const years = Object.keys(eventsByYear).filter(y => y !== 'Unknown');
          const minYear = Math.min(...years);
          const maxYear = Math.max(...years);
          statsContainer.innerHTML = `
            <span>${data.events.length} events</span>
            <span>${minYear} - ${maxYear}</span>
          `;
          
          // Render events
          eventsContainer.innerHTML = '';
          
          Object.keys(eventsByYear).sort().reverse().forEach(year => {
            const yearSection = document.createElement('div');
            yearSection.className = 'timeline-year-section';
            yearSection.dataset.year = year;
            
            yearSection.innerHTML = `
              <h3 class="timeline-year-header">${year}</h3>
              <div class="timeline-year-events">
                ${eventsByYear[year].map(event => `
                  <div class="timeline-event-card" data-date="${event.date || ''}" data-title="${(event.title || '').toLowerCase()}">
                    <div class="event-date">${formatDate(event.date)}</div>
                    <div class="event-title">${event.title || 'Untitled Event'}</div>
                    ${event.image ? `<img src="${event.image}" alt="${event.title}" class="event-image">` : ''}
                    ${event.body ? `<div class="event-description">${event.body}</div>` : ''}
                    ${event.url ? `<a href="${event.url}" class="event-link" target="_blank">Read more →</a>` : ''}
                  </div>
                `).join('')}
              </div>
            `;
            
            eventsContainer.appendChild(yearSection);
          });
        } else {
          eventsContainer.innerHTML = '<div class="no-events">No timeline events found.</div>';
        }
      })
      .catch(error => {
        console.error('Error loading timeline:', error);
        const eventsContainer = container.querySelector('.timeline-events-list');
        eventsContainer.innerHTML = '<div class="error">Failed to load timeline events. Please refresh the page.</div>';
      });
  }
  
  function formatDate(dateStr) {
    if (!dateStr) return '';
    try {
      const date = new Date(dateStr);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    } catch (e) {
      return dateStr;
    }
  }
  
  function filterEvents(container, searchTerm) {
    const term = searchTerm.toLowerCase();
    container.querySelectorAll('.timeline-event-card').forEach(card => {
      const title = card.dataset.title || '';
      const matches = title.includes(term);
      card.style.display = matches ? 'block' : 'none';
    });
    
    // Hide empty year sections
    container.querySelectorAll('.timeline-year-section').forEach(section => {
      const hasVisibleEvents = section.querySelectorAll('.timeline-event-card[style="block"]').length > 0 ||
                               section.querySelectorAll('.timeline-event-card:not([style])').length > 0;
      section.style.display = hasVisibleEvents || !term ? 'block' : 'none';
    });
  }
  
  function filterByPeriod(container, period) {
    const periodRanges = {
      'all': [0, 9999],
      'pre-1500': [0, 1499],
      '1500-1650': [1500, 1650],
      '1650-1800': [1650, 1800],
      '1800-1900': [1800, 1900],
      '1900-1950': [1900, 1950],
      '1950-1990': [1950, 1990],
      '1990-2025': [1990, 2025]
    };
    
    const [minYear, maxYear] = periodRanges[period] || [0, 9999];
    
    container.querySelectorAll('.timeline-year-section').forEach(section => {
      const year = parseInt(section.dataset.year) || 0;
      section.style.display = (year >= minYear && year <= maxYear) ? 'block' : 'none';
    });
  }

})(jQuery, Drupal, once);