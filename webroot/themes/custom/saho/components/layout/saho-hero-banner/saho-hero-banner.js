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

        // Graphic mode link protection
        const link = banner.querySelector('.saho-hero-banner__link');
        if (link) {
          const originalUrl = link.getAttribute('data-original-url') || link.getAttribute('href');

          // Prevent link href from being modified
          const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
              if (mutation.type === 'attributes' && mutation.attributeName === 'href') {
                const currentHref = link.getAttribute('href');
                if (currentHref !== originalUrl) {
                  console.warn('Hero banner link was modified! Restoring original URL:', originalUrl);
                  link.setAttribute('href', originalUrl);
                }
              }
            });
          });

          observer.observe(link, { attributes: true });

          // Also prevent any click event modifications
          link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            if (href !== originalUrl) {
              console.warn('Click detected with wrong URL. Fixing and continuing...');
              link.setAttribute('href', originalUrl);
            }
          }, true); // Use capture phase
        }
      });
    },
  };
})(Drupal, once);
