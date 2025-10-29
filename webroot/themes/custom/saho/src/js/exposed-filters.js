/**
 * @file
 * Modern exposed filters with collapsible panels and show more functionality.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.sahoExposedFilters = {
    attach: function (context, settings) {
      // Initialize each exposed form
      once('saho-exposed-filters', '.views-exposed-form', context).forEach(function (form) {
        initializeFilterPanel(form);
        initializeShowMore(form);
        initializeActiveFilters(form);
      });
    }
  };

  /**
   * Initialize collapsible filter panel.
   */
  function initializeFilterPanel(form) {
    // Check if we need to add a collapsible wrapper
    const befFilters = form.querySelector('.bef-exposed-filters');
    if (!befFilters) return;

    // Always start collapsed - show all content first, filters are optional
    const initiallyCollapsed = true;

    // Wrap filters in collapsible content
    if (!form.querySelector('.exposed-filters-content')) {
      const wrapper = document.createElement('div');
      wrapper.className = 'exposed-filters-content ' + (initiallyCollapsed ? 'collapsed' : 'expanded');
      befFilters.parentNode.insertBefore(wrapper, befFilters);
      wrapper.appendChild(befFilters);

      // Count total filter options available
      const totalOptions = form.querySelectorAll('input[type="checkbox"], input[type="radio"]').length;

      // Add header with toggle
      const header = document.createElement('div');
      header.className = 'exposed-filters-header';
      header.innerHTML = `
        <h3>Filter Results <span class="filter-hint">(${totalOptions} options)</span></h3>
        <span class="toggle-icon ${initiallyCollapsed ? '' : 'expanded'}">▼</span>
      `;
      wrapper.parentNode.insertBefore(header, wrapper);

      // Toggle functionality
      header.addEventListener('click', function() {
        const isExpanded = wrapper.classList.contains('expanded');
        const icon = header.querySelector('.toggle-icon');

        if (isExpanded) {
          wrapper.classList.remove('expanded');
          wrapper.classList.add('collapsed');
          icon.classList.remove('expanded');
        } else {
          wrapper.classList.remove('collapsed');
          wrapper.classList.add('expanded');
          icon.classList.add('expanded');
        }
      });
    }
  }

  /**
   * Initialize "show more" functionality for filter options.
   */
  function initializeShowMore(form) {
    const filterGroups = form.querySelectorAll('.bef-checkboxes, .bef-radios, .form-checkboxes, .form-radios');

    filterGroups.forEach(function(group) {
      const items = group.querySelectorAll('.form-item, .bef-checkbox, .bef-radio');
      const visibleCount = 5; // Show top 5 categories by count

      if (items.length > visibleCount) {
        // Create "show more" button
        const showMoreBtn = document.createElement('button');
        showMoreBtn.type = 'button';
        showMoreBtn.className = 'filter-show-more';
        showMoreBtn.innerHTML = 'Show more <span class="icon">▼</span>';

        // Insert after the filter group
        group.parentNode.insertBefore(showMoreBtn, group.nextSibling);

        // Toggle functionality
        showMoreBtn.addEventListener('click', function(e) {
          e.preventDefault();
          const isExpanded = group.classList.contains('show-all');

          if (isExpanded) {
            group.classList.remove('show-all');
            showMoreBtn.classList.remove('expanded');
            showMoreBtn.innerHTML = `Show more (${items.length - visibleCount}) <span class="icon">▼</span>`;

            // Scroll to button if needed
            showMoreBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          } else {
            group.classList.add('show-all');
            showMoreBtn.classList.add('expanded');
            showMoreBtn.innerHTML = 'Show less <span class="icon">▼</span>';
          }
        });

        // Update button text with count
        showMoreBtn.innerHTML = `Show more (${items.length - visibleCount}) <span class="icon">▼</span>`;
      }
    });
  }

  /**
   * Initialize active filters summary.
   */
  function initializeActiveFilters(form) {
    // Listen for changes on filter inputs
    const filterInputs = form.querySelectorAll('input[type="checkbox"], input[type="radio"], select');

    filterInputs.forEach(function(input) {
      input.addEventListener('change', function() {
        updateActiveFiltersSummary(form);
      });
    });

    // Initial update
    updateActiveFiltersSummary(form);
  }

  /**
   * Update the active filters summary display.
   */
  function updateActiveFiltersSummary(form) {
    const activeFilters = getActiveFilters(form);

    // Remove existing summary
    const existingSummary = form.querySelector('.active-filters-summary');
    if (existingSummary) {
      existingSummary.remove();
    }

    // Only show summary if there are active filters
    if (activeFilters.length === 0) return;

    // Create summary element
    const summary = document.createElement('div');
    summary.className = 'active-filters-summary';

    const label = document.createElement('span');
    label.className = 'summary-label';
    label.textContent = 'Active filters:';
    summary.appendChild(label);

    // Add tag for each active filter
    activeFilters.forEach(function(filter) {
      const tag = document.createElement('span');
      tag.className = 'active-filter-tag';
      tag.innerHTML = `
        ${filter.label}
        <span class="remove-filter" data-filter-id="${filter.id}">×</span>
      `;

      // Remove filter on click
      tag.querySelector('.remove-filter').addEventListener('click', function() {
        const input = form.querySelector(`#${filter.id}`);
        if (input) {
          input.checked = false;
          input.dispatchEvent(new Event('change'));

          // If autosubmit is enabled, submit the form
          if (form.querySelector('input[name="autosubmit_hidden"]')) {
            form.submit();
          }
        }
      });

      summary.appendChild(tag);
    });

    // Add "clear all" button
    if (activeFilters.length > 1) {
      const clearAll = document.createElement('button');
      clearAll.type = 'button';
      clearAll.className = 'clear-all-filters';
      clearAll.textContent = 'Clear all';
      clearAll.addEventListener('click', function() {
        activeFilters.forEach(function(filter) {
          const input = form.querySelector(`#${filter.id}`);
          if (input) {
            input.checked = false;
          }
        });

        // Submit form if autosubmit is enabled
        if (form.querySelector('input[name="autosubmit_hidden"]')) {
          form.submit();
        }

        updateActiveFiltersSummary(form);
      });
      summary.appendChild(clearAll);
    }

    // Insert summary at the top of the form
    form.insertBefore(summary, form.firstChild);
  }

  /**
   * Get all active filters from the form.
   */
  function getActiveFilters(form) {
    const active = [];
    const checkedInputs = form.querySelectorAll('input[type="checkbox"]:checked, input[type="radio"]:checked');

    checkedInputs.forEach(function(input) {
      // Skip if this is a hidden or system input
      if (input.name.includes('autosubmit') || input.classList.contains('visually-hidden')) {
        return;
      }

      const label = form.querySelector(`label[for="${input.id}"]`);
      if (label) {
        active.push({
          id: input.id,
          name: input.name,
          value: input.value,
          label: label.textContent.trim()
        });
      }
    });

    return active;
  }

})(Drupal, once);
