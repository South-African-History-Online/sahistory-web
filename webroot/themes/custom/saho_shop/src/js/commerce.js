/**
 * @file
 * Commerce-specific JavaScript for SAHO Shop
 */

((Drupal, once) => {
  /**
   * Product Gallery
   */
  Drupal.behaviors.sahoShopProductGallery = {
    attach: (context, settings) => {
      once('product-gallery', '.commerce-product__gallery', context).forEach((gallery) => {
        const mainImage = gallery.querySelector('.main-image img');
        const thumbnails = gallery.querySelectorAll('.thumbnails img');

        if (!mainImage || !thumbnails.length) return;

        thumbnails.forEach((thumb) => {
          thumb.addEventListener('click', function () {
            // Update main image
            mainImage.src = this.dataset.fullsize || this.src;
            mainImage.alt = this.alt;

            // Update active state
            thumbnails.forEach((t) => t.classList.remove('active'));
            this.classList.add('active');
          });
        });
      });
    },
  };

  /**
   * Add to Cart Animation
   */
  Drupal.behaviors.sahoShopAddToCart = {
    attach: (context, settings) => {
      once('add-to-cart', '.commerce-order-item-add-to-cart-form', context).forEach((form) => {
        form.addEventListener('submit', () => {
          const button = form.querySelector('.button--primary');
          if (button) {
            button.classList.add('is-loading');
            button.disabled = true;

            // Re-enable after 2 seconds (fallback)
            setTimeout(() => {
              button.classList.remove('is-loading');
              button.disabled = false;
            }, 2000);
          }
        });
      });
    },
  };

  /**
   * Quantity Increment/Decrement
   */
  Drupal.behaviors.sahoShopQuantity = {
    attach: (context, settings) => {
      once('quantity-controls', '.quantity-input', context).forEach((input) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'quantity-controls';

        const decrementBtn = document.createElement('button');
        decrementBtn.type = 'button';
        decrementBtn.className = 'quantity-btn quantity-btn--minus';
        decrementBtn.textContent = '-';

        const incrementBtn = document.createElement('button');
        incrementBtn.type = 'button';
        incrementBtn.className = 'quantity-btn quantity-btn--plus';
        incrementBtn.textContent = '+';

        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(decrementBtn);
        wrapper.appendChild(input);
        wrapper.appendChild(incrementBtn);

        const min = Number.parseInt(input.min) || 1;
        const max = Number.parseInt(input.max) || 999;

        decrementBtn.addEventListener('click', () => {
          const currentValue = Number.parseInt(input.value) || min;
          if (currentValue > min) {
            input.value = currentValue - 1;
            input.dispatchEvent(new Event('change'));
          }
        });

        incrementBtn.addEventListener('click', () => {
          const currentValue = Number.parseInt(input.value) || min;
          if (currentValue < max) {
            input.value = currentValue + 1;
            input.dispatchEvent(new Event('change'));
          }
        });
      });
    },
  };

  /**
   * Cart Update Handler
   */
  Drupal.behaviors.sahoShopCartUpdate = {
    attach: (context, settings) => {
      // Update cart count in header when cart changes
      if (settings.commerce_cart && typeof settings.commerce_cart.count !== 'undefined') {
        const cartCount = document.querySelector('.commerce-cart-block__count');
        const cartIcon = document.querySelector('.commerce-cart-block__icon');

        if (cartIcon) {
          // Remove existing count badge if present
          const existingCount = cartIcon.querySelector('.commerce-cart-block__count');
          if (existingCount) {
            existingCount.remove();
          }

          // Add new count badge if count > 0
          if (settings.commerce_cart.count > 0) {
            const countBadge = document.createElement('span');
            countBadge.className = 'commerce-cart-block__count';
            countBadge.textContent = settings.commerce_cart.count;
            cartIcon.appendChild(countBadge);
          }
        } else if (cartCount) {
          // Fallback: just update the text content
          if (settings.commerce_cart.count > 0) {
            cartCount.textContent = settings.commerce_cart.count;
            cartCount.style.display = 'flex';
          } else {
            cartCount.style.display = 'none';
          }
        }
      }
    },
  };

  /**
   * Product Wishlist Toggle
   */
  Drupal.behaviors.sahoShopWishlist = {
    attach: (context, settings) => {
      once('wishlist-toggle', '.product-card__wishlist', context).forEach((button) => {
        button.addEventListener('click', function (e) {
          e.preventDefault();
          this.classList.toggle('is-active');

          // You can add AJAX call here to save wishlist state
          const icon = this.querySelector('i');
          if (icon) {
            icon.classList.toggle('far'); // Regular
            icon.classList.toggle('fas'); // Solid
          }
        });
      });
    },
  };
})(Drupal, once);
