/**
 * @file
 * SAHO Featured Grid Component JavaScript
 * Drupal 11 SDC - Interactive functionality for featured content grid
 */

((Drupal, once) => {
  /**
   * SAHO Featured Grid behavior
   */
  Drupal.behaviors.sahoFeaturedGrid = {
    attach: (context, _settings) => {
      // Initialize category navigation
      once('saho-featured-grid', '.saho-featured-grid', context).forEach((grid) => {
        new SahoFeaturedGrid(grid);
      });
    },
  };

  /**
   * SAHO Featured Grid Class
   */
  function SahoFeaturedGrid(element) {
    this.grid = element;
    this.categoryButtons = this.grid.querySelectorAll('.saho-category-item');
    this.contentSections = this.grid.querySelectorAll('.featured-content-section');
    this.sortSelects = this.grid.querySelectorAll('select[id*="sort"]');

    this.init();
  }

  SahoFeaturedGrid.prototype.init = function () {
    this.bindEvents();
    this.initializeSorting();
    this.loadDynamicContent();
  };

  /**
   * Bind event listeners
   */
  SahoFeaturedGrid.prototype.bindEvents = function () {
    const self = this;

    // Category navigation
    this.categoryButtons.forEach((button) => {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        self.switchCategory(this);
      });

      // Keyboard support
      button.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          self.switchCategory(this);
        }
      });
    });

    // Sort functionality
    this.sortSelects.forEach((select) => {
      select.addEventListener('change', function () {
        self.sortContent(this);
      });
    });
  };

  /**
   * Switch between content categories
   */
  SahoFeaturedGrid.prototype.switchCategory = function (button) {
    const targetId = button.getAttribute('data-target');
    const targetSection = this.grid.querySelector(`#${targetId}`);

    if (!targetSection) return;

    // Update button states
    this.categoryButtons.forEach((btn) => {
      btn.classList.remove('active');
      btn.setAttribute('aria-selected', 'false');
    });

    button.classList.add('active');
    button.setAttribute('aria-selected', 'true');

    // Update section visibility
    this.contentSections.forEach((section) => {
      section.classList.remove('active');
      section.style.display = 'none';
    });

    targetSection.classList.add('active');
    targetSection.style.display = 'block';

    // Load dynamic content if needed
    if (targetId !== 'all-featured') {
      this.loadCategoryContent(targetId);
    }

    // Announce change to screen readers
    this.announceChange(button.textContent.trim());
  };

  /**
   * Sort content within active section
   */
  SahoFeaturedGrid.prototype.sortContent = function (select) {
    const sortValue = select.value;
    const activeSection = this.grid.querySelector('.featured-content-section.active');
    const gridContainer = activeSection.querySelector('.saho-landing-grid');

    if (!gridContainer) return;

    const items = Array.from(gridContainer.children);
    let sortedItems;

    switch (sortValue) {
      case 'title':
        sortedItems = items.sort((a, b) => {
          const titleA = a.getAttribute('data-title') || '';
          const titleB = b.getAttribute('data-title') || '';
          return titleA.localeCompare(titleB);
        });
        break;

      case 'type':
        sortedItems = items.sort((a, b) => {
          const typeA = a.getAttribute('data-node-type') || '';
          const typeB = b.getAttribute('data-node-type') || '';
          return typeA.localeCompare(typeB);
        });
        break;
      default:
        sortedItems = items.sort((a, b) => {
          const dateA = Number.parseInt(a.getAttribute('data-updated'), 10) || 0;
          const dateB = Number.parseInt(b.getAttribute('data-updated'), 10) || 0;
          return dateB - dateA; // Newest first
        });
        break;
    }

    // Re-append sorted items
    sortedItems.forEach((item) => {
      gridContainer.appendChild(item);
    });

    // Announce sort change
    this.announceChange(`Content sorted by ${select.options[select.selectedIndex].text}`);
  };

  /**
   * Initialize sorting functionality
   */
  SahoFeaturedGrid.prototype.initializeSorting = function () {
    // Set initial sort to "recent" and apply sorting
    this.sortSelects.forEach((select) => {
      if (select.value === 'recent') {
        const event = new Event('change');
        select.dispatchEvent(event);
      }
    });
  };

  /**
   * Load dynamic category content via AJAX
   */
  SahoFeaturedGrid.prototype.loadCategoryContent = function (categoryId) {
    const section = this.grid.querySelector(`#${categoryId}`);
    const contentContainer = section.querySelector(`#${categoryId}-content`);

    if (!contentContainer || contentContainer.hasAttribute('data-loaded')) {
      return;
    }

    // Show loading state
    contentContainer.innerHTML = `
      <div class="col-12 text-center py-5">
        <div class="spinner-border saho-text-primary" role="status" aria-live="polite">
          <span class="visually-hidden">Loading ${categoryId} content...</span>
        </div>
      </div>
    `;
    setTimeout(() => {
      this.renderCategoryContent(categoryId, contentContainer);
    }, 1000);
  };

  /**
   * Render category content by filtering existing items or showing info
   */
  SahoFeaturedGrid.prototype.renderCategoryContent = function (categoryId, container) {
    const allItemsGrid = this.grid.querySelector('#all-featured-grid');

    if (!allItemsGrid) {
      container.innerHTML = `
        <div class="col-12 text-center py-5">
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Unable to load content for this category.
          </div>
        </div>
      `;
      container.setAttribute('data-loaded', 'true');
      return;
    }

    // Filter items based on category
    const allItems = Array.from(allItemsGrid.children);
    let filteredItems = [];
    let categoryUrl = null;

    switch (categoryId) {
      case 'staff-picks':
        // Filter items with staff-pick data attribute
        filteredItems = allItems.filter((item) => item.getAttribute('data-staff-pick') === '1');
        break;

      case 'most-read':
        // Link to a separate page or show message
        categoryUrl = '/search?sort=views';
        break;

      case 'africa-section':
        categoryUrl = '/africa';
        break;

      case 'politics-society':
        categoryUrl = '/politics-society';
        break;

      case 'timelines':
        categoryUrl = '/timelines';
        break;

      default:
        filteredItems = allItems;
    }

    // If we have a URL, show link to the section
    if (categoryUrl) {
      const categoryName = categoryId.replace(/-/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
      container.innerHTML = `
        <div class="col-12 text-center py-5">
          <div class="saho-category-redirect p-4 rounded shadow-sm bg-white">
            <i class="fas fa-external-link-alt fa-2x mb-3 saho-text-primary"></i>
            <h4>Explore ${categoryName}</h4>
            <p class="text-muted mb-3">Visit our dedicated ${categoryName} section for more content.</p>
            <a href="${categoryUrl}" class="btn saho-bg-primary text-white px-4 py-2">
              <i class="fas fa-arrow-right me-2"></i>Go to ${categoryName}
            </a>
          </div>
        </div>
      `;
      container.setAttribute('data-loaded', 'true');
      return;
    }

    // Render filtered items
    if (filteredItems.length > 0) {
      container.innerHTML = '';
      filteredItems.forEach((item) => {
        const clone = item.cloneNode(true);
        container.appendChild(clone);
      });
    } else {
      const categoryName = categoryId.replace(/-/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
      container.innerHTML = `
        <div class="col-12 text-center py-5">
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No ${categoryName} items found in the current featured content.
          </div>
        </div>
      `;
    }

    container.setAttribute('data-loaded', 'true');
  };

  /**
   * Announce changes to screen readers
   */
  SahoFeaturedGrid.prototype.announceChange = (message) => {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'visually-hidden';
    announcement.textContent = message;

    document.body.appendChild(announcement);

    setTimeout(() => {
      document.body.removeChild(announcement);
    }, 1000);
  };

  /**
   * Public API for external integration
   */
  window.SahoFeaturedGrid = SahoFeaturedGrid;
})(Drupal, once);
