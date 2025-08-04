/**
 * @file
 * JavaScript for the Featured Biography Block.
 */
(function ($, Drupal) {
  'use strict';

  // Add custom events for inline handlers
  $(document).on('slide.to', '.featured-bio-indicator', function(e, slideIndex) {
    console.log('Custom slide.to event triggered with index:', slideIndex);
    var $block = $(this).closest('.featured-biography-block');
    $block.trigger('goToSlide', [slideIndex]);
  });
  
  // Direct click handlers for prev/next buttons
  $(document).on('click', '.featured-bio-prev-btn', function(e) {
    console.log('Prev button clicked via document handler');
    e.preventDefault();
    $(this).closest('.featured-biography-block').trigger('prevSlide');
    return false;
  });
  
  $(document).on('click', '.featured-bio-next-btn', function(e) {
    console.log('Next button clicked via document handler');
    e.preventDefault();
    $(this).closest('.featured-biography-block').trigger('nextSlide');
    return false;
  });

  Drupal.behaviors.featuredBiographyCarousel = {
    attach: function (context, settings) {
      console.log('Featured Biography Carousel JS loaded and running');
      
      // Initialize carousel for multiple biographies - using very specific selectors
      $('.featured-biography-block', context).once('featured-biography-init').each(function () {
        var $block = $(this);
        var $blockInner = $block.find('.featured-biography-block');
        
        // If no inner container, the block itself might be the container
        if ($blockInner.length === 0) {
          $blockInner = $block;
        }
        
        var $cardsContainer = $blockInner.find('.saho-cards-grid');
        
        // Only initialize carousel if we have multiple cards and carousel is enabled
        if ($cardsContainer.length && $cardsContainer.find('.saho-card').length > 1 && 
            ($blockInner.hasClass('enable-carousel') || $block.hasClass('enable-carousel'))) {
          console.log('Initializing modern carousel for', $blockInner.attr('id') || 'featured biography block');
          initializeModernCarousel($cardsContainer, $blockInner);
        } else {
          console.log('Not initializing carousel - conditions not met', {
            'container': $cardsContainer.length > 0,
            'cards': $cardsContainer.length ? $cardsContainer.find('.saho-card').length : 0,
            'carousel enabled': $blockInner.hasClass('enable-carousel') || $block.hasClass('enable-carousel')
          });
        }
        
        // Add hover effects to biography cards within this specific block only
        $blockInner.find('.saho-card').once('biography-hover').hover(
          function () {
            $(this).addClass('saho-card--hover');
          },
          function () {
            $(this).removeClass('saho-card--hover');
          }
        );
      });
      
// Function to initialize modern carousel with touch support
      function initializeModernCarousel($container, $block) {
        // Get all items and calculate details
        var $items = $container.find('.saho-card');
        var itemCount = $items.length;
        var visibleItems = calculateVisibleItems();
        var currentIndex = 0;
        var isDragging = false;
        var startPos = 0;
        var currentTranslate = 0;
        var prevTranslate = 0;
        var animationID = 0;
        var containerWidth = $container.width();
        var itemWidth = containerWidth / visibleItems;
        
        // Reference to controls
        var $controls = $block.find('.featured-biography-carousel-controls');
        
        // Debug carousel setup
        console.log('Featured Biography: Initializing carousel', {
          'blockId': $block.attr('id') || 'featured-bio-block',
          'controlsFound': $controls.length > 0,
          'totalItems': itemCount,
          'visibleItems': visibleItems
        });
        
        // If controls don't exist in template, don't try to add them
        if ($controls.length === 0) {
          console.log('Featured Biography: No carousel controls found, skipping carousel initialization');
          return; // Exit early - don't try to initialize without controls
        }
        
        // Get indicators container
        var $indicators = $controls.find('.featured-bio-indicators');
        
        var pages = Math.ceil(itemCount / visibleItems);
        
        // Make sure we have valid data to work with
        if (itemCount === 0 || pages === 0) {
          console.log('Featured Biography: No items to show in carousel');
          return; // Exit if no items to show
        }
        
        console.log('Modern carousel setup:', {
          items: itemCount, 
          visibleItems: visibleItems, 
          pages: pages,
          containerWidth: containerWidth,
          itemWidth: itemWidth
        });
        
        // Prepare container for modern carousel
        $container.css({
          'display': 'flex',
          'flex-wrap': 'nowrap',
          'transition': 'transform 0.5s ease',
          'will-change': 'transform',
          'touch-action': 'pan-y',
          '-webkit-overflow-scrolling': 'touch'
        });
        
        // Initialize indicators
        if ($indicators.length > 0) {
          $indicators.empty();
          for (var i = 0; i < pages; i++) {
            $indicators.append('<button class="featured-bio-indicator" data-slide-to="' + i + '" aria-label="Biography ' + (i + 1) + '"></button>');
          }
          
          // Make the first indicator active
          $indicators.find('.featured-bio-indicator').first().addClass('active');
          
          // Add click events to indicators
          $indicators.find('.featured-bio-indicator').off('click').on('click', function () {
            currentIndex = parseInt($(this).attr('data-slide-to')) * visibleItems;
            console.log('Indicator clicked, moving to index:', currentIndex);
            updateCarousel(true);
          });
        }
        
        // Initialize buttons with explicit binding and debugging
        var $prevBtn = $controls.find('.featured-bio-prev-btn');
        var $nextBtn = $controls.find('.featured-bio-next-btn');
        
        // Remove any existing bindings to prevent duplicates
        $prevBtn.off('click');
        $nextBtn.off('click');
        
        // Debug button info
        console.log('Button elements found:', {
          'prevButton': $prevBtn.length > 0,
          'nextButton': $nextBtn.length > 0
        });
        
        // Add new explicit bindings with debugging
        $prevBtn.on('click', function (e) {
          console.log('Previous button clicked');
          e.preventDefault();
          e.stopPropagation();
          slidePrev();
          return false;
        });
        
        $nextBtn.on('click', function (e) {
          console.log('Next button clicked');
          e.preventDefault();
          e.stopPropagation();
          slideNext();
          return false;
        });
        
        // Add direct click handling as a fallback
        $prevBtn[0].onclick = function() {
          console.log('Prev button direct click');
          slidePrev();
          return false;
        };
        
        $nextBtn[0].onclick = function() {
          console.log('Next button direct click');
          slideNext();
          return false;
        };
        
        // Add touch events for mobile
        $container.on('touchstart mousedown', handleStart);
        $container.on('touchend mouseup', handleEnd);
        $container.on('touchmove mousemove', handleMove);
        
        // Handle touch/mouse start
        function handleStart(e) {
          isDragging = true;
          startPos = getPositionX(e);
          animationID = requestAnimationFrame(animation);
          $container.css('cursor', 'grabbing');
        }
        
        // Handle touch/mouse end
        function handleEnd() {
          isDragging = false;
          cancelAnimationFrame(animationID);
          $container.css('cursor', 'grab');
          
          // Calculate slide based on movement
          var diff = currentTranslate - prevTranslate;
          
          // If swipe was significant
          if (Math.abs(diff) > 20) {
            if (diff > 0) {
              slidePrev();
            } else {
              slideNext();
            }
          } else {
            // Snap back to current slide
            updateCarousel(true);
          }
        }
        
        // Handle touch/mouse move
        function handleMove(e) {
          if (isDragging) {
            var currentPosition = getPositionX(e);
            currentTranslate = prevTranslate + currentPosition - startPos;
          }
        }
        
        // Animation function to make drag smooth
        function animation() {
          if (isDragging) {
            setSliderPosition();
            requestAnimationFrame(animation);
          }
        }
        
        // Apply transform to container during drag
        function setSliderPosition() {
          $container.css('transform', 'translateX(' + currentTranslate + 'px)');
        }
        
        // Helper to get position whether touch or mouse
        function getPositionX(e) {
          return e.type.includes('mouse') ? e.pageX : e.originalEvent.touches[0].clientX;
        }
        
        // Slide to previous set of items
        function slidePrev() {
          currentIndex = Math.max(0, currentIndex - visibleItems);
          updateCarousel(true);
        }
        
        // Slide to next set of items
        function slideNext() {
          currentIndex = Math.min(itemCount - visibleItems, currentIndex + visibleItems);
          updateCarousel(true);
        }
        
        // Update carousel state and position
        function updateCarousel(animate) {
          // Update the transform with animation or not
          if (animate) {
            $container.css('transition', 'transform 0.5s ease');
          } else {
            $container.css('transition', 'none');
          }
          
          // Calculate the offset
          var offset = -currentIndex * (containerWidth / visibleItems);
          
          // Store for touch tracking
          prevTranslate = offset;
          currentTranslate = offset;
          
          // Apply transform
          $container.css('transform', 'translateX(' + offset + 'px)');
          
          // Update button states
          updateButtons();
          
          // Update indicators
          updateIndicators();
          
          console.log('Carousel updated:', {
            currentIndex: currentIndex, 
            visibleItems: visibleItems,
            transform: offset + 'px'
          });
        }
        
        // Update button states
        function updateButtons() {
          if (currentIndex <= 0) {
            $prevBtn.addClass('disabled').prop('disabled', true);
          } else {
            $prevBtn.removeClass('disabled').prop('disabled', false);
          }
          
          if (currentIndex >= itemCount - visibleItems) {
            $nextBtn.addClass('disabled').prop('disabled', true);
          } else {
            $nextBtn.removeClass('disabled').prop('disabled', false);
          }
        }
        
        // Update indicator states
        function updateIndicators() {
          if ($indicators.length > 0) {
            var activeIndex = Math.floor(currentIndex / visibleItems);
            $indicators.find('.featured-bio-indicator').removeClass('active');
            $indicators.find('.featured-bio-indicator').eq(activeIndex).addClass('active');
          }
        }
        
        // Handle resize events for responsiveness
        $(window).on('resize', function() {
          // Recalculate dimensions
          containerWidth = $container.width();
          visibleItems = calculateVisibleItems();
          itemWidth = containerWidth / visibleItems;
          
          // Update pages count
          pages = Math.ceil(itemCount / visibleItems);
          
          // Update indicators if needed
          if ($indicators.length > 0 && pages !== $indicators.find('.featured-bio-indicator').length) {
            $indicators.empty();
            for (var i = 0; i < pages; i++) {
              $indicators.append('<button class="featured-bio-indicator" data-slide-to="' + i + '" aria-label="Biography ' + (i + 1) + '"></button>');
            }
            
            // Rebind events
            $indicators.find('.featured-bio-indicator').on('click', function () {
              currentIndex = parseInt($(this).attr('data-slide-to')) * visibleItems;
              updateCarousel(true);
            });
          }
          
          // Ensure current index is valid
          currentIndex = Math.min(currentIndex, itemCount - visibleItems);
          
          // Update carousel without animation
          updateCarousel(false);
        });
        
        // Calculate how many items should be visible based on screen width
        function calculateVisibleItems() {
          var width = $(window).width();
          if (width < 480) return 1;
          if (width < 768) return 1;
          if (width < 992) return 2;
          return 3;
        }
        
        // Initialize with the first position
        updateCarousel(false);
        
        // Apply grabbing cursor for touch UX
        $container.css('cursor', 'grab');
      }
      
      // Add animation when new content is loaded
      $('.block-featured-biography-block .saho-card, div[id^="block-featuredbiography"] .saho-card').once('featured-biography-animate').each(function (index) {
        var $card = $(this);
        setTimeout(function () {
          $card.addClass('saho-card--visible');
        }, 100 * index);
      });
    }
  };

})(jQuery, Drupal);