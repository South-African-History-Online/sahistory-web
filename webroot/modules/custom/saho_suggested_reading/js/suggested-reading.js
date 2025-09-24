(function (Drupal, once) {
  'use strict';

  // Mobile detection utility
  const isMobile = () => window.innerWidth <= 768;
  const isTouch = () => 'ontouchstart' in window || navigator.maxTouchPoints > 0;

  Drupal.behaviors.sahoSuggestedReading = {
    attach: function (context) {
      // Initialize suggested reading enhancements
      once('saho-suggested-reading', '.saho-suggested-reading-enhanced', context).forEach(function (element) {
        // Mobile-first optimizations
        initializeMobileOptimizations(element);

        // Enhanced lazy loading for mobile performance
        initializeLazyLoading(element);

        // Touch-optimized interactions
        initializeTouchInteractions(element);

        // Swipe gestures for mobile
        if (isMobile() && isTouch()) {
          initializeSwipeGestures(element);
        }

        // Track engagement with suggested reading
        initializeAnalytics(element);
      });

      // Handle dynamic accordion for suggested reading sections
      once('saho-suggested-accordion', '.saho-suggested-accordion', context).forEach(function (accordion) {
        initializeAccordion(accordion);
      });
    }
  };

  // Mobile-first initialization
  function initializeMobileOptimizations(element) {
    const cardsGrids = element.querySelectorAll('.saho-cards-grid');

    cardsGrids.forEach(function (grid) {
      const cards = grid.querySelectorAll('.saho-card');

      // Add mobile-specific classes
      if (isMobile()) {
        grid.classList.add('mobile-optimized');

        // For many cards on mobile, enable horizontal scroll
        if (cards.length > 4) {
          grid.classList.add('horizontal-scroll');
          enableHorizontalScroll(grid);
        }
      }

      // Intersection observer for viewport-based optimizations
      if ('IntersectionObserver' in window) {
        const gridObserver = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('in-viewport');
            }
          });
        }, { rootMargin: '50px' });

        gridObserver.observe(grid);
      }
    });
  }

  // Enhanced lazy loading with mobile optimizations
  function initializeLazyLoading(element) {
    const images = element.querySelectorAll('.saho-card-image img');

    if ('IntersectionObserver' in window) {
      const imageObserver = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            const img = entry.target;

            // Mobile-optimized loading
            if (isMobile()) {
              // Use smaller image sizes on mobile if available
              const mobileSrc = img.dataset.mobileSrc || img.src || img.dataset.src;
              img.src = mobileSrc;
            } else {
              img.src = img.dataset.src || img.src;
            }

            img.removeAttribute('data-src');
            img.removeAttribute('data-mobile-src');
            img.classList.add('loaded');
            imageObserver.unobserve(img);
          }
        });
      }, {
        rootMargin: isMobile() ? '100px' : '50px' // Larger margin on mobile
      });

      images.forEach(function (img) {
        // Add loading placeholder
        img.classList.add('lazy-loading');
        imageObserver.observe(img);
      });
    } else {
      // Fallback for older browsers
      images.forEach(function (img) {
        img.src = img.dataset.src || img.src;
        img.classList.add('loaded');
      });
    }
  }

  // Touch-optimized interactions
  function initializeTouchInteractions(element) {
    const cards = element.querySelectorAll('.saho-card');

    cards.forEach(function (card) {
      const link = card.querySelector('.saho-card-link');

      if (link && isTouch()) {
        // Enhanced touch feedback
        link.addEventListener('touchstart', function (e) {
          this.classList.add('touch-active');
        }, { passive: TRUE });

        link.addEventListener('touchend', function (e) {
          const self = this;
          setTimeout(function () {
            self.classList.remove('touch-active');
          }, 150);
        }, { passive: TRUE });

        link.addEventListener('touchcancel', function (e) {
          this.classList.remove('touch-active');
        }, { passive: TRUE });

        // Prevent accidental selections during scroll
        let startY = 0;
        link.addEventListener('touchstart', function (e) {
          startY = e.touches[0].clientY;
        }, { passive: TRUE });

        link.addEventListener('touchmove', function (e) {
          const currentY = e.touches[0].clientY;
          const diffY = Math.abs(currentY - startY);

          // If significant vertical movement, likely scrolling
          if (diffY > 10) {
            this.classList.remove('touch-active');
          }
        }, { passive: TRUE });
      }
    });
  }

  // Swipe gestures for mobile card navigation
  function initializeSwipeGestures(element) {
    const cardsGrids = element.querySelectorAll('.saho-cards-grid.horizontal-scroll');

    cardsGrids.forEach(function (grid) {
      let startX = 0;
      let scrollLeft = 0;
      let isDown = FALSE;

      grid.addEventListener('touchstart', function (e) {
        isDown = TRUE;
        startX = e.touches[0].pageX - grid.offsetLeft;
        scrollLeft = grid.scrollLeft;
        grid.classList.add('scrolling');
      }, { passive: TRUE });

      grid.addEventListener('touchmove', function (e) {
        if (!isDown) { return;
        }
        e.preventDefault();
        const x = e.touches[0].pageX - grid.offsetLeft;
        const walk = (x - startX) * 2;
        grid.scrollLeft = scrollLeft - walk;
      });

      grid.addEventListener('touchend', function (e) {
        isDown = FALSE;
        grid.classList.remove('scrolling');

        // Snap to card positions
        snapToNearestCard(grid);
      }, { passive: TRUE });
    });
  }

  // Enable horizontal scroll for mobile
  function enableHorizontalScroll(grid) {
    grid.style.gridTemplateColumns = 'repeat(' + grid.children.length + ', minmax(280px, 1fr))';
    grid.style.overflowX = 'auto';
    grid.style.scrollSnapType = 'x mandatory';

    // Add scroll snap to each card
    Array.from(grid.children).forEach(function (card) {
      card.style.scrollSnapAlign = 'start';
    });
  }

  // Snap to nearest card after swipe
  function snapToNearestCard(grid) {
    const cardWidth = grid.children[0].offsetWidth + parseInt(getComputedStyle(grid).gap);
    const scrollPos = grid.scrollLeft;
    const targetIndex = Math.round(scrollPos / cardWidth);
    const targetScroll = targetIndex * cardWidth;

    grid.scrollTo({
      left: targetScroll,
      behavior: 'smooth'
    });
  }

  // Analytics tracking
  function initializeAnalytics(element) {
    const links = element.querySelectorAll('.saho-card-link');

    links.forEach(function (link) {
      link.addEventListener('click', function (e) {
        const title = link.querySelector('.saho-card-title') ? .textContent || 'Unknown';
        const section = link.closest('.saho-related-content-section') ? .querySelector('h4') ? .textContent || 'Unknown Section';

        // Track engagement
        if (typeof gtag !== 'undefined') {
          gtag('event', 'suggested_reading_click', {
            'event_category': 'engagement',
            'event_label': title,
            'custom_map': {
              'dimension1': section,
              'dimension2': isMobile() ? 'mobile' : 'desktop'
            }
          });
        }

        // Track mobile-specific interactions
        if (isMobile()) {
          console.log('Mobile suggested reading click:', title, 'in section:', section);
        }
      });
    });
  }

  // Accordion functionality
  function initializeAccordion(accordion) {
    const buttons = accordion.querySelectorAll('.saho-accordion-button');

    buttons.forEach(function (button) {
      // Enhanced touch target for mobile
      if (isMobile()) {
        button.style.minHeight = '48px';
        button.style.padding = '12px 16px';
      }

      button.addEventListener('click', function (e) {
        e.preventDefault();

        const targetId = this.getAttribute('data-target');
        const target = document.querySelector(targetId);
        const isExpanded = this.getAttribute('aria-expanded') === 'true';

        // Toggle current section
        this.setAttribute('aria-expanded', !isExpanded);
        this.classList.toggle('collapsed');

        if (target) {
          target.classList.toggle('show');
          target.setAttribute('aria-hidden', isExpanded);

          // Smooth scroll on mobile
          if (!isExpanded && isMobile()) {
            setTimeout(function () {
              target.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
              });
            }, 300);
          }
        }
      });

      // Touch feedback for accordion buttons
      if (isTouch()) {
        button.addEventListener('touchstart', function () {
          this.classList.add('touch-active');
        }, { passive: TRUE });

        button.addEventListener('touchend', function () {
          const self = this;
          setTimeout(function () {
            self.classList.remove('touch-active');
          }, 150);
        }, { passive: TRUE });
      }
    });
  }

  // Responsive handler for viewport changes
  let resizeTimeout;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function () {
      // Re-initialize mobile optimizations on orientation change
      const elements = document.querySelectorAll('.saho-suggested-reading-enhanced');
      elements.forEach(function (element) {
        initializeMobileOptimizations(element);
      });
    }, 250);
  });

})(Drupal, once);