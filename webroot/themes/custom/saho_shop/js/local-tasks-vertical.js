(function (Drupal) {
  'use strict';

  Drupal.behaviors.localTasksVertical = {
    attach: function (context) {
      setTimeout(function() {
        const localTasks = once('local-tasks-vertical', '.local-tasks--vertical', context);

        localTasks.forEach(function(container) {
          const navTabs = container.querySelector('.nav-tabs');
          const activeItem = container.querySelector('.nav-item .nav-link.active');

          if (!navTabs || !activeItem) return;

          // Toggle on active item click (mobile only)
          activeItem.addEventListener('click', function(e) {
            if (window.innerWidth <= 767.98) {
              e.preventDefault();
              navTabs.classList.toggle('expanded');
            }
          });

          // Close when clicking outside
          document.addEventListener('click', function(event) {
            if (!container.contains(event.target)) {
              navTabs.classList.remove('expanded');
            }
          });
        });
      }, 100);
    }
  };

})(Drupal);
