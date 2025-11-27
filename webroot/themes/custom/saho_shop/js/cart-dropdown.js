/**
 * Cart Dropdown Smart Positioning and Hover
 * Automatically adjusts dropdown position and handles hover display
 */
(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.cartDropdownPositioning = {
    attach: function (context, settings) {
      once('cart-dropdown-positioning', '.cart-block__dropdown', context).forEach(function(dropdown) {
        const trigger = context.querySelector('[data-bs-target="#' + dropdown.id + '"]');
        if (!trigger) return;

        // Check if hover is enabled
        const hoverEnabled = dropdown.classList.contains('cart-block__dropdown--hover');
        let hoverTimeout;

        function adjustPosition() {
          // Get width from CSS custom property (assumes rem unit)
          const computedStyle = window.getComputedStyle(dropdown);
          const dropdownWidthRem = parseFloat(computedStyle.getPropertyValue('--cart-dropdown-width')) || 20;
          const rootFontSize = parseFloat(getComputedStyle(document.documentElement).fontSize);
          const dropdownWidth = dropdownWidthRem * rootFontSize;

          const cartBlock = dropdown.closest('.block-commerce-cart');
          const cartBlockRect = cartBlock.getBoundingClientRect();
          const viewportWidth = window.innerWidth;

          // Reset any previous adjustments
          dropdown.style.left = '';
          dropdown.style.right = '';

          // Check if dropdown is center-aligned
          const isCentered = dropdown.classList.contains('cart-block__dropdown--center');

          if (isCentered) {
            // For centered dropdown, calculate both left and right edges
            const cartBlockCenter = cartBlockRect.left + (cartBlockRect.width / 2);
            const dropdownLeft = cartBlockCenter - (dropdownWidth / 2);
            const dropdownRight = cartBlockCenter + (dropdownWidth / 2);

            // Check for overflow on left
            if (dropdownLeft < 0) {
              const overflowRem = Math.abs(dropdownLeft) / rootFontSize;
              dropdown.style.left = overflowRem + 'rem';
            }
            // Check for overflow on right
            else if (dropdownRight > viewportWidth) {
              const overflow = dropdownRight - viewportWidth;
              const overflowRem = overflow / rootFontSize;
              dropdown.style.left = -overflowRem + 'rem';
            }
          } else {
            // For left-aligned dropdown, calculate space to the right edge
            const spaceToRight = viewportWidth - cartBlockRect.left;

            // If dropdown would overflow, adjust left position
            if (dropdownWidth > spaceToRight) {
              const overflow = dropdownWidth - spaceToRight;
              const overflowRem = overflow / rootFontSize;
              dropdown.style.left = -overflowRem + 'rem';
            }
          }
        }

        // Simple hover functionality
        if (hoverEnabled) {
          const cart = dropdown.closest('.cart-block');

          function showDropdown() {
            clearTimeout(hoverTimeout);
            if (!dropdown.classList.contains('show')) {
              const bsCollapse = new bootstrap.Collapse(dropdown, {
                toggle: true
              });
            }
          }

          function hideDropdown() {
            hoverTimeout = setTimeout(function() {
              if (dropdown.classList.contains('show')) {
                // Try to get existing collapse instance or create new one
                let bsCollapse = bootstrap.Collapse.getInstance(dropdown);
                if (!bsCollapse) {
                  bsCollapse = new bootstrap.Collapse(dropdown);
                }
                bsCollapse.hide();
              }
            }, 150);
          }

          cart.addEventListener('mouseenter', showDropdown);
          cart.addEventListener('mouseleave', hideDropdown);
        }

        // Adjust position on load and resize (skip only for left-aligned)
        if (!dropdown.classList.contains('cart-block__dropdown--left')) {
          // Calculate position on load
          adjustPosition();

          // Adjust on window resize
          window.addEventListener('resize', adjustPosition);
        }
      });
    }
  };

})(Drupal, drupalSettings);
