/**
 * @file
 * Quantity input enhancement with plus/minus buttons.
 */

(function (Drupal, drupalSettings) {
  'use strict';

  /**
   * Quantity input behavior.
   */
  Drupal.behaviors.quantityInput = {
    attach: function (context, settings) {
      once('quantity-input', '.quantity-input-wrapper', context).forEach(function (wrapper) {
        const input = wrapper.querySelector('.quantity-input');
        const minusBtn = wrapper.querySelector('.quantity-btn--minus');
        const plusBtn = wrapper.querySelector('.quantity-btn--plus');

        if (!input || (!minusBtn && !plusBtn)) {
          return;
        }

        // Get settings from theme settings or use defaults
        const step = parseInt(input.getAttribute('step')) || 1;
        const min = parseInt(input.getAttribute('min')) || 0;
        const max = parseInt(input.getAttribute('max')) || 999999999;

        /**
         * Update button states based on current value.
         */
        function updateButtonStates() {
          const currentValue = parseInt(input.value) || 0;

          if (minusBtn) {
            minusBtn.disabled = currentValue <= min;
          }

          if (plusBtn) {
            plusBtn.disabled = currentValue >= max;
          }
        }

        /**
         * Increment the quantity value.
         */
        function increment() {
          const currentValue = parseInt(input.value) || 0;
          const newValue = Math.min(currentValue + step, max);

          if (newValue !== currentValue) {
            input.value = newValue;
            updateButtonStates();
            triggerChangeEvent();
          }
        }

        /**
         * Decrement the quantity value.
         */
        function decrement() {
          const currentValue = parseInt(input.value) || 0;
          const newValue = Math.max(currentValue - step, min);

          if (newValue !== currentValue) {
            input.value = newValue;
            updateButtonStates();
            triggerChangeEvent();
          }
        }

        /**
         * Trigger change event for form validation and other behaviors.
         */
        function triggerChangeEvent() {
          const event = new Event('change', { bubbles: true });
          input.dispatchEvent(event);

          // Also trigger input event for real-time updates
          const inputEvent = new Event('input', { bubbles: true });
          input.dispatchEvent(inputEvent);
        }

        /**
         * Validate and correct input value.
         */
        function validateInput() {
          let value = parseInt(input.value);

          if (isNaN(value)) {
            value = min;
          } else if (value < min) {
            value = min;
          } else if (value > max) {
            value = max;
          }

          if (value !== parseInt(input.value)) {
            input.value = value;
          }

          updateButtonStates();
        }

        // Add event listeners
        if (plusBtn) {
          plusBtn.addEventListener('click', function(e) {
            e.preventDefault();
            increment();
          });
        }

        if (minusBtn) {
          minusBtn.addEventListener('click', function(e) {
            e.preventDefault();
            decrement();
          });
        }

        // Handle direct input changes
        input.addEventListener('input', function() {
          updateButtonStates();
        });

        input.addEventListener('blur', function() {
          validateInput();
          triggerChangeEvent();
        });

        // Handle keyboard events
        input.addEventListener('keydown', function(e) {
          if (e.key === 'ArrowUp') {
            e.preventDefault();
            increment();
          } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            decrement();
          }
        });

        // Handle mouse wheel events
        input.addEventListener('wheel', function(e) {
          e.preventDefault();

          if (e.deltaY < 0) {
            increment();
          } else {
            decrement();
          }
        });

        // Initialize button states
        updateButtonStates();

        // Handle form submission to ensure proper values
        const form = input.closest('form');
        if (form) {
          form.addEventListener('submit', function() {
            validateInput();
          });
        }
      });
    }
  };

})(Drupal, drupalSettings);
