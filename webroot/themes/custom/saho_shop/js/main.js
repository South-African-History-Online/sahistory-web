/**
 * @file
 * saho_shop theme main JS file.
 *
 */

(function (Drupal, drupalSettings) {
  // Initiate all Toasts on page.
  Drupal.behaviors.saho_shopToast = {
    attach(context, settings) {
      once('initToast', '.toast', context).forEach(el => {
        const toastList = new bootstrap.Toast(el);
        toastList.show();
      });
    },
  };

  // Collapse buttons containing links.
  Drupal.behaviors.collapseButtonLinks = {
    attach(context, settings) {
      once('collapseButtonLinks', '[data-bs-toggle="collapse"]', context).forEach(el => {
        // Prevent accordion collapse when clicking on links
        el.addEventListener('click', function (e) {
          if (e.target.href) {
            const targetUrl = e.target.href;
            window.location.href = targetUrl;
          }
        });
      });
    },
  };

  // Collapse and accordion if a field is required.
  Drupal.behaviors.focusRequired = {
    attach(context, settings) {
      const inputs = document.querySelectorAll('form .accordion input');
      [].forEach.call(inputs, function (input) {
        input.addEventListener('invalid', function (e) {
          const accordion = input.closest('.collapse');
          const collapseAccordion = bootstrap.Collapse.getInstance(accordion);
          if (collapseAccordion) {
            collapseAccordion.show();
          }
        });
      });
    },
  };

  // Collapse certain block facets on mobile
  Drupal.behaviors.collapseBlockFacetsMob = {
    attach(context) {
      const breakPoint = drupalSettings.responsive.breakpoints['saho_shop.sm-max'];

      // Ensure breakpoint is valid
      if (!breakPoint) {
        return;
      }

      const mq = window.matchMedia(breakPoint);
      if (mq.matches) {
        once('collapseBlockFacetsMob', '.block-facets .collapse', context).forEach(element => {
          // Try to get or create a Bootstrap collapse instance
          let collapseInstance = bootstrap.Collapse.getInstance(element);
          if (!collapseInstance) {
            collapseInstance = new bootstrap.Collapse(element, { toggle: false });
          }
          collapseInstance.hide();
        });
      }
    },
  };

  // Checkout order summary responsive collapse
  Drupal.behaviors.checkoutOrderSummaryResponsive = {
    attach(context) {
      const breakPoint = drupalSettings.responsive.breakpoints['saho_shop.sm-max'];

      // Ensure breakpoint is valid
      if (!breakPoint) {
        return;
      }

      // Handle both regular and focused checkout order summaries
      const selectors = [
        '.checkout-form__sidebar-inner',
        '.focused-checkout-form__sidebar-inner'
      ];

      selectors.forEach(selector => {
        once('checkoutOrderSummaryResponsive', selector, context).forEach(
          orderSummary => {
            // Find title and content elements (works for both regular and focused checkout)
            const title = orderSummary.querySelector('.checkout-form__sidebar-title, .focused-checkout-form__sidebar-title');
            const content = orderSummary.querySelector('.checkout-form__sidebar-content, .focused-checkout-form__sidebar-content');

            if (!title || !content) {
              return;
            }

            // Function to handle responsive behavior
            const handleResponsiveBehavior = () => {
              const mq = window.matchMedia(breakPoint);
              const isMobile = mq.matches;
              const collapseInstance = bootstrap.Collapse.getInstance(content);

              if (collapseInstance && isMobile) {
                // On mobile: ensure it starts collapsed
                collapseInstance.hide();
              }
            };

            // Set initial state
            handleResponsiveBehavior();

            // Listen for window resize
            window.addEventListener('resize', handleResponsiveBehavior);
          }
        );
      });
    },
  };
})(Drupal, drupalSettings);
