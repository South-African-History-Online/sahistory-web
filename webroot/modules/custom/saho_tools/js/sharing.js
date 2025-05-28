/**
 * @file
 * Provides social media sharing functionality for SAHO website content.
 * JavaScript for the sharing functionality.
 */
(function ($, Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Sharing behavior.
     */
    Drupal.behaviors.sahoSharing = {
        attach: function (context, settings) {
            console.log('SAHO Sharing behavior attached');
      
            // Target both links and buttons with data-sharing-trigger attribute
            const sharingLinks = document.querySelectorAll('a[data-sharing-trigger], button[data-sharing-trigger]');
            console.log('Found sharing elements:', sharingLinks.length);
      
            once('sahoSharing', 'a[data-sharing-trigger], button[data-sharing-trigger]', context).forEach(
                function (element) {
                    console.log('Attaching click handler to:', element);
        
                    // Update the element to use our sharing functionality
                    $(element).on(
                        'click', function (e) {
                            console.log('Sharing element clicked');
                            e.preventDefault();
                            Drupal.sahoSharing.openSharingModal();
                        }
                    );
                }
            );
        }
    };

    /**
     * Sharing namespace.
     */
    Drupal.sahoSharing = Drupal.sahoSharing || {
        /**
         * Open the sharing modal.
         */
        openSharingModal: function () {
            console.log('Opening sharing modal');
      
            // Check if the modal element exists
            const modalElement = document.getElementById('sharing-modal');
            if (!modalElement) {
                console.error('Sharing modal element not found! Make sure the HTML is added to the page.');
                return;
            }
      
            // Check if we can use Bootstrap's JavaScript API
            if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
                console.log('Using Bootstrap JS API for modal');
        
                // Initialize the modal if it's not already
                if (!this.modal) {
                    try {
                        console.log('Initializing Bootstrap modal');
                        this.modal = new bootstrap.Modal(modalElement);
                    } catch (error) {
                        console.error('Error initializing Bootstrap modal:', error);
                    }
                }

                // Show the modal
                try {
                    console.log('Showing modal');
                    this.modal.show();
                } catch (error) {
                    console.error('Error showing modal:', error);
                    // Fall back to jQuery if Bootstrap API fails
                    this.showModalWithjQuery(modalElement);
                }
            } else {
                // Fall back to jQuery if Bootstrap is not available
                console.warn('Bootstrap JS API not available, falling back to jQuery');
                this.showModalWithjQuery(modalElement);
            }
        },

        /**
         * Show modal using jQuery instead of Bootstrap JS API.
         * 
         * @param {HTMLElement} modalElement
         *   The modal element to show.
         */
        showModalWithjQuery: function (modalElement) {
            console.log('Showing modal with jQuery');
      
            // Get jQuery object for the modal
            const $modal = $(modalElement);
      
            // Ensure the close button (X) is visible in the top right corner
            const $closeButton = $modal.find('.btn-close');
            if ($closeButton.length === 0) {
                // If no close button exists, add one
                $modal.find('.modal-header').append('<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>');
            } else {
                // Make sure it's visible and styled correctly
                $closeButton.css(
                    {
                        'display': 'block',
                        'position': 'absolute',
                        'right': '1rem',
                        'top': '1rem',
                        'font-size': '1.5rem',
                        'font-weight': 'bold',
                        'line-height': '1',
                        'color': '#000',
                        'opacity': '0.5',
                        'background': 'transparent',
                        'border': '0',
                        'padding': '0.25rem 0.5rem'
                    }
                ).html('&times;');
            }
      
            // Add necessary classes to show the modal
            $modal.addClass('show').css('display', 'block').attr('aria-modal', 'true').removeAttr('aria-hidden');
      
            // Add backdrop
            $('body').addClass('modal-open').append('<div class="modal-backdrop fade show"></div>');
      
            // Remove any existing event handlers to prevent duplicates
            $modal.find('[data-bs-dismiss="modal"]').off('click');
            $('.modal-backdrop').off('click');
            $(document).off('keydown.sharingModal');
      
            // Handle close button clicks
            $modal.find('[data-bs-dismiss="modal"]').on(
                'click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Close button clicked');
                    Drupal.sahoSharing.hideModalWithjQuery($modal);
                    return false;
                }
            );
      
            // Handle ESC key
            $(document).on(
                'keydown.sharingModal', function (e) {
                    if (e.key === 'Escape') {
                        console.log('ESC key pressed');
                        Drupal.sahoSharing.hideModalWithjQuery($modal);
                    }
                }
            );
      
            // Handle backdrop clicks
            $('.modal-backdrop').on(
                'click', function () {
                    console.log('Backdrop clicked');
                    Drupal.sahoSharing.hideModalWithjQuery($modal);
                }
            );
        },
    
        /**
         * Hide modal using jQuery.
         * 
         * @param {jQuery} $modal
         *   The jQuery modal object to hide.
         */
        hideModalWithjQuery: function ($modal) {
            console.log('Hiding modal with jQuery');
      
            // Remove classes to hide the modal
            $modal.removeClass('show').css('display', 'none').attr('aria-hidden', 'true').removeAttr('aria-modal');
      
            // Remove backdrop
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
      
            // Remove event handlers
            $modal.find('[data-bs-dismiss="modal"]').off('click');
            $('.modal-backdrop').off('click');
            $(document).off('keydown.sharingModal');
        }
    };

})(jQuery, Drupal, drupalSettings, once);