/**
 * SAHO Hero Banner Component - Basic Functionality
 *
 * Handles image loaded state for smooth rendering.
 *
 * @file
 * SAHO Hero Banner JavaScript behaviors
 */

((Drupal, once) => {
  /**
   * Hero Banner Behavior
   *
   * Attaches loading state to hero banner components.
   */
  Drupal.behaviors.sahoHeroBanner = {
    attach: (context, _settings) => {
      once('saho-hero-banner', '.saho-hero-banner', context).forEach((banner) => {
        // Add loaded class after image loads for smooth rendering
        const img = banner.querySelector('.saho-hero-banner__image');

        if (img) {
          // If image is already loaded (cached)
          if (img.complete) {
            banner.classList.add('is-loaded');
          } else {
            // Wait for image to load
            img.addEventListener('load', () => {
              banner.classList.add('is-loaded');
            });

            // Handle image load errors gracefully
            img.addEventListener('error', () => {
              banner.classList.add('is-loaded');
            });
          }
        }

        // Graphic mode: Ensure link is clickable without JavaScript interference
        const link = banner.querySelector('.saho-hero-banner__link');
        if (link) {
          // Just ensure the link element has proper attributes - don't add event listeners
          link.style.pointerEvents = 'auto';
        }
      });
    },
  };
})(Drupal, once);
