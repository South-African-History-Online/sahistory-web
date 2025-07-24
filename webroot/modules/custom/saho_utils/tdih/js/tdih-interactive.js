/**
 * @file
 * JavaScript for the TDIH Interactive Block.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Behavior for the TDIH Interactive Block.
   */
  Drupal.behaviors.tdihInteractive = {
    attach: function (context, settings) {
      // Initialize the date picker with today's date if not already set.
      var today = new Date();
      var todayFormatted = today.getFullYear() + '-' +
                          ('0' + (today.getMonth() + 1)).slice(-2) + '-' +
                          ('0' + today.getDate()).slice(-2);

      // Set the date picker to today's date if it's empty.
      $('.tdih-birthday-date-picker', context).once('tdih-init').each(function () {
        if (!$(this).val()) {
          $(this).val(todayFormatted);
        }
      });

      // Add toggle button for Today in history section
      $('.tdih-interactive-block', context).once('tdih-toggle').each(function () {
        var $block = $(this);
        var $wrapper = $block.find('.tdih-today-history-wrapper');

        // Only add toggle if the wrapper exists
        if ($wrapper.length) {
          // Create toggle button with initial text based on visibility
          var isVisible = $wrapper.is(':visible');
          var $toggleButton = $('<button>', {
            'class': 'tdih-toggle-button' + (isVisible ? '' : ' active'),
            'text': isVisible ? Drupal.t('Hide Today in History') : Drupal.t('Show Today in History')
          });

          // Insert after header
          $toggleButton.insertAfter($block.find('.tdih-interactive-header'));

          // Function to update button text based on state
          function updateButtonText(isVisible) {
            $toggleButton.text(isVisible ?
              Drupal.t('Hide Today in History') :
              Drupal.t('Show Today in History')
            );
          }

          // Function to set visibility state
          function setVisibility(isVisible) {
            // Set visibility of the wrapper
            if (isVisible) {
              $wrapper.slideDown();
            } else {
              $wrapper.slideUp();
            }

            // Update button state
            $toggleButton.toggleClass('active', !isVisible);
            updateButtonText(isVisible);

            // Store preference in localStorage
            try {
              localStorage.setItem('tdihTodayHistoryVisibility', isVisible ? 'visible' : 'hidden');
            } catch (e) {
              // Local storage not available
            }
          }

          // Add click handler
          $toggleButton.on('click', function () {
            // Toggle visibility (invert current state)
            var isCurrentlyVisible = $wrapper.is(':visible');
            setVisibility(!isCurrentlyVisible);
          });

          // Always show the Today in History section by default
          // Clear any stored preference to reset to default state
          try {
            localStorage.removeItem('tdihTodayHistoryVisibility');
            // Ensure the section is visible
            if (!$wrapper.is(':visible')) {
              setVisibility(TRUE);
            }
          } catch (e) {
            // Local storage not available
          }
        }
      });

      // Add hover effects to event items.
      $('.tdih-event-item', context).once('tdih-hover').hover(
        function () {
          $(this).addClass('tdih-event-item-hover');
        },
        function () {
          $(this).removeClass('tdih-event-item-hover');
        }
      );

      // Add click handler to show/hide event body in compact mode.
      $('.compact-mode .tdih-event-title a', context).once('tdih-toggle').click(function (e) {
        // Only if there's a body to toggle.
        var $item = $(this).closest('.tdih-event-item');
        var $body = $item.find('.tdih-event-body');

        if ($body.length) {
          e.preventDefault();
          $body.slideToggle();
          $item.toggleClass('expanded');
          return FALSE;
        }
      });

      // Add animation to newly loaded events.
      $('.tdih-events-container', context).once('tdih-animate').each(function () {
        $(this).hide().fadeIn(500);
      });

      // Enhance the AJAX progress indicator with African drum animation.
      $(document).once('tdih-ajax-setup').ajaxSend(function (event, xhr, settings) {
        if (settings.url && settings.url.indexOf('tdih') !== -1) {
          // Add a class to the body during loading for potential page-wide effects.
          $('body').addClass('tdih-loading');

          // Add a custom message to the throbber if not already present.
          setTimeout(function () {
            if ($('.tdih-interactive-block .ajax-progress .message').length &&
                !$('.tdih-interactive-block .ajax-progress .message .drum-text').length) {
              $('.tdih-interactive-block .ajax-progress .message').append(
                '<span class="drum-text"> Beating the drums of history...</span>'
              );
            }
          }, 10);
        }
      });

      // Handle AJAX completion for TDIH requests.
      $(document).once('tdih-ajax-complete').ajaxComplete(function (event, xhr, settings) {
        if (settings.url && settings.url.indexOf('tdih') !== -1) {
          // Remove loading class.
          $('body').removeClass('tdih-loading');

          // Add a subtle highlight effect to new items.
          $('.tdih-event-item').addClass('highlight');
          setTimeout(function () {
            $('.tdih-event-item').removeClass('highlight');
          }, 1000);

          // Add birthday events class when the birthday form is submitted
          if (settings.url.indexOf('birthday-date-form') !== -1) {
            // Find the events container that was just updated
            var $eventsContainer = $('.tdih-events-container').last();

            // Add the birthday events class to highlight these events
            $eventsContainer.addClass('tdih-birthday-events');

            // Update the heading to indicate these are birthday events
            var date = $('.tdih-birthday-date-picker').val();
            if (date) {
              var dateObj = new Date(date);
              var formattedDate = dateObj.toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric'
              });

              $eventsContainer.find('h3').text('Events on ' + formattedDate);

              // Check for exact date matches (day, month, AND year)
              var selectedYear = dateObj.getFullYear();

              // Process each event item to find exact matches
              $eventsContainer.find('.tdih-event-item').each(function () {
                var $eventItem = $(this);
                var eventDateText = $eventItem.find('.tdih-event-date').text();

                // Extract the year from the event date text (format: "DD Month YYYY")
                var eventYear = parseInt(eventDateText.match(/\d{4}/)[0], 10);

                // If the years match, this is an exact date match
                if (eventYear === selectedYear) {
                  $eventItem.addClass('tdih-exact-match');

                  // Move the exact match to the top of the list
                  $eventItem.parent().prepend($eventItem);
                }
              });
            }
          } else {
            // For regular "today's events", ensure the class is removed
            $('.tdih-events-container').removeClass('tdih-birthday-events');
          }
        }
      });

      // Add CSS for the highlight effect.
      $('head').once('tdih-highlight-css').append(
        '<style>' +
        '@keyframes tdih-highlight-pulse {' +
        '  0% { background-color: rgba(205, 133, 63, 0.2); }' +
        '  100% { background-color: transparent; }' +
        '}' +
        '.tdih-event-item.highlight {' +
        '  animation: tdih-highlight-pulse 1s ease-out;' +
        '}' +
        '</style>'
      );

      // Initialize lazy loading for images.
      $('.lazy', context).once('tdih-lazy-load').each(function () {
        // Check if IntersectionObserver is supported
        if ('IntersectionObserver' in window) {
          var lazyImageObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting) {
                var lazyImage = entry.target;

                // Set the src to the data-src value
                if (lazyImage.dataset.src) {
                  lazyImage.src = lazyImage.dataset.src;

                  // When the image is loaded, remove the lazy class
                  lazyImage.onload = function () {
                    lazyImage.classList.remove('lazy');
                    lazyImage.removeAttribute('data-src');
                  };

                  // Stop observing the image
                  observer.unobserve(lazyImage);
                }
              }
            });
          }, {
            rootMargin: '100px 0px', // Load images when they're 100px from entering the viewport
            threshold: 0.01 // Trigger when at least 1% of the image is visible
          });

          // Start observing the image
          lazyImageObserver.observe(this);
        } else {
          // Fallback for browsers that don't support IntersectionObserver
          // Load all images immediately
          var lazyImages = document.querySelectorAll('.lazy');
          lazyImages.forEach(function (lazyImage) {
            if (lazyImage.dataset.src) {
              lazyImage.src = lazyImage.dataset.src;
              lazyImage.classList.remove('lazy');
              lazyImage.removeAttribute('data-src');
            }
          });
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);