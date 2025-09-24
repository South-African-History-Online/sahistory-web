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

            // Check if the modal element exists, create if not
            let modalElement = document.getElementById('sharing-modal');
            if (!modalElement) {
                modalElement = this.createSharingModal();
            }

            // Update modal content with current page data
            this.updateSharingContent(modalElement);

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
         * Create the sharing modal HTML structure.
         *
         * @return {HTMLElement}
         *   The created modal element.
         */
        createSharingModal: function () {
            const modalHtml = `
                <div class="modal fade" id="sharing-modal" tabindex="-1" role="dialog" aria-labelledby="sharing-modal-title" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="modal-title" id="sharing-modal-title">Share this page</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="sharing-content">
                                    <!-- Content will be populated dynamically -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Create a temporary container to parse the HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = modalHtml;
            const modalElement = tempDiv.firstElementChild;

            // Add to body
            document.body.appendChild(modalElement);

            return modalElement;
        },

        /**
         * Update sharing modal content with current page data.
         *
         * @param {HTMLElement} modalElement
         *   The modal element to update.
         */
        updateSharingContent: function (modalElement) {
            const currentUrl = window.location.href;
            const pageTitle = document.title;
            const metaDescription = document.querySelector('meta[name="description"]')?.content || '';

            const contentHtml = `
                <!-- URL Copy Section -->
                <div class="share-url-section">
                    <div class="share-url-title">Copy Link</div>
                    <div class="share-url-display">
                        <input type="text" class="share-url-input" id="page-url-input" value="${currentUrl}" readonly>
                        <button class="copy-citation-btn url-copy-btn" id="copy-url-btn">
                            <svg class="copy-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span>Copy URL</span>
                        </button>
                    </div>
                </div>

                <!-- Social Media Section -->
                <div class="social-media-section">
                    <div class="social-media-title">Share on Social Media</div>
                    <div class="social-media-grid">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}"
                           target="_blank" rel="noopener" class="social-share-btn facebook">
                            <svg class="social-share-icon" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <span>Facebook</span>
                        </a>

                        <a href="https://twitter.com/intent/tweet?text=${encodeURIComponent(pageTitle)}&url=${encodeURIComponent(currentUrl)}"
                           target="_blank" rel="noopener" class="social-share-btn x">
                            <svg class="social-share-icon" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            <span>X (Twitter)</span>
                        </a>

                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(currentUrl)}"
                           target="_blank" rel="noopener" class="social-share-btn linkedin">
                            <svg class="social-share-icon" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                            <span>LinkedIn</span>
                        </a>

                        <a href="https://wa.me/?text=${encodeURIComponent(pageTitle + ' - ' + currentUrl)}"
                           target="_blank" rel="noopener" class="social-share-btn whatsapp">
                            <svg class="social-share-icon" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.085"/>
                            </svg>
                            <span>WhatsApp</span>
                        </a>

                        <a href="https://www.reddit.com/submit?url=${encodeURIComponent(currentUrl)}&title=${encodeURIComponent(pageTitle)}"
                           target="_blank" rel="noopener" class="social-share-btn reddit">
                            <svg class="social-share-icon" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>
                            </svg>
                            <span>Reddit</span>
                        </a>

                        <a href="mailto:?subject=${encodeURIComponent(pageTitle)}&body=${encodeURIComponent(pageTitle + ' - ' + currentUrl)}"
                           class="social-share-btn email">
                            <svg class="social-share-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>Email</span>
                        </a>
                    </div>
                </div>
            `;

            const contentContainer = modalElement.querySelector('.sharing-content');
            contentContainer.innerHTML = contentHtml;

            // Initialize copy functionality
            this.initializeUrlCopy();
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

            // Find existing close button and clean it up
            let $closeButton = $modal.find('.btn-close').first();

            // Only create if absolutely none exist
            if ($closeButton.length === 0) {
                $closeButton = $('<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>');
                $modal.find('.modal-header').append($closeButton);
            }

            // Clear any text content to let CSS handle the X
            $closeButton.empty();
            
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
         * Show copy feedback on button.
         *
         * @param {jQuery} $button
         *   The button element.
         * @param {string} message
         *   The feedback message.
         */
        showCopyFeedback: function ($button, message) {
            const originalHtml = $button.html();
            const originalClass = $button.attr('class');

            // Update button with success feedback
            $button.removeClass('btn-outline-secondary').addClass('copied').html(
                '<svg class="citation-icon" fill="currentColor" viewBox="0 0 20 20" width="16" height="16">' +
                '<path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>' +
                '<path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 100 4h2a2 2 0 100-4h-.5a1 1 0 000-2H9a2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/>' +
                '</svg><span>' + message + '</span>'
            );

            // Restore original button after 2 seconds
            setTimeout(function () {
                $button.attr('class', originalClass).html(originalHtml);
            }, 2000);
        },

        /**
         * Fallback copy method using execCommand.
         *
         * @param {string} text
         *   The text to copy.
         * @param {jQuery} $button
         *   The button element.
         */
        fallbackCopyText: function (text, $button) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
                this.showCopyFeedback($button, 'Copied!');
            } catch (err) {
                this.showCopyFeedback($button, 'Failed');
            }

            document.body.removeChild(textarea);
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
            let $closeButton = $modal.find('.btn-close').first();

            // Only create if absolutely none exist
            if ($closeButton.length === 0) {
                $closeButton = $('<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>');
                $modal.find('.modal-header').append($closeButton);
            }

            // Remove any text content to prevent double X
            $closeButton.empty();

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
         * Initialize close button functionality (duplicate method).
         *
         * @param {jQuery} $modal
         *   The jQuery modal object.
         */
        initializeCloseButtonOld: function ($modal) {
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
            // Handle URL copy button - also check for share-url-copy-btn class and copy-url-btn ID
            once('urlCopy', '.url-copy-btn, .share-url-copy-btn, #copy-url-btn', document).forEach(
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
