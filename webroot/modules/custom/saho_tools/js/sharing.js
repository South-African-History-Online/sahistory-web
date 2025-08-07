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
                        this.modal = new bootstrap.Modal(modalElement);
                    } catch (error) {
                    }
                }

                // Show the modal
                try {
                    this.modal.show();
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
                'click',
                function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    Drupal.sahoSharing.hideModalWithjQuery($modal);
                    return false;
                }
            );

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
            $modal.find('[data-bs-dismiss="modal"]').off('click');
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
                                navigator.clipboard.writeText(urlInput.value).then(function() {
                                    Drupal.sahoSharing.showCopyFeedback($(button), 'URL copied!');
                                }).catch(function(err) {
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
                    this.showCopyFeedback($button, 'Copy failed', true);
                }
            } catch (err) {
                this.showCopyFeedback($button, 'Copy failed', true);
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
    $(document).ready(function() {
        Drupal.sahoSharing.initializeUrlCopy();
    });

})(jQuery, Drupal, drupalSettings, once);
