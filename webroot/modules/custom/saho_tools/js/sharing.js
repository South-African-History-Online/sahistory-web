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

            once('sahoSharing', 'a[data-sharing-trigger], button[data-sharing-trigger]', context).forEach(
                function (element) {

                    // Update the element to use our sharing functionality
                    $(element).on(
                        'click',
                        function (e) {
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

            // Check if the modal element exists
            const modalElement = document.getElementById('sharing-modal');
            if (!modalElement) {
                return;
            }

            // Check if we can use Bootstrap's JavaScript API
            if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {

                // Initialize the modal if it's not already
                if (!this.modal) {
                    try {
                        this.modal = new bootstrap.Modal(modalElement, {
                            keyboard: true, // Allow ESC key to close
                            backdrop: true  // Allow clicking outside to close
                        });
                    } catch (error) {
                    }
                }

                // Show the modal
                try {
                    this.modal.show();
                    // Initialize close button functionality after modal is shown
                    setTimeout(() => {
                        this.initializeCloseButton($(modalElement));
                        this.initializeUrlCopy();
                    }, 100);
                } catch (error) {
                    // Fall back to jQuery if Bootstrap API fails
                    this.showModalWithjQuery(modalElement);
                }
            } else {
                // Fall back to jQuery if Bootstrap is not available
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

            // Get jQuery object for the modal
            const $modal = $(modalElement);

            // Find existing close button - don't create duplicate
            let $closeButton = $modal.find('.btn-close');
            
            // If close button exists, ensure it's properly set up but don't create duplicate
            if ($closeButton.length > 0) {
                // Ensure close button text is always visible
                $closeButton.html('×');
            }
            
            // Set up close button click handler
            $closeButton.off('click.sharingClose').on('click.sharingClose', function(e) {
                e.preventDefault();
                e.stopPropagation();
                Drupal.sahoSharing.hideModalWithjQuery($modal);
                return false;
            });

            // Add necessary classes to show the modal
            $modal.addClass('show').css('display', 'block').attr('aria-modal', 'true').removeAttr('aria-hidden');

            // Add backdrop with SAHO red color
            var $backdrop = $('<div class="modal-backdrop fade"></div>');
            $backdrop.css({
                'background-color': 'rgba(153, 0, 0, 0.4)',
                'backdrop-filter': 'blur(4px)'
            });
            $('body').addClass('modal-open').append($backdrop);
            // Trigger reflow before adding show class for animation
            $backdrop[0].offsetHeight;
            $backdrop.addClass('show');

            // Remove any existing event handlers to prevent duplicates
            $modal.find('[data-bs-dismiss="modal"], .btn-close').off('click');
            $('.modal-backdrop').off('click');
            $(document).off('keydown.sharingModal');

            // Handle all dismiss elements
            $modal.find('[data-bs-dismiss="modal"]').off('click.sharingDismiss').on('click.sharingDismiss', function(e) {
                e.preventDefault();
                e.stopPropagation();
                Drupal.sahoSharing.hideModalWithjQuery($modal);
                return false;
            });

            // Handle ESC key
            $(document).on(
                'keydown.sharingModal',
                function (e) {
                    if (e.key === 'Escape') {
                        Drupal.sahoSharing.hideModalWithjQuery($modal);
                    }
                }
            );

            // Handle backdrop clicks
            $('.modal-backdrop').on(
                'click',
                function () {
                    Drupal.sahoSharing.hideModalWithjQuery($modal);
                }
            );

            // Initialize URL copy functionality
            this.initializeUrlCopy();
            
            // Initialize close button
            this.initializeCloseButton($modal);
        },
        
        /**
         * Initialize close button functionality.
         *
         * @param {jQuery} $modal
         *   The jQuery modal object.
         */
        initializeCloseButton: function ($modal) {
            const self = this;
            
            // Find existing close button - don't create duplicate
            let $closeButton = $modal.find('.btn-close');
            
            // Only ensure the × is visible if button exists
            if ($closeButton.length > 0 && $closeButton.html().trim() === '') {
                $closeButton.html('×');
            }
            
            // Set up click handler
            $closeButton.off('click.sharingClose').on('click.sharingClose', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Try Bootstrap API first if available
                if (self.modal && typeof self.modal.hide === 'function') {
                    self.modal.hide();
                } else {
                    // Fall back to jQuery
                    self.hideModalWithjQuery($modal);
                }
                return false;
            });
        },

        /**
         * Hide modal using jQuery.
         *
         * @param {jQuery} $modal
         *   The jQuery modal object to hide.
         */
        hideModalWithjQuery: function ($modal) {

            // Remove classes to hide the modal
            $modal.removeClass('show').css('display', 'none').attr('aria-hidden', 'true').removeAttr('aria-modal');

            // Remove backdrop
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');

            // Remove event handlers
            $modal.find('[data-bs-dismiss="modal"], .btn-close').off('click');
            $('.modal-backdrop').off('click');
            $(document).off('keydown.sharingModal');
        },

        /**
         * Initialize URL copy functionality.
         */
        initializeUrlCopy: function () {
            // Handle URL copy button
            once('urlCopy', '.url-copy-btn', document).forEach(
                function (button) {
                    $(button).on('click', function (e) {
                        e.preventDefault();
                        const urlInput = document.getElementById('page-url-input');

                        if (urlInput) {
                            // Select the text
                            urlInput.select();
                            urlInput.setSelectionRange(0, 99999); // For mobile devices

                            // Try modern clipboard API first
                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                navigator.clipboard.writeText(urlInput.value).then(function () {
                                    Drupal.sahoSharing.showCopyFeedback($(button), 'URL copied!');
                                }).catch(function (err) {
                                    // Fall back to execCommand
                                    Drupal.sahoSharing.fallbackCopyText(urlInput.value, $(button));
                                });
                            } else {
                                // Fall back to execCommand
                                Drupal.sahoSharing.fallbackCopyText(urlInput.value, $(button));
                            }
                        }
                    });
                }
            );
        },

        /**
         * Fallback text copying using execCommand.
         *
         * @param {string} text
         *   The text to copy.
         * @param {jQuery} $button
         *   The button element for feedback.
         */
        fallbackCopyText: function (text, $button) {
            // Create a temporary textarea
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);

            // Select and copy
            textarea.select();
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    this.showCopyFeedback($button, 'URL copied!');
                } else {
                    this.showCopyFeedback($button, 'Copy failed', TRUE);
                }
            } catch (err) {
                this.showCopyFeedback($button, 'Copy failed', TRUE);
            }

            // Clean up
            document.body.removeChild(textarea);
        },

        /**
         * Show feedback for copy operations.
         *
         * @param {jQuery} $button
         *   The button element.
         * @param {string} message
         *   The feedback message.
         * @param {boolean} isError
         *   Whether this is an error message.
         */
        showCopyFeedback: function ($button, message, isError = false) {
            const originalText = $button.html();
            const originalClass = $button.attr('class');

            // Update button
            if (isError) {
                $button.removeClass('url-copy-btn').addClass('btn-danger').html(
                    '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">' +
                    '<path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>' +
                    '</svg> ' + message
                );
            } else {
                $button.removeClass('url-copy-btn').addClass('btn-success').html(
                    '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">' +
                    '<path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>' +
                    '</svg> ' + message
                );
            }

            // Restore original button after 2 seconds
            setTimeout(function () {
                $button.attr('class', originalClass).html(originalText);
            }, 2000);
        }
    };

    // Initialize URL copy functionality when the document is ready
    $(document).ready(function () {
        Drupal.sahoSharing.initializeUrlCopy();
    });

})(jQuery, Drupal, drupalSettings, once);
