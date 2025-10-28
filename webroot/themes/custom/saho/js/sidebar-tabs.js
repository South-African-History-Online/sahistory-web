/**
 * @file
 * SAHO Sidebar Tabs JavaScript
 *
 * Provides interactive functionality for the tabbed sidebar interface
 * including tab switching, accessibility features, and responsive behavior.
 */

(($, Drupal, once) => {
  /**
   * Initialize SAHO sidebar tabs functionality.
   */
  Drupal.behaviors.sahoSidebarTabs = {
    attach: (context, _settings) => {
      once('saho-sidebar-tabs', '.saho-sidebar-tabs', context).forEach((element) => {
        const $tabsContainer = $(element);
        initializeTabs($tabsContainer);
      });
    },
  };

  /**
   * Initialize tab functionality for a tabs container.
   *
   * @param {jQuery} $container - The tabs container element
   */
  function initializeTabs($container) {
    const $nav = $container.find('.saho-tabs-nav');
    const $buttons = $nav.find('.saho-tab-button');
    const _$panes = $container.find('.saho-tab-pane');

    // Set up click handlers
    $buttons.on('click', function (e) {
      e.preventDefault();
      const $button = $(this);
      const targetId = $button.attr('data-tab');

      switchTab($container, targetId);
    });

    // Set up keyboard navigation
    $buttons.on('keydown', (e) => {
      handleTabKeyboard(e, $buttons, $container);
    });

    // Initialize the first tab as active if none is set
    if (!$buttons.hasClass('active')) {
      const $firstButton = $buttons.first();
      if ($firstButton.length) {
        const firstTargetId = $firstButton.attr('data-tab');
        switchTab($container, firstTargetId, false);
      }
    }

    // Store tab state in session storage
    const tabsId = $container.attr('id') || 'saho-sidebar-tabs';
    const savedTab = sessionStorage.getItem(`saho-tabs-${tabsId}`);
    if (savedTab && $container.find(`[data-tab="${savedTab}"]`).length) {
      switchTab($container, savedTab, false);
    }

    // Update tab badges with content counts
    updateTabBadges($container);

    // Set up responsive behavior
    setupResponsiveTabs($container);
  }

  /**
   * Switch to a specific tab.
   *
   * @param {jQuery} $container - The tabs container
   * @param {string} targetId - The target tab ID
   * @param {boolean} animate - Whether to animate the switch
   */
  function switchTab($container, targetId, animate = true) {
    const $buttons = $container.find('.saho-tab-button');
    const $panes = $container.find('.saho-tab-pane');
    const $targetButton = $container.find(`[data-tab="${targetId}"]`);
    const $targetPane = $container.find(`#${targetId}`);

    if (!$targetButton.length || !$targetPane.length) {
      return;
    }

    // Update button states
    $buttons.removeClass('active').attr('aria-selected', 'false').attr('tabindex', '-1');
    $targetButton.addClass('active').attr('aria-selected', 'true').attr('tabindex', '0');

    // Update pane states
    $panes.removeClass('active').attr('aria-hidden', 'true');

    if (animate) {
      $targetPane.addClass('active').attr('aria-hidden', 'false');
    } else {
      $targetPane.addClass('active').attr('aria-hidden', 'false');
    }

    // Focus the active button for accessibility
    if (document.activeElement && $buttons.is(document.activeElement)) {
      $targetButton.focus();
    }

    // Save tab state
    const tabsId = $container.attr('id') || 'saho-sidebar-tabs';
    sessionStorage.setItem(`saho-tabs-${tabsId}`, targetId);

    // Scroll tab into view if needed
    scrollTabIntoView($targetButton);

    // Trigger custom event
    $container.trigger('saho:tabChanged', [targetId, $targetPane]);
  }

  /**
   * Handle keyboard navigation for tabs.
   *
   * @param {Event} e - The keyboard event
   * @param {jQuery} $buttons - All tab buttons
   * @param {jQuery} $container - The tabs container
   */
  function handleTabKeyboard(e, $buttons, $container) {
    const $current = $(e.target);
    const currentIndex = $buttons.index($current);
    let $target = null;

    switch (e.key) {
      case 'ArrowLeft':
      case 'ArrowUp':
        e.preventDefault();
        $target = currentIndex > 0 ? $buttons.eq(currentIndex - 1) : $buttons.last();
        break;

      case 'ArrowRight':
      case 'ArrowDown':
        e.preventDefault();
        $target =
          currentIndex < $buttons.length - 1 ? $buttons.eq(currentIndex + 1) : $buttons.first();
        break;

      case 'Home':
        e.preventDefault();
        $target = $buttons.first();
        break;

      case 'End':
        e.preventDefault();
        $target = $buttons.last();
        break;

      case 'Enter':
      case ' ': {
        e.preventDefault();
        const targetId = $current.attr('data-tab');
        switchTab($container, targetId);
        return;
      }
    }

    if ($target?.length) {
      const targetId = $target.attr('data-tab');
      switchTab($container, targetId);
    }
  }

  /**
   * Scroll tab button into view if it's outside the visible area.
   *
   * @param {jQuery} $button - The tab button to scroll into view
   */
  function scrollTabIntoView($button) {
    const $nav = $button.closest('.saho-tabs-nav');
    const navElement = $nav[0];
    const buttonElement = $button[0];

    if (!navElement || !buttonElement) {
      return;
    }

    const navRect = navElement.getBoundingClientRect();
    const buttonRect = buttonElement.getBoundingClientRect();

    // Check if button is outside the visible area
    if (buttonRect.left < navRect.left) {
      navElement.scrollLeft -= navRect.left - buttonRect.left + 20;
    } else if (buttonRect.right > navRect.right) {
      navElement.scrollLeft += buttonRect.right - navRect.right + 20;
    }
  }

  /**
   * Update tab badges with content counts.
   *
   * @param {jQuery} $container - The tabs container
   */
  function updateTabBadges($container) {
    const $buttons = $container.find('.saho-tab-button');

    $buttons.each(function () {
      const $button = $(this);
      const targetId = $button.attr('data-tab');
      const $pane = $container.find(`#${targetId}`);

      if (!$pane.length) {
        return;
      }

      // Count different types of content
      let count = 0;

      // Count views rows
      const $viewsRows = $pane.find('.views-row');
      if ($viewsRows.length) {
        count += $viewsRows.length;
      }

      // Count taxonomy terms
      const $taxonomyTerms = $pane.find('.saho-taxonomy-tag, .field__item');
      if ($taxonomyTerms.length && !$viewsRows.length) {
        count += $taxonomyTerms.length;
      }

      // Count reference items
      const $refItems = $pane.find('.saho-reference-list li');
      if ($refItems.length) {
        count += $refItems.length;
      }

      // Count content sections
      const $contentSections = $pane.find(
        '.saho-content-group, .saho-taxonomy-section, .saho-resource-section'
      );
      if ($contentSections.length && count === 0) {
        count += $contentSections.length;
      }

      // Update or add badge
      let $badge = $button.find('.saho-tab-badge');
      if (count > 0) {
        if (!$badge.length) {
          $badge = $('<span class="saho-tab-badge"></span>');
          $button.append($badge);
        }
        $badge.text(count);
      } else {
        $badge.remove();
      }
    });
  }

  /**
   * Set up responsive behavior for tabs.
   *
   * @param {jQuery} $container - The tabs container
   */
  function setupResponsiveTabs($container) {
    const $nav = $container.find('.saho-tabs-nav');

    // Add touch/swipe support for mobile
    if ('ontouchstart' in window) {
      setupTouchNavigation($nav);
    }

    // Handle window resize
    $(window).on(
      'resize.sahoTabs',
      debounce(() => {
        // Ensure active tab is visible after resize
        const $activeButton = $nav.find('.saho-tab-button.active');
        if ($activeButton.length) {
          setTimeout(() => scrollTabIntoView($activeButton), 100);
        }
      }, 250)
    );
  }

  /**
   * Set up touch navigation for tab bar.
   *
   * @param {jQuery} $nav - The tabs navigation element
   */
  function setupTouchNavigation($nav) {
    let startX = 0;
    let scrollLeft = 0;
    let isScrolling = false;

    $nav.on('touchstart', (e) => {
      startX = e.touches[0].pageX - $nav.offset().left;
      scrollLeft = $nav.scrollLeft();
      isScrolling = true;
    });

    $nav.on('touchmove', (e) => {
      if (!isScrolling) return;

      e.preventDefault();
      const x = e.touches[0].pageX - $nav.offset().left;
      const walk = (x - startX) * 2;
      $nav.scrollLeft(scrollLeft - walk);
    });

    $nav.on('touchend', () => {
      isScrolling = false;
    });
  }

  /**
   * Debounce function to limit the rate of function execution.
   *
   * @param {Function} func - The function to debounce
   * @param {number} wait - The number of milliseconds to delay
   * @return {Function} - The debounced function
   */
  function debounce(func, wait) {
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

  /**
   * Public API for external tab control.
   */
  Drupal.sahoTabs = {
    /**
     * Switch to a specific tab by ID.
     *
     * @param {string} containerId - The tabs container ID
     * @param {string} tabId - The tab ID to switch to
     */
    switchTo: (containerId, tabId) => {
      const $container = $(`#${containerId}`);
      if ($container.length) {
        switchTab($container, tabId);
      }
    },

    /**
     * Get the currently active tab ID.
     *
     * @param {string} containerId - The tabs container ID
     * @return {string|null} - The active tab ID or null
     */
    getActive: (containerId) => {
      const $container = $(`#${containerId}`);
      const $activeButton = $container.find('.saho-tab-button.active');
      return $activeButton.length ? $activeButton.attr('data-tab') : null;
    },
  };
})(jQuery, Drupal, once);
