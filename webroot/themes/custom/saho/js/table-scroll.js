/**
 * @file
 * Table scroll indicators for narrow content areas
 *
 * Adds visual indicators when tables can be scrolled horizontally
 * and manages scroll shadow states.
 */

((Drupal, once) => {
  Drupal.behaviors.sahoTableScroll = {
    attach: (context, _settings) => {
      // Initialize scroll wrappers with visual indicators
      once('table-scroll', '.table-scroll-wrapper', context).forEach((wrapper) => {
        const updateScrollState = () => {
          const scrollLeft = wrapper.scrollLeft;
          const scrollWidth = wrapper.scrollWidth;
          const clientWidth = wrapper.clientWidth;
          const scrollRight = scrollWidth - clientWidth - scrollLeft;

          // Add/remove classes based on scroll position
          if (scrollLeft > 10) {
            wrapper.classList.add('scrolled-left');
          } else {
            wrapper.classList.remove('scrolled-left');
          }

          if (scrollRight > 10) {
            wrapper.classList.add('scrolled-right');
          } else {
            wrapper.classList.remove('scrolled-right');
          }

          // If content doesn't scroll, hide indicators
          if (scrollWidth <= clientWidth) {
            wrapper.classList.add('no-scroll');
          } else {
            wrapper.classList.remove('no-scroll');
          }
        };

        // Update on scroll
        wrapper.addEventListener('scroll', updateScrollState);

        // Update on window resize
        window.addEventListener('resize', updateScrollState);

        // Initial update
        updateScrollState();
      });

      // Auto-wrap tables without explicit wrapper in narrow content areas
      once(
        'table-auto-scroll',
        '.saho-article-body table, .saho-main-content table',
        context
      ).forEach((table) => {
        // Skip if already in a wrapper or has special class
        if (
          table.closest('.table-scroll-wrapper') ||
          table.closest('.table-full-width') ||
          table.closest('.table-wide') ||
          table.classList.contains('no-auto-scroll')
        ) {
          return;
        }

        const checkTableWidth = () => {
          const tableWidth = table.scrollWidth;
          const containerWidth = table.parentElement.clientWidth;

          // If table is wider than container, it needs scrolling
          if (tableWidth > containerWidth + 10) {
            table.classList.add('needs-scroll');
          } else {
            table.classList.remove('needs-scroll');
          }
        };

        // Check on resize
        window.addEventListener('resize', checkTableWidth);

        // Initial check
        checkTableWidth();
      });

      // Handle keyboard navigation for scrollable tables
      once('table-keyboard', '.table-scroll-wrapper', context).forEach((wrapper) => {
        wrapper.setAttribute('tabindex', '0');
        wrapper.setAttribute('role', 'region');
        wrapper.setAttribute('aria-label', 'Scrollable table');

        wrapper.addEventListener('keydown', (e) => {
          const scrollAmount = 50;

          switch (e.key) {
            case 'ArrowLeft':
              e.preventDefault();
              wrapper.scrollLeft -= scrollAmount;
              break;
            case 'ArrowRight':
              e.preventDefault();
              wrapper.scrollLeft += scrollAmount;
              break;
            case 'Home':
              if (e.ctrlKey) {
                e.preventDefault();
                wrapper.scrollLeft = 0;
              }
              break;
            case 'End':
              if (e.ctrlKey) {
                e.preventDefault();
                wrapper.scrollLeft = wrapper.scrollWidth;
              }
              break;
          }
        });
      });

      // Add mobile touch swipe hint
      once('table-touch-hint', '.table-scroll-wrapper', context).forEach((wrapper) => {
        // Only show hint on touch devices
        if (!('ontouchstart' in window)) {
          return;
        }

        // Create hint element
        const hint = document.createElement('div');
        hint.className = 'table-scroll-hint';
        hint.innerHTML = '← Swipe to see more →';
        hint.style.cssText = `
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: rgba(139, 0, 0, 0.9);
          color: white;
          padding: 0.5rem 1rem;
          border-radius: 4px;
          font-size: 0.875rem;
          pointer-events: none;
          z-index: 10;
          opacity: 0;
          transition: opacity 0.3s ease;
        `;

        wrapper.style.position = 'relative';
        wrapper.appendChild(hint);

        // Show hint briefly when wrapper is first visible
        const observer = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting && wrapper.scrollWidth > wrapper.clientWidth) {
                hint.style.opacity = '1';
                setTimeout(() => {
                  hint.style.opacity = '0';
                  setTimeout(() => {
                    hint.remove();
                  }, 300);
                }, 2000);
                observer.disconnect();
              }
            });
          },
          { threshold: 0.5 }
        );

        observer.observe(wrapper);
      });
    },
  };
})(Drupal, once);
