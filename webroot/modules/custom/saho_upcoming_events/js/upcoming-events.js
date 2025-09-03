/**
 * @file
 * SAHO Upcoming Events JavaScript behaviors.
 */

(function (Drupal, drupalSettings) {

  'use strict';

  /**
   * Make event cards fully clickable.
   */
  Drupal.behaviors.sahoUpcomingEventsCardClick = {
    attach: function (context, settings) {
      // Make entire event card clickable
      var eventCards = context.querySelectorAll('.event-card-clickable');
      
      eventCards.forEach(function(card) {
        // Find the main link in the card (now it's in the button)
        var titleLink = card.querySelector('.event-card__button');
        
        if (titleLink && !card.hasAttribute('data-clickable')) {
          // Mark as processed to avoid duplicate handlers
          card.setAttribute('data-clickable', 'true');
          
          // Add click handler to entire card
          card.addEventListener('click', function(e) {
            // Don't trigger if clicking on existing links
            if (e.target.tagName !== 'A' && !e.target.closest('a')) {
              // Simulate clicking the title link
              if (e.ctrlKey || e.metaKey) {
                // Open in new tab if Ctrl/Cmd+Click
                window.open(titleLink.href, '_blank');
              } else {
                window.location.href = titleLink.href;
              }
            }
          });
          
          // Add keyboard support
          card.setAttribute('tabindex', '0');
          card.setAttribute('role', 'button');
          card.setAttribute('aria-label', 'View event: ' + titleLink.textContent.trim());
          
          card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              window.location.href = titleLink.href;
            }
          });
          
          // Add visual feedback for focus
          card.addEventListener('focus', function() {
            card.style.outline = '2px solid #e74c3c';
            card.style.outlineOffset = '2px';
          });
          
          card.addEventListener('blur', function() {
            card.style.outline = 'none';
          });
        }
      });
    }
  };

})(Drupal, drupalSettings);