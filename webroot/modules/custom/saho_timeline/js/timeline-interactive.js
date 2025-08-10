/**
 * @file
 * Modern Interactive Timeline with smooth scrolling and zoom functionality.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Timeline interactive behaviors.
   */
  Drupal.behaviors.sahoTimelineInteractive = {
    attach: function (context, settings) {
      // Debug logging
      console.log('Timeline behavior attaching...', context);
      
      const timelines = once('saho-timeline-interactive', '.timeline-interactive', context);
      console.log('Found timelines:', timelines.length);
      
      timelines.forEach(function(timeline) {
        console.log('Initializing interactive timeline on element:', timeline);
        try {
          // Mark as initialized to prevent simple version from running
          timeline.classList.add('timeline-initialized');
          timeline.classList.remove('timeline-simple-mode');
          new ModernInteractiveTimeline(timeline, settings.sahoTimeline || {});
        } catch (error) {
          console.error('Error initializing interactive timeline:', error);
          // Remove initialized flag so simple version can take over
          timeline.classList.remove('timeline-initialized');
        }
      });
    }
  };

  /**
   * Modern Interactive Timeline class.
   */
  class ModernInteractiveTimeline {
    constructor(element, settings) {
      this.element = element;
      this.settings = $.extend({
        apiEndpoint: '/api/timeline/events',
        minYear: 1000,
        maxYear: 2025,
        currentZoom: 1,
        zoomLevels: [
          { name: 'overview', scale: 0.5, label: 'Overview' },
          { name: 'century', scale: 1, label: 'Century View' },
          { name: 'decade', scale: 2, label: 'Decade View' },
          { name: 'year', scale: 4, label: 'Year View' },
          { name: 'detail', scale: 8, label: 'Detail View' }
        ]
      }, settings);
      
      this.events = [];
      this.currentYearRange = { start: 1800, end: 2025 };
      this.isLoading = false;
      this.canvasWidth = 5000;
      this.isDragging = false;
      this.dragStart = { x: 0, scrollLeft: 0 };
      
      this.init();
    }

    /**
     * Initialize the modern timeline.
     */
    init() {
      console.log('Timeline init starting...');
      try {
        this.createTimelineStructure();
        console.log('Structure created');
        this.setupEventListeners();
        console.log('Event listeners attached');
        this.loadEvents();
        console.log('Loading events...');
        this.setupDragScroll();
        this.setupKeyboardNavigation();
        this.updateControlStates();
        // Disable auto-update for now to reduce noise
        // this.startAutoUpdate();
      } catch (error) {
        console.error('Timeline init error:', error);
        this.showError('Failed to initialize timeline: ' + error.message);
      }
    }

    /**
     * Create the HTML structure for the timeline.
     */
    createTimelineStructure() {
      const structure = `
        <div class="timeline-container">
          <div class="timeline-viewport">
            <div class="timeline-canvas">
              <div class="timeline-track"></div>
              <div class="timeline-events"></div>
              <div class="timeline-dates"></div>
            </div>
          </div>
        </div>
        
        <div class="timeline-controls">
          <button class="timeline-control-button" data-action="zoom-in" title="Zoom In">🔍+</button>
          <button class="timeline-control-button" data-action="zoom-out" title="Zoom Out">🔍-</button>
          <button class="timeline-control-button" data-action="filters" title="Filters">⚙️</button>
          <button class="timeline-control-button" data-action="reset" title="Reset View">🏠</button>
        </div>
        
        <div class="timeline-zoom-indicator">
          ${this.settings.zoomLevels[this.settings.currentZoom].label}
        </div>
        
        <div class="timeline-filters">
          <h3>Timeline Filters</h3>
          <div class="timeline-filter-section">
            <label>Search Events:</label>
            <input type="text" id="timeline-search" placeholder="Search..." />
          </div>
          <div class="timeline-filter-section">
            <label>Date Range:</label>
            <input type="number" id="start-year" placeholder="Start Year" min="1000" max="2025" />
            <input type="number" id="end-year" placeholder="End Year" min="1000" max="2025" />
          </div>
          <div class="timeline-filter-section">
            <label>Themes:</label>
            <select id="timeline-themes" multiple>
              <option value="liberation-struggle">Liberation Struggle</option>
              <option value="apartheid">Apartheid</option>
              <option value="cultural-heritage">Cultural Heritage</option>
              <option value="womens-history">Women's History</option>
              <option value="education">Education</option>
              <option value="politics">Politics</option>
            </select>
          </div>
        </div>
        
        <button class="timeline-filters-toggle" title="Toggle Filters">🎛️</button>
        
        <div class="timeline-loading" style="display: none;">Loading...</div>
      `;
      
      this.element.innerHTML = structure;
      
      // Wait a bit for DOM to be ready
      setTimeout(() => {
        this.viewport = this.element.querySelector('.timeline-viewport');
        this.canvas = this.element.querySelector('.timeline-canvas');
        this.eventsContainer = this.element.querySelector('.timeline-events');
        this.datesContainer = this.element.querySelector('.timeline-dates');
        this.filtersPanel = this.element.querySelector('.timeline-filters');
        
        // Verify elements were created
        console.log('Timeline elements found:', {
          viewport: !!this.viewport,
          canvas: !!this.canvas,
          eventsContainer: !!this.eventsContainer,
          datesContainer: !!this.datesContainer,
          filtersPanel: !!this.filtersPanel
        });
        
        if (!this.viewport || !this.canvas || !this.eventsContainer) {
          console.error('Missing timeline elements:', {
            viewport: this.viewport,
            canvas: this.canvas,
            eventsContainer: this.eventsContainer
          });
          throw new Error('Failed to create timeline structure');
        }
        
        console.log('Timeline structure verified successfully');
      }, 10);
      
      // For immediate access, still set these
      this.viewport = this.element.querySelector('.timeline-viewport');
      this.canvas = this.element.querySelector('.timeline-canvas');
      this.eventsContainer = this.element.querySelector('.timeline-events');
      this.datesContainer = this.element.querySelector('.timeline-dates');
      this.filtersPanel = this.element.querySelector('.timeline-filters');
    }

    /**
     * Setup event listeners.
     */
    setupEventListeners() {
      // Control buttons
      this.element.addEventListener('click', (e) => {
        const action = e.target.dataset.action;
        if (action) {
          this.handleControlAction(action);
        }
      });

      // Filter toggle
      const filtersToggle = this.element.querySelector('.timeline-filters-toggle');
      filtersToggle.addEventListener('click', () => {
        this.filtersPanel.classList.toggle('open');
      });

      // Search input
      const searchInput = this.element.querySelector('#timeline-search');
      let searchTimeout;
      searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => this.applyFilters(), 300);
      });

      // Date range inputs
      const startYear = this.element.querySelector('#start-year');
      const endYear = this.element.querySelector('#end-year');
      [startYear, endYear].forEach(input => {
        input.addEventListener('change', () => this.applyFilters());
      });

      // Themes select
      const themesSelect = this.element.querySelector('#timeline-themes');
      themesSelect.addEventListener('change', () => this.applyFilters());

      // Wheel zoom
      this.viewport.addEventListener('wheel', (e) => {
        if (e.ctrlKey || e.metaKey) {
          e.preventDefault();
          if (e.deltaY < 0) {
            this.zoomIn();
          } else {
            this.zoomOut();
          }
        }
      });

      // Window resize
      window.addEventListener('resize', () => this.updateLayout());
    }

    /**
     * Setup drag scrolling.
     */
    setupDragScroll() {
      this.viewport.addEventListener('mousedown', (e) => {
        this.isDragging = true;
        this.dragStart.x = e.clientX;
        this.dragStart.scrollLeft = this.viewport.scrollLeft;
        this.viewport.style.cursor = 'grabbing';
      });

      this.viewport.addEventListener('mousemove', (e) => {
        if (!this.isDragging) return;
        e.preventDefault();
        const x = e.clientX - this.dragStart.x;
        this.viewport.scrollLeft = this.dragStart.scrollLeft - x;
      });

      this.viewport.addEventListener('mouseup', () => {
        this.isDragging = false;
        this.viewport.style.cursor = 'grab';
      });

      this.viewport.addEventListener('mouseleave', () => {
        this.isDragging = false;
        this.viewport.style.cursor = 'grab';
      });

      // Touch events for mobile
      this.setupTouchEvents();
    }

    /**
     * Setup touch events for mobile devices.
     */
    setupTouchEvents() {
      let touchStart = null;
      
      this.viewport.addEventListener('touchstart', (e) => {
        touchStart = {
          x: e.touches[0].clientX,
          scrollLeft: this.viewport.scrollLeft
        };
      });

      this.viewport.addEventListener('touchmove', (e) => {
        if (!touchStart) return;
        e.preventDefault();
        const x = e.touches[0].clientX - touchStart.x;
        this.viewport.scrollLeft = touchStart.scrollLeft - x;
      });

      this.viewport.addEventListener('touchend', () => {
        touchStart = null;
      });
    }

    /**
     * Setup keyboard navigation.
     */
    setupKeyboardNavigation() {
      document.addEventListener('keydown', (e) => {
        if (!this.isTimelineInFocus()) return;
        
        switch(e.code) {
          case 'ArrowLeft':
            e.preventDefault();
            this.viewport.scrollLeft -= 100;
            break;
          case 'ArrowRight':
            e.preventDefault();
            this.viewport.scrollLeft += 100;
            break;
          case 'Equal':
          case 'NumpadAdd':
            if (e.ctrlKey) {
              e.preventDefault();
              this.zoomIn();
            }
            break;
          case 'Minus':
          case 'NumpadSubtract':
            if (e.ctrlKey) {
              e.preventDefault();
              this.zoomOut();
            }
            break;
          case 'Home':
            e.preventDefault();
            this.viewport.scrollLeft = 0;
            break;
          case 'End':
            e.preventDefault();
            this.viewport.scrollLeft = this.canvas.scrollWidth;
            break;
        }
      });
    }

    /**
     * Check if timeline is in focus.
     */
    isTimelineInFocus() {
      const rect = this.element.getBoundingClientRect();
      return rect.top < window.innerHeight && rect.bottom > 0;
    }

    /**
     * Handle control button actions.
     */
    handleControlAction(action) {
      try {
        switch(action) {
          case 'zoom-in':
            this.zoomIn();
            break;
          case 'zoom-out':
            this.zoomOut();
            break;
          case 'filters':
            if (this.filtersPanel) {
              this.filtersPanel.classList.toggle('open');
            } else {
              console.warn('Filters panel not found');
            }
            break;
          case 'reset':
            this.resetView();
            break;
          default:
            console.warn('Unknown control action:', action);
        }
      } catch (error) {
        console.error('Error handling control action:', action, error);
      }
    }

    /**
     * Zoom in to show more detail.
     */
    zoomIn() {
      if (this.settings.currentZoom < this.settings.zoomLevels.length - 1) {
        const oldScrollPosition = this.viewport ? this.viewport.scrollLeft : 0;
        const oldScrollRatio = this.canvasWidth > 0 ? oldScrollPosition / this.canvasWidth : 0;
        
        this.settings.currentZoom++;
        console.log(`Zooming in to level ${this.settings.currentZoom}`);
        this.updateZoom();
        
        // Try to maintain relative scroll position after zoom
        if (this.viewport && this.canvasWidth > 0) {
          setTimeout(() => {
            this.viewport.scrollLeft = oldScrollRatio * this.canvasWidth;
          }, 100);
        }
      } else {
        console.log('Already at maximum zoom level');
      }
    }

    /**
     * Zoom out to show broader view.
     */
    zoomOut() {
      if (this.settings.currentZoom > 0) {
        const oldScrollPosition = this.viewport ? this.viewport.scrollLeft : 0;
        const oldScrollRatio = this.canvasWidth > 0 ? oldScrollPosition / this.canvasWidth : 0;
        
        this.settings.currentZoom--;
        console.log(`Zooming out to level ${this.settings.currentZoom}`);
        this.updateZoom();
        
        // Try to maintain relative scroll position after zoom
        if (this.viewport && this.canvasWidth > 0) {
          setTimeout(() => {
            this.viewport.scrollLeft = oldScrollRatio * this.canvasWidth;
          }, 100);
        }
      } else {
        console.log('Already at minimum zoom level');
      }
    }

    /**
     * Update zoom level and redraw timeline.
     */
    updateZoom() {
      try {
        const zoomLevel = this.settings.zoomLevels[this.settings.currentZoom];
        if (!zoomLevel) {
          console.error('Invalid zoom level:', this.settings.currentZoom);
          return;
        }
        
        const indicator = this.element.querySelector('.timeline-zoom-indicator');
        if (indicator) {
          indicator.textContent = zoomLevel.label;
        }
        
        this.updateLayout();
        
        // Only re-render if we have events
        if (this.events && this.events.length > 0) {
          this.renderEvents();
          this.renderDateMarkers();
        } else {
          console.log('No events to re-render after zoom');
        }
      } catch (error) {
        console.error('Error updating zoom:', error);
      }
      
      // Update control states
      this.updateControlStates();
    }
    
    /**
     * Update control button states based on current zoom level.
     */
    updateControlStates() {
      const zoomInBtn = this.element.querySelector('[data-action="zoom-in"]');
      const zoomOutBtn = this.element.querySelector('[data-action="zoom-out"]');
      
      if (zoomInBtn) {
        if (this.settings.currentZoom >= this.settings.zoomLevels.length - 1) {
          zoomInBtn.style.opacity = '0.5';
          zoomInBtn.style.cursor = 'not-allowed';
          zoomInBtn.title = 'Maximum zoom reached';
        } else {
          zoomInBtn.style.opacity = '1';
          zoomInBtn.style.cursor = 'pointer';
          zoomInBtn.title = 'Zoom In';
        }
      }
      
      if (zoomOutBtn) {
        if (this.settings.currentZoom <= 0) {
          zoomOutBtn.style.opacity = '0.5';
          zoomOutBtn.style.cursor = 'not-allowed';
          zoomOutBtn.title = 'Minimum zoom reached';
        } else {
          zoomOutBtn.style.opacity = '1';
          zoomOutBtn.style.cursor = 'pointer';
          zoomOutBtn.title = 'Zoom Out';
        }
      }
    }

    /**
     * Reset view to default state.
     */
    resetView() {
      this.settings.currentZoom = 1;
      this.currentYearRange = { start: 1800, end: 2025 };
      this.viewport.scrollLeft = this.canvasWidth * 0.7; // Start near modern era
      
      // Clear filters
      this.element.querySelector('#timeline-search').value = '';
      this.element.querySelector('#start-year').value = '';
      this.element.querySelector('#end-year').value = '';
      this.element.querySelector('#timeline-themes').selectedIndex = -1;
      
      this.updateZoom();
      this.loadEvents();
    }

    /**
     * Apply current filters and reload events.
     */
    applyFilters() {
      const filters = this.getCurrentFilters();
      this.loadEvents(filters);
    }

    /**
     * Get current filter values.
     */
    getCurrentFilters() {
      return {
        keywords: this.element.querySelector('#timeline-search').value,
        start_date: this.element.querySelector('#start-year').value,
        end_date: this.element.querySelector('#end-year').value,
        themes: Array.from(this.element.querySelector('#timeline-themes').selectedOptions)
                     .map(option => option.value)
      };
    }

    /**
     * Load events from API.
     */
    async loadEvents(filters = {}) {
      if (this.isLoading) return;
      
      console.log('Loading events with filters:', filters);
      this.isLoading = true;
      this.showLoading();
      
      try {
        const params = new URLSearchParams({
          limit: 550, // Working limit - 2.75x more events than before
          ...filters
        });
        
        const url = `${this.settings.apiEndpoint}?${params}`;
        console.log('Fetching from:', url);
        
        const response = await fetch(url);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Received data:', data);
        
        this.events = data.events || [];
        console.log(`Loaded ${this.events.length} events`);
        
        if (this.events.length > 0) {
          this.renderEvents();
          this.renderDateMarkers();
        } else {
          console.warn('No events received from API');
          this.showError('No events found. Try adjusting your filters.');
        }
        
      } catch (error) {
        console.error('Failed to load timeline events:', error);
        this.showError('Failed to load timeline events: ' + error.message);
      } finally {
        this.isLoading = false;
        this.hideLoading();
      }
    }

    /**
     * Render events on the timeline.
     */
    renderEvents() {
      console.log('Rendering events:', this.events.length);
      
      if (!this.eventsContainer) {
        console.error('Events container not found');
        return;
      }
      
      this.eventsContainer.innerHTML = '';
      
      if (!this.events.length) {
        console.warn('No events to render');
        return;
      }
      
      const yearSpan = this.settings.maxYear - this.settings.minYear;
      let alternatePosition = false; // Alternate above/below timeline
      let renderedCount = 0;
      
      this.events.forEach((event, index) => {
        try {
          // More robust date parsing
          let eventDate;
          let year;
          
          if (event.date) {
            // Handle different date formats
            const dateStr = event.date.toString();
            if (index < 3) console.log(`Processing event: "${event.title}" with date: "${dateStr}"`);
            
            if (dateStr.includes('T')) {
              // ISO format like "2000-01-29T00:00:00"
              eventDate = new Date(dateStr);
            } else if (dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
              // YYYY-MM-DD format
              eventDate = new Date(dateStr + 'T00:00:00');
            } else {
              // Try parsing as-is
              eventDate = new Date(dateStr);
            }
            
            year = eventDate.getFullYear();
          } else {
            console.warn('Event has no date:', event.title);
            eventDate = new Date('1900-01-01T00:00:00');
            year = 1900;
          }
          
          // Validate year
          if (isNaN(year) || year < this.settings.minYear || year > this.settings.maxYear) {
            console.warn(`Event year ${year} out of range for event: "${event.title}"`);
            return;
          }
          
          // Calculate position based on year
          const yearProgress = (year - this.settings.minYear) / yearSpan;
          const xPosition = yearProgress * this.canvasWidth;
          
          // Debug key positioning for first few events
          if (index < 5) {
            console.log(`Event ${index}: "${event.title}" (${year}) -> ${xPosition}px`);
          }
          
          // Create event element
          const eventElement = this.createEventElement(event, index);
          eventElement.style.left = `${xPosition}px`;
          
          // Alternate positioning to avoid overlap
          if (alternatePosition) {
            eventElement.classList.add('above');
          } else {
            eventElement.classList.add('below');
          }
          alternatePosition = !alternatePosition;
          
          this.eventsContainer.appendChild(eventElement);
          renderedCount++;
          
          // Event successfully added
        } catch (error) {
          console.error('Error rendering event:', event, error);
        }
      });
      
      console.log(`Rendered ${renderedCount} events successfully`);
    }

    /**
     * Create an individual event element.
     */
    createEventElement(event, index) {
      const eventEl = document.createElement('div');
      eventEl.className = 'timeline-event';
      eventEl.style.animationDelay = `${index * 0.05}s`;
      
      // Add image class if event has image
      if (event.image) {
        eventEl.classList.add('has-image');
      }
      
      eventEl.innerHTML = `
        <div class="timeline-event-marker"></div>
        <div class="timeline-event-content">
          <div class="timeline-event-title">${event.title || 'Untitled Event'}</div>
          <div class="timeline-event-date">${this.formatEventDate(event.date)}</div>
          ${event.image ? `<img src="${event.image}" alt="${event.title}" class="timeline-event-image" />` : ''}
          <div class="timeline-event-description">${event.body || ''}</div>
        </div>
      `;
      
      // Click handler for full details
      eventEl.addEventListener('click', () => {
        if (event.url) {
          window.open(event.url, '_blank');
        }
      });
      
      return eventEl;
    }

    /**
     * Format event date for display.
     */
    formatEventDate(dateStr) {
      if (!dateStr) return '';
      
      const date = new Date(dateStr);
      if (isNaN(date.getTime())) return dateStr;
      
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    }

    /**
     * Render date markers along the timeline.
     */
    renderDateMarkers() {
      this.datesContainer.innerHTML = '';
      
      const zoomLevel = this.settings.zoomLevels[this.settings.currentZoom];
      const yearSpan = this.settings.maxYear - this.settings.minYear;
      
      let interval, format;
      
      // Determine marker interval based on zoom level
      switch(zoomLevel.name) {
        case 'century':
          interval = 100;
          format = (year) => `${Math.floor(year/100)}00s`;
          break;
        case 'decade':
          interval = 50;
          format = (year) => `${year}`;
          break;
        case 'year':
          interval = 25;
          format = (year) => `${year}`;
          break;
        default:
          interval = 10;
          format = (year) => `${year}`;
      }
      
      // Create date markers
      for (let year = this.settings.minYear; year <= this.settings.maxYear; year += interval) {
        const yearProgress = (year - this.settings.minYear) / yearSpan;
        const xPosition = yearProgress * this.canvasWidth;
        
        const marker = document.createElement('div');
        marker.className = 'timeline-date-marker';
        marker.style.left = `${xPosition}px`;
        marker.textContent = format(year);
        
        // Major markers for significant years
        if (year % (interval * 2) === 0) {
          marker.classList.add('major');
        }
        
        this.datesContainer.appendChild(marker);
      }
    }

    /**
     * Update layout and canvas size.
     */
    updateLayout() {
      const zoomLevel = this.settings.zoomLevels[this.settings.currentZoom];
      
      // Calculate reasonable canvas width based on zoom level
      const baseWidth = 5000;
      this.canvasWidth = baseWidth * zoomLevel.scale;
      
      // Ensure minimum and maximum reasonable widths
      this.canvasWidth = Math.max(2000, Math.min(this.canvasWidth, 50000));
      
      if (this.canvas) {
        this.canvas.style.minWidth = `${this.canvasWidth}px`;
        console.log(`Updated canvas width to ${this.canvasWidth}px (zoom: ${zoomLevel.label})`);
      }
    }

    /**
     * Start auto-update interval.
     */
    startAutoUpdate() {
      // Auto-refresh every 5 minutes to get new content
      setInterval(() => {
        if (!this.isLoading) {
          this.loadEvents(this.getCurrentFilters());
        }
      }, 300000);
    }

    /**
     * Show loading indicator.
     */
    showLoading() {
      const loader = this.element.querySelector('.timeline-loading');
      if (loader) {
        loader.style.display = 'block';
      }
      // Also hide the initial loader if it exists
      const initialLoader = this.element.querySelector('.timeline-loading-initial');
      if (initialLoader) {
        initialLoader.style.display = 'none';
      }
    }

    /**
     * Hide loading indicator.
     */
    hideLoading() {
      const loader = this.element.querySelector('.timeline-loading');
      if (loader) {
        loader.style.display = 'none';
      }
    }

    /**
     * Show error message.
     */
    showError(message) {
      const errorEl = document.createElement('div');
      errorEl.className = 'timeline-error';
      errorEl.textContent = message;
      errorEl.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #ff6b6b;
        color: white;
        padding: 20px;
        border-radius: 8px;
        z-index: 1000;
      `;
      
      document.body.appendChild(errorEl);
      
      setTimeout(() => {
        errorEl.remove();
      }, 5000);
    }
  }

})(jQuery, Drupal, once);