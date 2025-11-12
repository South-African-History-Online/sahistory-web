/**
 * @file
 * SAHO Shop Global JavaScript
 */

((Drupal, once) => {
  /**
   * Mobile Menu Toggle
   */
  Drupal.behaviors.sahoShopMobileMenu = {
    attach: (context, settings) => {
      once('mobile-menu-toggle', '.mobile-menu-toggle', context).forEach((toggle) => {
        toggle.addEventListener('click', () => {
          const nav = document.querySelector('.site-header__nav');
          if (nav) {
            nav.classList.toggle('is-open');
            toggle.classList.toggle('is-active');
          }
        });
      });
    },
  };

  /**
   * Sticky Header
   */
  Drupal.behaviors.sahoShopStickyHeader = {
    attach: (context, settings) => {
      once('sticky-header', '.site-header', context).forEach((header) => {
        let lastScroll = 0;

        window.addEventListener('scroll', () => {
          const currentScroll = window.pageYOffset;

          if (currentScroll <= 0) {
            header.classList.remove('scroll-up');
            return;
          }

          if (currentScroll > lastScroll && !header.classList.contains('scroll-down')) {
            header.classList.remove('scroll-up');
            header.classList.add('scroll-down');
          } else if (currentScroll < lastScroll && header.classList.contains('scroll-down')) {
            header.classList.remove('scroll-down');
            header.classList.add('scroll-up');
          }

          lastScroll = currentScroll;
        });
      });
    },
  };

  /**
   * Smooth Scroll for Anchor Links
   */
  Drupal.behaviors.sahoShopSmoothScroll = {
    attach: (context, settings) => {
      once('smooth-scroll', 'a[href^="#"]', context).forEach((link) => {
        link.addEventListener('click', function (e) {
          const href = this.getAttribute('href');
          if (href === '#') return;

          const target = document.querySelector(href);
          if (target) {
            e.preventDefault();
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'start',
            });
          }
        });
      });
    },
  };
})(Drupal, once);
