/**
 * @file
 * SAHO Sidebar Accordion JavaScript
 *
 * Provides accordion functionality for the Related Content sidebar sections.
 */

(($, Drupal, once) => {
  /**
   * Initialize SAHO sidebar accordion functionality.
   */
  Drupal.behaviors.sahoSidebarAccordion = {
    attach: (context, _settings) => {
      once('saho-sidebar-accordion', '.saho-accordion', context).forEach((element) => {
        const $accordion = $(element);
        initializeAccordion($accordion);
      });
    },
  };

  /**
   * Initialize accordion functionality.
   *
   * @param {jQuery} $accordion - The accordion container element
   */
  function initializeAccordion($accordion) {
    const $buttons = $accordion.find('.saho-accordion-button');
    const _$collapses = $accordion.find('.saho-accordion-collapse');

    // Set up click handlers
    $buttons.on('click', function (e) {
      e.preventDefault();
      const $button = $(this);
      const $target = $($button.attr('data-target') || $button.attr('aria-controls'));

      if ($target.length) {
        toggleAccordionItem($button, $target, $accordion);
      }
    });

    // Set up keyboard navigation
    $buttons.on('keydown', (e) => {
      handleAccordionKeyboard(e, $buttons);
    });

    // Initialize first available accordion as open by default
    const $firstButton = $buttons.first();
    if ($firstButton.length) {
      const $firstTarget = $(
        $firstButton.attr('data-target') || $firstButton.attr('aria-controls')
      );
      if ($firstTarget.length) {
        openAccordionItem($firstButton, $firstTarget);
      }
    }
  }

  /**
   * Toggle an accordion item.
   *
   * @param {jQuery} $button - The accordion button
   * @param {jQuery} $target - The target collapse element
   * @param {jQuery} $accordion - The accordion container
   */
  function toggleAccordionItem($button, $target, $accordion) {
    const isExpanded = $button.attr('aria-expanded') === 'true';

    if (isExpanded) {
      closeAccordionItem($button, $target);
    } else {
      // Close other items in this accordion
      const $otherButtons = $accordion.find('.saho-accordion-button').not($button);
      $otherButtons.each(function () {
        const $otherButton = $(this);
        const $otherTarget = $(
          $otherButton.attr('data-target') || $otherButton.attr('aria-controls')
        );
        if ($otherTarget.length && $otherButton.attr('aria-expanded') === 'true') {
          closeAccordionItem($otherButton, $otherTarget);
        }
      });

      // Open this item
      openAccordionItem($button, $target);
    }
  }

  /**
   * Open an accordion item.
   *
   * @param {jQuery} $button - The accordion button
   * @param {jQuery} $target - The target collapse element
   */
  function openAccordionItem($button, $target) {
    $button.removeClass('collapsed');
    $button.attr('aria-expanded', 'true');
    $target.addClass('show');
    $target.attr('aria-hidden', 'false');

    // Let CSS handle the smooth animation
    $target.css('height', 'auto');
  }

  /**
   * Close an accordion item.
   *
   * @param {jQuery} $button - The accordion button
   * @param {jQuery} $target - The target collapse element
   */
  function closeAccordionItem($button, $target) {
    $button.addClass('collapsed');
    $button.attr('aria-expanded', 'false');
    $target.removeClass('show');
    $target.attr('aria-hidden', 'true');

    // Let CSS handle the smooth animation
    $target.css('height', '0');
  }

  /**
   * Handle keyboard navigation for accordion.
   *
   * @param {Event} e - The keyboard event
   * @param {jQuery} $buttons - All accordion buttons
   */
  function handleAccordionKeyboard(e, $buttons) {
    const $current = $(e.target);
    const currentIndex = $buttons.index($current);
    let $target = null;

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        $target =
          currentIndex < $buttons.length - 1 ? $buttons.eq(currentIndex + 1) : $buttons.first();
        break;

      case 'ArrowUp':
        e.preventDefault();
        $target = currentIndex > 0 ? $buttons.eq(currentIndex - 1) : $buttons.last();
        break;

      case 'Home':
        e.preventDefault();
        $target = $buttons.first();
        break;

      case 'End':
        e.preventDefault();
        $target = $buttons.last();
        break;
    }

    if ($target?.length) {
      $target.focus();
    }
  }

  /**
   * Convert existing Bootstrap accordion to SAHO accordion.
   */
  Drupal.behaviors.sahoConvertBootstrapAccordion = {
    attach: (context, _settings) => {
      once('saho-convert-accordion', '.accordion', context).forEach((element) => {
        const $accordion = $(element);

        // Add SAHO accordion class
        $accordion.addClass('saho-accordion');

        // Convert accordion items
        $accordion.find('.accordion-item').each(function () {
          const $item = $(this);
          const $button = $item.find('.accordion-button');
          const $collapse = $item.find('.accordion-collapse');

          // Add SAHO classes
          $item.addClass('saho-accordion-item');
          $button.addClass('saho-accordion-button');
          $collapse.addClass('saho-accordion-collapse');

          // Set up proper ARIA attributes
          const collapseId = $collapse.attr('id');
          if (collapseId) {
            $button.attr('aria-controls', collapseId);
            $button.attr('data-target', `#${collapseId}`);
          }

          // Remove Bootstrap data attributes
          $button.removeAttr('data-bs-toggle data-bs-target');
          $collapse.removeAttr('data-bs-parent');
        });

        // Initialize the converted accordion
        initializeAccordion($accordion);
      });
    },
  };
})(jQuery, Drupal, once);
