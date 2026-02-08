/**
 * @file
 * Carousel functionality for History Through Pictures block.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.historyPicturesCarousel = {
    attach: function (context, settings) {
      once('history-carousel', '.history-pictures-carousel', context).forEach(function (carousel) {
        const track = carousel.querySelector('.carousel-track');
        const prevBtn = carousel.querySelector('.carousel-prev');
        const nextBtn = carousel.querySelector('.carousel-next');
        const items = carousel.querySelectorAll('.history-picture-item');

        if (!track || !prevBtn || !nextBtn || items.length === 0) {
          return;
        }

        let currentIndex = 0;
        const itemsToShow = getItemsToShow();
        const totalSlides = Math.ceil(items.length / itemsToShow);

        // Update items to show based on screen size
        function getItemsToShow() {
          if (window.innerWidth < 768) {
            return 1;
          } else if (window.innerWidth < 1024) {
            return 2;
          } else {
            return 3;
          }
        }

        // Scroll to specific index
        function scrollToIndex(index) {
          const itemWidth = items[0].offsetWidth;
          const gap = 24; // 1.5rem in pixels
          const scrollPosition = index * itemsToShow * (itemWidth + gap);

          track.scrollTo({
            left: scrollPosition,
            behavior: 'smooth'
          });

          currentIndex = index;
          updateButtons();
        }

        // Update button states
        function updateButtons() {
          prevBtn.disabled = currentIndex === 0;
          nextBtn.disabled = currentIndex >= totalSlides - 1;

          prevBtn.style.opacity = currentIndex === 0 ? '0.5' : '1';
          nextBtn.style.opacity = currentIndex >= totalSlides - 1 ? '0.5' : '1';
          prevBtn.style.cursor = currentIndex === 0 ? 'not-allowed' : 'pointer';
          nextBtn.style.cursor = currentIndex >= totalSlides - 1 ? 'not-allowed' : 'pointer';
        }

        // Previous button click
        prevBtn.addEventListener('click', function () {
          if (currentIndex > 0) {
            scrollToIndex(currentIndex - 1);
          }
        });

        // Next button click
        nextBtn.addEventListener('click', function () {
          if (currentIndex < totalSlides - 1) {
            scrollToIndex(currentIndex + 1);
          }
        });

        // Handle window resize
        let resizeTimeout;
        window.addEventListener('resize', function () {
          clearTimeout(resizeTimeout);
          resizeTimeout = setTimeout(function () {
            const newItemsToShow = getItemsToShow();
            if (newItemsToShow !== itemsToShow) {
              currentIndex = 0;
              scrollToIndex(0);
            }
          }, 250);
        });

        // Keyboard navigation
        carousel.addEventListener('keydown', function (e) {
          if (e.key === 'ArrowLeft') {
            prevBtn.click();
          } else if (e.key === 'ArrowRight') {
            nextBtn.click();
          }
        });

        // Touch/swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        track.addEventListener('touchstart', function (e) {
          touchStartX = e.changedTouches[0].screenX;
        });

        track.addEventListener('touchend', function (e) {
          touchEndX = e.changedTouches[0].screenX;
          handleSwipe();
        });

        function handleSwipe() {
          const swipeThreshold = 50;
          if (touchEndX < touchStartX - swipeThreshold) {
            // Swipe left - next
            nextBtn.click();
          } else if (touchEndX > touchStartX + swipeThreshold) {
            // Swipe right - previous
            prevBtn.click();
          }
        }

        // Initialize button states
        updateButtons();
      });
    }
  };

})(Drupal, once);
