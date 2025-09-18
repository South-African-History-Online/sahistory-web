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
          const dateA = Number.parseInt(a.getAttribute('data-updated')) || 0;
          const dateB = Number.parseInt(b.getAttribute('data-updated')) || 0;
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
   * Render category content (placeholder - integrate with Drupal Views/AJAX)
   */
  SahoFeaturedGrid.prototype.renderCategoryContent = (categoryId, container) => {
    // This would typically be replaced with actual Drupal AJAX response
    const placeholderContent = `
      <div class="col-12 text-center py-5">
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          ${categoryId.replace('-', ' ').toUpperCase()} content would be loaded here via AJAX.
          <br><small>Integrate with Drupal Views REST API or custom endpoint.</small>
        </div>
      </div>
    `;

    container.innerHTML = placeholderContent;
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
