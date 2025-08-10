/**
 * @file
 * Provides citation functionality for SAHO website content.
 * JavaScript for the citation functionality.
 */
(function ($, Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Citation behavior.
     */
    Drupal.behaviors.sahoCitation = {
        attach: function (context, settings) {

            // Check if the library is loaded correctly
            if (drupalSettings.sahoTools && drupalSettings.sahoTools.debug) {
            } else {
            }

            // Check if Bootstrap is available
            if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
            } else {
            }

            // Target both links and buttons with data-citation-trigger attribute or href="#cite"
            const citeLinks = document.querySelectorAll('a[data-citation-trigger], a[href="#cite"], button[data-citation-trigger]');

            if (citeLinks.length === 0) {
            }

            once('sahoCitation', 'a[data-citation-trigger], a[href="#cite"], button[data-citation-trigger]', context).forEach(
                function (element) {

                    // Update the element to use our citation functionality
                    $(element).on(
                        'click',
                        function (e) {
                            e.preventDefault();
                            Drupal.sahoCitation.openCitationModal();
                        }
                    );
                }
            );

            // Initialize the copy citation button
            once('sahoCitationCopy', '.copy-citation', context).forEach(
                function (element) {
                    $(element).on(
                        'click',
                        function (e) {
                            e.preventDefault();
                            Drupal.sahoCitation.copyCitation();
                        }
                    );
                }
            );
        }
    };

    /**
     * Citation namespace.
     */
    Drupal.sahoCitation = Drupal.sahoCitation || {
        /**
         * Open the citation modal and load citation data.
         */
        openCitationModal: function () {

            // Check if the modal element exists
            const modalElement = document.getElementById('citation-modal');
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
                    // Initialize copy buttons after modal is shown
                    setTimeout(() => {
                        this.initializeCopyButtons($(modalElement));
                    }, 100);
                } catch (error) {
                    // Fall back to jQuery if Bootstrap API fails
                    this.showModalWithjQuery(modalElement);
                }
            } else {
                // Fall back to jQuery if Bootstrap is not available
                this.showModalWithjQuery(modalElement);
            }

            // Get the node data from drupalSettings
            const nodeData = drupalSettings.sahoTools && drupalSettings.sahoTools.nodeData;
            const pageData = drupalSettings.sahoTools && drupalSettings.sahoTools.pageData;

            if (nodeData) {
                // Generate a basic citation from node data first as immediate content
                this.generateBasicCitationFromNodeData(nodeData);

                // Then try to load citation data from the API (will overwrite if successful)
                this.loadCitationData(nodeData.nid);
            } else if (pageData) {
                // For non-node pages, generate basic citation
                this.generateBasicCitation(pageData);
            } else {
                // Fallback for when no data is available
                $('.citation-content').html('<div class="alert alert-danger">Unable to generate citation for this page.</div>');
            }
        },

        /**
         * Generate basic citation from node data.
         *
         * @param {Object} nodeData
         *   Node data from drupalSettings.
         */
        generateBasicCitationFromNodeData: function (nodeData) {

            // Always use South African History Online (SAHO) as the author
            const author = 'South African History Online (SAHO)';

            // Format dates
            const currentDate = new Date();
            const accessDate = currentDate.toLocaleDateString(
                'en-US',
                {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }
            );

            // Try to get creation date from nodeData
            let creationDate = accessDate;
            let creationYear = currentDate.getFullYear();

            if (nodeData.created) {
                const nodeCreated = new Date(nodeData.created * 1000);
                creationDate = nodeCreated.toLocaleDateString(
                    'en-US',
                    {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    }
                );
                creationYear = nodeCreated.getFullYear();
            }

            // Get page URL
            const pageUrl = window.location.href;

            // Get page title
            const pageTitle = nodeData.title || document.title;

            // Generate Harvard citation - without year after author
            const harvardCitation = `${author} < em > ${pageTitle} < / em > . Available at: ${pageUrl} (Accessed: ${accessDate}).`;

            // Generate APA citation
            const apaCitation = author + '. (' + creationDate + '). <em>' +
            pageTitle + '</em>. ' + pageUrl;

            // Generate Oxford citation
            const oxfordCitation = author + '. "' + pageTitle + '." ' +
            creationDate + '. Accessed ' + accessDate + '. ' + pageUrl + '.';

            // Update citation content
            this.updateCitationContent(
                {
                    harvard: harvardCitation,
                    apa: apaCitation,
                    oxford: oxfordCitation
                }
            );
        },

        /**
         * Generate basic citation for non-node pages.
         *
         * @param {Object} pageData
         *   Basic page data.
         */
        generateBasicCitation: function (pageData) {
            // Always use South African History Online (SAHO) as the author
            const author = 'South African History Online (SAHO)';

            const currentDate = new Date();
            const formattedDate = currentDate.toLocaleDateString(
                'en-US',
                {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }
            );

            // Generate Harvard citation - without year after author
            const harvardCitation = `${author} < em > ${pageData.title} < / em > . Available at: ${pageData.url} (Accessed: ${formattedDate}).`;

            // Generate APA citation
            const apaCitation = author + '. (' + formattedDate + '). <em>' +
            pageData.title + '</em>. ' + pageData.url;

            // Generate Oxford citation
            const oxfordCitation = author + '. "' + pageData.title + '." ' +
            formattedDate + '. Accessed ' + formattedDate + '. ' + pageData.url + '.';

            // Update citation content
            this.updateCitationContent(
                {
                    harvard: harvardCitation,
                    apa: apaCitation,
                    oxford: oxfordCitation
                }
            );
        },

        /**
         * Load citation data from the server.
         *
         * @param {number} nid
         *   The node ID.
         */
        loadCitationData: function (nid) {
            const self = this;

            // Show loading state with spinner
            $('.citation-content').html('<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></div>');

            // Log the URL we're requesting
            const apiUrl = Drupal.url('api/citation/' + nid);

            // Make an AJAX request to get the citation data
            $.ajax(
                {
                    url: apiUrl,
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response && response.citations) {
                            // Update the citation content
                            self.updateCitationContent(response.citations);

                            // Check if we have image data and update the modal
                            if (response.image_info && response.image_info.has_image) {
                                self.addImageToModal(response.image_info);
                            }
                        } else {
                            // Show error message
                            $('.citation-content').html('<div class="alert alert-danger">Failed to load citation data. Invalid response format.</div>');
                        }
                    },
                    error: function (xhr, status, error) {

                        // Show detailed error message
                        let errorMessage = 'Failed to load citation data.';
                        if (xhr.status) {
                            errorMessage += ' Status: ' + xhr.status;
                        }
                        if (xhr.responseText) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.error) {
                                        errorMessage += ' Error: ' + response.error;
                                }
                            } catch (e) {
                                // If we can't parse the response as JSON, just use the raw text
                                if (xhr.responseText.length < 100) {
                                    errorMessage += ' ' + xhr.responseText;
                                }
                            }
                        }

                        // Show error message
                        $('.citation-content').html('<div class="alert alert-danger">' + errorMessage + '</div>');
                    }
                }
            );
        },

        /**
         * Update citation content in the modal.
         *
         * @param {Object} citations
         *   The citation data.
         */
        updateCitationContent: function (citations) {
            // Update each citation format - use the correct selectors
            $('.apa-citation .citation-content').html(citations.apa);
            $('.harvard-citation .citation-content').html(citations.harvard);
            $('.oxford-citation .citation-content').html(citations.oxford);

            // Re-initialize copy buttons after content is loaded
            const modalElement = document.getElementById('citation-modal');
            if (modalElement) {
                this.initializeCopyButtons($(modalElement));
            }
        },

        /**
         * Fallback method for individual citation copy using execCommand.
         *
         * @param {string} text
         *   The text to copy.
         * @param {jQuery} $button
         *   The button element.
         */
        fallbackIndividualCopy: function (text, $button) {
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
                    this.showIndividualCopyFeedback($button, 'Copied!');
                } else {
                    this.showIndividualCopyFeedback($button, 'Failed', TRUE);
                }
            } catch (err) {
                this.showIndividualCopyFeedback($button, 'Failed', TRUE);
            }

            // Clean up
            document.body.removeChild(textarea);
        },

        /**
         * Show feedback for individual copy operations.
         *
         * @param {jQuery} $button
         *   The button element.
         * @param {string} message
         *   The feedback message.
         * @param {boolean} isError
         *   Whether this is an error message.
         */
        showIndividualCopyFeedback: function ($button, message, isError = false) {
            const originalHtml = $button.html();
            const originalClass = $button.attr('class');

            // Update button
            if (isError) {
                $button.removeClass('btn-primary').addClass('btn-danger').html(
                    '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">' +
                    '<path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>' +
                    '</svg><span class="visually-hidden">' + message + '</span>'
                );
            } else {
                $button.removeClass('btn-primary').addClass('btn-success').html(
                    '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">' +
                    '<path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>' +
                    '</svg><span class="visually-hidden">' + message + '</span>'
                );
            }

            // Restore original button after 1.5 seconds
            setTimeout(function () {
                $button.attr('class', originalClass).html(originalHtml);
            }, 1500);
        },

        /**
         * Add image to the citation modal.
         *
         * @param {Object} imageInfo
         *   The image information.
         */
        addImageToModal: function (imageInfo) {
            // Check if image container already exists
            let $imageContainer = $('.citation-image-container');
            if (!$imageContainer.length) {
                // Create image container
                $imageContainer = $('<div class="citation-image-container mb-3"></div>');
                $('.modal-body').prepend($imageContainer);
            }

            // Clear existing content
            $imageContainer.empty();

            // Add image
            const $image = $(
                '<img>',
                {
                    src: imageInfo.image_url,
                    alt: imageInfo.image_alt || 'Image for citation',
                    class: 'img-fluid rounded mb-2'
                }
            );

            $imageContainer.append($image);

            // Add caption if available
            if (imageInfo.image_title || imageInfo.photographer || imageInfo.copyright) {
                const captionParts = [];

                if (imageInfo.image_title) {
                    captionParts.push('<strong>' + imageInfo.image_title + '</strong>');
                }

                if (imageInfo.photographer) {
                    captionParts.push('Photo by: ' + imageInfo.photographer);
                }

                if (imageInfo.copyright) {
                    captionParts.push(imageInfo.copyright);
                }

                if (captionParts.length) {
                    const $caption = $('<figcaption class="figure-caption text-center">' + captionParts.join(' | ') + '</figcaption>');
                    $imageContainer.append($caption);
                }
            }
        },

        /**
         * Copy text to clipboard and show feedback.
         *
         * @param {string} text
         *   The text to copy.
         * @param {jQuery} $element
         *   The element to show feedback on.
         * @param {boolean} autoClose
         *   Whether to automatically close the modal after copying.
         */
        copyTextToClipboard: function (text, $element, autoClose) {
            // Create a temporary textarea element to copy the text
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);

            // Select and copy the text
            textarea.select();
            document.execCommand('copy');

            // Remove the textarea
            document.body.removeChild(textarea);

            // Show visual feedback
            if ($element) {
                $element.addClass('copying');
                setTimeout(
                    function () {
                        $element.removeClass('copying');

                        // Auto-close the modal after copying if requested
                        if (autoClose) {
                            const $modal = $('#citation-modal');
                            // Check if we're using Bootstrap's Modal API
                            if (Drupal.sahoCitation.modal) {
                                Drupal.sahoCitation.modal.hide();
                            } else {
                                // Fall back to jQuery
                                Drupal.sahoCitation.hideModalWithjQuery($modal);
                            }
                        }
                    },
                    1500
                );
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

            // Explicitly add a click handler to the close button
            $closeButton.off('click').on(
                'click',
                function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    Drupal.sahoCitation.hideModalWithjQuery($modal);
                    return FALSE;
                }
            );

            // Add necessary classes to show the modal
            $modal.addClass('show').css('display', 'block').attr('aria-modal', 'true').removeAttr('aria-hidden');

            // Add backdrop
            $('body').addClass('modal-open').append('<div class="modal-backdrop fade show"></div>');

            // Remove any existing event handlers to prevent duplicates
            $modal.find('[data-bs-dismiss="modal"]').off('click');
            $('.modal-backdrop').off('click');
            $(document).off('keydown.citationModal');
            $modal.find('.copy-citation').off('click');
            $modal.find('.btn-copy-citation').off('click');

            // Enhance the close button visibility with better styling - use text instead of SVG
            $modal.find('.btn-close').css(
                {
                    'display': 'block',
                    'position': 'absolute',
                    'right': '1rem',
                    'top': '1rem',
                    'font-size': '2rem',
                    'font-weight': 'bold',
                    'line-height': '1',
                    'color': '#fff',
                    'opacity': '1',
                    'background-color': '#dc3545',
                    'border-radius': '50%',
                    'width': '2rem',
                    'height': '2rem',
                    'text-align': 'center',
                    'padding': '0',
                    'border': '2px solid white',
                    'box-shadow': '0 2px 5px rgba(0, 0, 0, 0.3)',
                    'z-index': '1060',
                    'cursor': 'pointer'
                }
            ).html('×');

            // Handle close button clicks with a more specific selector
            $modal.find('button[data-bs-dismiss="modal"], .btn-close').on(
                'click',
                function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    Drupal.sahoCitation.hideModalWithjQuery($modal);
                    return FALSE;
                }
            );

            // Handle ESC key
            $(document).on(
                'keydown.citationModal',
                function (e) {
                    if (e.key === 'Escape') {
                        Drupal.sahoCitation.hideModalWithjQuery($modal);
                    }
                }
            );

            // Handle backdrop clicks
            $('.modal-backdrop').on(
                'click',
                function () {
                    Drupal.sahoCitation.hideModalWithjQuery($modal);
                }
            );

            // Handle tab switching without Bootstrap
            $modal.find('[data-bs-toggle="tab"]').off('click').on(
                'click',
                function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const $this = $(this);
                    const target = $this.attr('data-bs-target') || $this.attr('href');

                    // Remove active class from all tabs and tab panes
                    $modal.find('.nav-link').removeClass('active');
                    $modal.find('.tab-pane').removeClass('show active');

                    // Add active class to clicked tab and its target pane
                    $this.addClass('active');
                    $(target).addClass('show active');

                    return FALSE;
                }
            );

            // Also handle regular tab links that might not have data-bs-toggle
            $modal.find('.nav-tabs a').off('click').on(
                'click',
                function (e) {
                    if (!$(this).attr('data-bs-toggle')) {
                        e.preventDefault();
                        e.stopPropagation();
                        const $this = $(this);
                        const target = $this.attr('href');

                        // Remove active class from all tabs and tab panes
                        $modal.find('.nav-link').removeClass('active');
                        $modal.find('.tab-pane').removeClass('show active');

                        // Add active class to clicked tab and its target pane
                        $this.addClass('active');
                        $(target).addClass('show active');

                        return FALSE;
                    }
                }
            );

            // Initialize copy buttons
            this.initializeCopyButtons($modal);

            // Initialize the new copy all button
            this.initializeCopyAllButton($modal);
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
            $(document).off('keydown.citationModal');
            $modal.find('[data-bs-toggle="tab"]').off('click');

        },

        /**
         * Initialize copy buttons in the modal.
         *
         * @param {jQuery} $modal
         *   The jQuery modal object.
         */
        initializeCopyButtons: function ($modal) {
            const self = this;

            // Remove any existing click handlers first
            $modal.find('.copy-individual').off('click');
            $modal.find('.copy-citation').off('click');
            $modal.find('.copy-all-citations').off('click');

            // Individual copy buttons
            $modal.find('.copy-individual').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const format = $(this).data('format');
                const citationText = $('.' + format + '-citation .citation-content').text().trim();

                if (citationText) {
                    // Try modern clipboard API first
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(citationText).then(() => {
                            self.showIndividualCopyFeedback($(this), 'Copied!');
                        }).catch(() => {
                            // Fall back to execCommand
                            self.fallbackIndividualCopy(citationText, $(this));
                        });
                    } else {
                        // Fall back to execCommand
                        self.fallbackIndividualCopy(citationText, $(this));
                    }
                }
                return FALSE;
            });

            // Copy all button if it exists
            const $copyAllButton = $modal.find('.copy-all-citations, .copy-citation');
            $copyAllButton.off('click').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                self.copyAllCitations();
                return FALSE;
            });
        },

        /**
         * Initialize the copy all formats button.
         *
         * @param {jQuery} $modal
         *   The jQuery modal object.
         */
        initializeCopyAllButton: function ($modal) {
            const self = this;

            // Handle the new copy all citations button
            const $copyAllButton = $modal.find('.copy-all-citations');
            $copyAllButton.off('click').on(
                'click',
                function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.copyAllCitations();
                    return FALSE;
                }
            );
        },

        /**
         * Copy all citation formats to clipboard.
         */
        copyAllCitations: function () {
            // Get all citation content
            let allCitationsText = '';
            const formats = ['apa', 'harvard', 'oxford'];
            const formatLabels = {
                'apa': 'APA (7th edition)',
                'harvard': 'Harvard (Author-Date)',
                'oxford': 'Oxford (Footnote style)'
            };

            formats.forEach(
                function (format) {
                    const $citationElement = $('.' + format + '-citation .citation-content');
                    const citationText = $citationElement.text().trim();

                    if (citationText && citationText !== '') {
                        allCitationsText += formatLabels[format] + ':\n' + citationText + '\n\n';
                    }
                }
            );

            // Trim the extra newlines at the end
            allCitationsText = allCitationsText.trim();

            if (allCitationsText) {
                // Try modern clipboard API first
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(allCitationsText).then(function () {
                        Drupal.sahoCitation.showCopyAllFeedback('All formats copied!');
                    }).catch(function (err) {
                        // Fall back to execCommand
                        Drupal.sahoCitation.fallbackCopyAllText(allCitationsText);
                    });
                } else {
                    // Fall back to execCommand
                    this.fallbackCopyAllText(allCitationsText);
                }
            } else {
                this.showCopyAllFeedback('No citations available', TRUE);
            }
        },

        /**
         * Fallback method for copying all citations using execCommand.
         *
         * @param {string} text
         *   The text to copy.
         */
        fallbackCopyAllText: function (text) {
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
                    this.showCopyAllFeedback('All formats copied!');
                } else {
                    this.showCopyAllFeedback('Copy failed', TRUE);
                }
            } catch (err) {
                this.showCopyAllFeedback('Copy failed', TRUE);
            }

            // Clean up
            document.body.removeChild(textarea);
        },

        /**
         * Show feedback for copy all operation.
         *
         * @param {string} message
         *   The feedback message.
         * @param {boolean} isError
         *   Whether this is an error message.
         */
        showCopyAllFeedback: function (message, isError = false) {
            const $button = $('.copy-all-citations');
            const originalText = $button.html();
            const originalClass = $button.attr('class');

            // Update button
            if (isError) {
                $button.removeClass('btn-primary').addClass('btn-danger').html(
                    '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-2">' +
                    '<path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>' +
                    '</svg>' + message
                );
            } else {
                $button.removeClass('btn-primary').addClass('btn-success').html(
                    '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-2">' +
                    '<path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>' +
                    '</svg>' + message
                );
            }

            // Restore original button after 2.5 seconds
            setTimeout(function () {
                $button.attr('class', originalClass).html(originalText);
            }, 2500);
        },

        /**
         * Copy the active citation to the clipboard.
         */
        copyCitation: function () {
            // Since all citations are now visible, we'll copy all of them
            let allCitationsText = '';

            // Get all citation content
            const formats = ['apa', 'oxford', 'harvard'];
            const formatLabels = {
                'apa': 'APA (7th edition)',
                'oxford': 'Oxford (Footnote style)',
                'harvard': 'Harvard'
            };

            formats.forEach(
                function (format) {
                    const $citationElement = $('.' + format + '-citation .citation-content');
                    const citationText = $citationElement.text().trim();

                    if (citationText) {
                        allCitationsText += formatLabels[format] + ':\n' + citationText + '\n\n';
                    }
                }
            );

            // Trim the extra newlines at the end
            allCitationsText = allCitationsText.trim();

            // Copy the text and show feedback, auto-close the modal
            this.copyTextToClipboard(allCitationsText, $('.citation-format'), TRUE);

            // Update the button text temporarily
            const $button = $('.copy-citation');
            const originalText = $button.text();
            $button.text('All Formats Copied!');
            setTimeout(
                function () {
                    $button.text(originalText);
                },
                1500
            );

        }
    };

})(jQuery, Drupal, drupalSettings, once);
