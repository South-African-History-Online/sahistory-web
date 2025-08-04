/**
 * @file
 * JavaScript behaviors for the Entity Overview block.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Behavior for Entity Overview block.
   */
  Drupal.behaviors.entityOverview = {
    attach: function (context, settings) {
      // Process each entity overview block once.
      once('entity-overview-processed', '.entity-overview-block', context).forEach(function (element) {
        // Initialize the block with settings.
        const blockId = element.getAttribute('data-block-id');
        const blockSettings = settings.entityOverview && settings.entityOverview[blockId]
          ? settings.entityOverview[blockId]
          : {};

        // Initialize the block.
        initEntityOverviewBlock(element, blockSettings);

        // Only initialize display mode toggle if explicitly enabled
        if (blockSettings.showDisplayToggle === true) {
          initDisplayModeToggle(element, blockSettings.displayMode || 'default');
        } else {
          // If toggle is disabled, just apply the configured display mode
          setDisplayMode(element, blockSettings.displayMode || 'default');
        }
      });
    }
  };

  /**
   * Initialize display mode toggle functionality.
   *
   * @param {HTMLElement} element
   *   The block element.
   * @param {string} initialMode
   *   The initial display mode from configuration.
   */
  function initDisplayModeToggle(element, initialMode) {
    // Look for Bootstrap button group toggle in the new template structure
    const displayToggleButtons = element.querySelectorAll('.entity-display-toggle');
    
    if (displayToggleButtons.length > 0) {
      // Handle existing Bootstrap toggle buttons
      displayToggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
          const mode = this.getAttribute('data-mode');
          setDisplayMode(element, mode);
          
          // Update active state using Bootstrap classes
          const buttonGroup = this.parentElement;
          buttonGroup.querySelectorAll('.btn').forEach(function(btn) {
            btn.classList.remove('active');
          });
          this.classList.add('active');
          
          // Store preference in localStorage
          try {
            if (window.localStorage) {
              localStorage.setItem('entityOverviewDisplayMode', mode);
            }
          } catch (e) {
            // Local storage not available
          }
        });
      });
      
      // Set initial display mode
      setDisplayMode(element, initialMode);
      
      // Check for stored preference
      try {
        if (window.localStorage) {
          const storedMode = localStorage.getItem('entityOverviewDisplayMode');
          if (storedMode && storedMode !== initialMode) {
            const storedButton = element.querySelector(`.entity-display-toggle[data-mode="${storedMode}"]`);
            if (storedButton) {
              storedButton.click();
            }
          }
        }
      } catch (e) {
        // Local storage not available
      }
    }
  }

  /**
   * Set the display mode for the entity overview block.
   *
   * @param {HTMLElement} element
   *   The block element.
   * @param {string} mode
   *   The display mode: 'default', 'compact', or 'full-width'.
   */
  function setDisplayMode(element, mode) {
    const itemsContainer = element.querySelector('.entity-overview-items');
    if (!itemsContainer) return;
    
    // Remove existing display mode classes
    itemsContainer.classList.remove('display-mode-default', 'display-mode-compact', 'display-mode-full-width');
    
    // Add the new display mode class
    itemsContainer.classList.add('display-mode-' + mode);
    
    // Update the grid layout based on mode
    if (mode === 'compact') {
      itemsContainer.style.gridTemplateColumns = 'repeat(auto-fill, minmax(250px, 1fr))';
    } else if (mode === 'full-width') {
      itemsContainer.style.gridTemplateColumns = '1fr';
    } else {
      itemsContainer.style.gridTemplateColumns = 'repeat(auto-fill, minmax(300px, 1fr))';
    }
  }

  /**
   * Initialize an entity overview block.
   *
   * @param {HTMLElement} element
   *   The block element.
   * @param {Object} settings
   *   The block settings.
   */
  function initEntityOverviewBlock(element, settings) {
    // Apply initial display mode from configuration if not already set
    if (settings.displayMode && settings.displayMode !== 'default') {
      // Only apply if not already set by the template
      if (!element.classList.contains('compact') && !element.classList.contains('full-width')) {
        setDisplayMode(element, settings.displayMode);
      }
    }
    // Find the controls and items container.
    const controls = element.querySelector('.entity-overview-controls');
    const itemsContainer = element.querySelector('.entity-overview-items');
    const loadMoreButton = element.querySelector('.entity-overview-load-more button');

    // If no controls or items container, exit early.
    if (!controls || !itemsContainer) {
      return;
    }

    // Store the current state.
    const state = {
      page: 0,
      filter: '',
      sort: '',
      loading: false,
      hasMore: true
    };

    // Handle filter changes.
    const filterSelect = controls.querySelector('.entity-overview-filter select');
    if (filterSelect) {
      filterSelect.addEventListener('change', function () {
        state.filter = this.value;
        state.page = 0;
        state.hasMore = true;
        loadEntities(true);
      });
    }

    // Handle sort changes.
    const sortSelect = controls.querySelector('.entity-overview-sort select');
    if (sortSelect) {
      sortSelect.addEventListener('change', function () {
        state.sort = this.value;
        state.page = 0;
        state.hasMore = true;
        loadEntities(true);
      });
    }

    // Handle load more button.
    if (loadMoreButton) {
      loadMoreButton.addEventListener('click', function () {
        if (!state.loading && state.hasMore) {
          state.page++;
          loadEntities(false);
        }
      });
    }

    /**
     * Load entities via AJAX.
     *
     * @param {boolean} replace
     *   Whether to replace the existing items or append to them.
     */
    function loadEntities(replace) {
      // Set loading state.
      state.loading = true;

      // Show loading indicator.
      if (loadMoreButton) {
        loadMoreButton.classList.add('is-loading');
        loadMoreButton.disabled = true;
      }

      // Prepare the request data.
      const data = {
        blockId: settings.blockId,
        page: state.page,
        filter: state.filter,
        sort: state.sort
      };

      // Make the AJAX request.
      Drupal.ajax({
        url: Drupal.url('entity-overview/ajax/load'),
        submit: data,
        success: function (response) {
          // Process the response.
          if (response.items) {
            // Update the items container.
            if (replace) {
              // Clear the container first.
              itemsContainer.innerHTML = '';
            }

            // Add the new items.
            response.items.forEach(function (itemHtml) {
              const tempDiv = document.createElement('div');
              tempDiv.innerHTML = itemHtml;
              const item = tempDiv.firstChild;
              itemsContainer.appendChild(item);
            });

            // Update the state.
            state.hasMore = response.hasMore;
          }

          // Reset loading state.
          state.loading = false;

          // Update load more button.
          if (loadMoreButton) {
            loadMoreButton.classList.remove('is-loading');
            loadMoreButton.disabled = false;

            // Hide the button if there are no more items.
            if (!state.hasMore) {
              loadMoreButton.parentNode.style.display = 'none';
            } else {
              loadMoreButton.parentNode.style.display = '';
            }
          }
        },
        error: function () {
          // Reset loading state.
          state.loading = false;

          // Update load more button.
          if (loadMoreButton) {
            loadMoreButton.classList.remove('is-loading');
            loadMoreButton.disabled = false;
          }

          // Show an error message.
          Drupal.announce(Drupal.t('An error occurred while loading entities.'));
        }
      }).execute();
    }
  }

})(jQuery, Drupal, drupalSettings);