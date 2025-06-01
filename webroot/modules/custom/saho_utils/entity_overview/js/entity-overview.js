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
        if (blockSettings.showDisplayToggle === TRUE) {
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
    // Check if display toggle exists (might be added by template)
    const displayToggle = element.querySelector('.entity-overview-display-toggle');

    // If no toggle exists and toggle is enabled, create one
    if (!displayToggle) {
      const toggleContainer = document.createElement('div');
      toggleContainer.className = 'entity-overview-display-toggle';

      // Create toggle buttons
      const defaultButton = document.createElement('button');
      defaultButton.textContent = Drupal.t('Default');
      defaultButton.setAttribute('data-mode', 'default');
      defaultButton.className = initialMode === 'default' ? 'active' : '';

      const compactButton = document.createElement('button');
      compactButton.textContent = Drupal.t('Compact');
      compactButton.setAttribute('data-mode', 'compact');
      compactButton.className = initialMode === 'compact' ? 'active' : '';

      const fullWidthButton = document.createElement('button');
      fullWidthButton.textContent = Drupal.t('Full Width');
      fullWidthButton.setAttribute('data-mode', 'full-width');
      fullWidthButton.className = initialMode === 'full-width' ? 'active' : '';

      // Add buttons to container
      toggleContainer.appendChild(defaultButton);
      toggleContainer.appendChild(compactButton);
      toggleContainer.appendChild(fullWidthButton);

      // Add container after the title
      const title = element.querySelector('.entity-overview-title');
      if (title) {
        title.parentNode.insertBefore(toggleContainer, title.nextSibling);
      } else {
        element.insertBefore(toggleContainer, element.firstChild);
      }

      // Add event listeners to buttons
      toggleContainer.querySelectorAll('button').forEach(function (button) {
        button.addEventListener('click', function () {
          const mode = this.getAttribute('data-mode');
          setDisplayMode(element, mode);

          // Update active button
          toggleContainer.querySelectorAll('button').forEach(function (btn) {
            btn.classList.remove('active');
          });
          this.classList.add('active');

          // Store preference in localStorage if available
          try {
            if (window.localStorage) {
              localStorage.setItem('entityOverviewDisplayMode', mode);
            }
          } catch (e) {
            // Local storage not available or permission denied
          }
        });
      });

      // Set initial display mode from configuration
      setDisplayMode(element, initialMode);

      // Check for stored preference only if not using the configured mode
      try {
        if (window.localStorage) {
          const storedMode = localStorage.getItem('entityOverviewDisplayMode');
          if (storedMode && storedMode !== initialMode) {
            // Find and click the corresponding button
            const storedButton = toggleContainer.querySelector(`button[data - mode = "${storedMode}"]`);
            if (storedButton) {
              storedButton.click();
            }
          }
        }
      } catch (e) {
        // Local storage not available or permission denied
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
    // Remove existing mode classes
    element.classList.remove('compact', 'full-width');

    // Add new mode class if not default
    if (mode !== 'default') {
      element.classList.add(mode);
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
      loading: FALSE,
      hasMore: TRUE
    };

    // Handle filter changes.
    const filterSelect = controls.querySelector('.entity-overview-filter select');
    if (filterSelect) {
      filterSelect.addEventListener('change', function () {
        state.filter = this.value;
        state.page = 0;
        state.hasMore = TRUE;
        loadEntities(TRUE);
      });
    }

    // Handle sort changes.
    const sortSelect = controls.querySelector('.entity-overview-sort select');
    if (sortSelect) {
      sortSelect.addEventListener('change', function () {
        state.sort = this.value;
        state.page = 0;
        state.hasMore = TRUE;
        loadEntities(TRUE);
      });
    }

    // Handle load more button.
    if (loadMoreButton) {
      loadMoreButton.addEventListener('click', function () {
        if (!state.loading && state.hasMore) {
          state.page++;
          loadEntities(FALSE);
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
      state.loading = TRUE;

      // Show loading indicator.
      if (loadMoreButton) {
        loadMoreButton.classList.add('is-loading');
        loadMoreButton.disabled = TRUE;
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
          state.loading = FALSE;

          // Update load more button.
          if (loadMoreButton) {
            loadMoreButton.classList.remove('is-loading');
            loadMoreButton.disabled = FALSE;

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
          state.loading = FALSE;

          // Update load more button.
          if (loadMoreButton) {
            loadMoreButton.classList.remove('is-loading');
            loadMoreButton.disabled = FALSE;
          }

          // Show an error message.
          Drupal.announce(Drupal.t('An error occurred while loading entities.'));
        }
      }).execute();
    }
  }

})(jQuery, Drupal, drupalSettings);