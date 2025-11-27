/**
 * @file
 * Main menu functionality with simple positioning.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Position submenu based on available space
   */
  function positionSubmenu(link, submenu) {
    const rect = link.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const spaceRight = viewportWidth - rect.right;
    const spaceLeft = rect.left;

    // Remove existing positioning classes
    submenu.classList.remove('main-nav__submenu-nested--left', 'main-nav__submenu-nested--right');

    // Add class based on which side has more space
    if (spaceLeft > spaceRight) {
      submenu.classList.add('main-nav__submenu-nested--left');
    } else {
      submenu.classList.add('main-nav__submenu-nested--right');
    }
  }

  /**
   * Initialize main menu functionality.
   */
  function initMainMenu() {
    const mainMenus = once('main-menu', '.region-header .main-nav');

    mainMenus.forEach(function (menu) {
      const menuItems = menu.querySelectorAll('.main-nav__item--has-submenu');

      menuItems.forEach(function (menuItem) {
        const link = menuItem.querySelector('.main-nav__link');
        const submenu = menuItem.querySelector('.main-nav__submenu, .main-nav__submenu-nested');

        if (!link || !submenu) {
          return;
        }

        const nestedLevel = submenu.classList.contains('main-nav__submenu-nested');

        // Position based on available space
        if (nestedLevel) {
          positionSubmenu(link, submenu);
        }

        // Handle ARIA attributes for accessibility
        menuItem.addEventListener('mouseenter', function() {
          link.setAttribute('aria-expanded', 'true');
        });

        menuItem.addEventListener('mouseleave', function() {
          link.setAttribute('aria-expanded', 'false');
        });

        submenu.addEventListener('mouseleave', function() {
          link.setAttribute('aria-expanded', 'false');
        });
      });

      // Update positioning on window resize
      window.addEventListener('resize', function() {
        const nestedSubmenus = menu.querySelectorAll('.main-nav__submenu-nested');

        nestedSubmenus.forEach(function(submenu) {
          const menuItem = submenu.closest('.main-nav__item');
          const link = menuItem ? menuItem.querySelector('.main-nav__link') : null;

          if (link && submenu) {
            positionSubmenu(link, submenu);
          }
        });
      });
    });
  }

  // Initialize when DOM is ready
  Drupal.behaviors.mainMenu = {
    attach: function (context) {
      initMainMenu();
    }
  };

})(Drupal, once);
