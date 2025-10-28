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
            const self = this;
            const modalElement = document.getElementById('citation-modal');

            if (!modalElement) {
                return;
            }

            // Function to initialize modal once Bootstrap is ready
            function initModal() {
                if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
                    // Bootstrap is ready - use the Modal API
                    if (!self.modal) {
                        try {
                            self.modal = new bootstrap.Modal(modalElement, {
                                keyboard: TRUE,
                                backdrop: TRUE
                            });
                        } catch (error) {
                            // Modal might already be initialized
                        }
                    }

                    // Show the modal
                    try {
                        self.modal.show();
                        // Initialize copy buttons and close functionality after modal is shown
                        setTimeout(() => {
                            self.initializeCopyButtons($(modalElement));
                            self.initializeCloseButton($(modalElement));
                        }, 100);
                    } catch (error) {
                        // Fall back to jQuery if Bootstrap API fails
                        self.showModalWithjQuery(modalElement);
                    }
                } else {
                    // Bootstrap not ready - wait and retry
                    if (!self._bootstrapCheckAttempts) {
                        self._bootstrapCheckAttempts = 0;
                    }

                    if (self._bootstrapCheckAttempts < 20) {
                        // Try again in 50ms (max 1 second total)
                        self._bootstrapCheckAttempts++;
                        setTimeout(initModal, 50);
                    } else {
                        // Fallback to jQuery after 1 second
                        self._bootstrapCheckAttempts = 0;
                        self.showModalWithjQuery(modalElement);
                    }
                }
            }

            // Start the initialization
            initModal();

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
         * Update citation content in the modal using template structure.
         *
         * @param {Object} citations
         *   The citation data.
         */
        updateCitationContent: function (citations) {
            // Store citation data for export functionality
            this.citationData = citations;
            this.currentFormat = 'apa'; // Default format

            // Use modern template structure
            const templateHtml = `
              < div class = "citation-formatter-modern" >
                < !-- Format selector tabs -- >
                < div class = "citation-format-selector" >
                  < button class = "citation-format-btn active" data - format = "apa" > APA 7th < / button >
                  < button class = "citation-format-btn" data - format = "harvard" > Harvard < / button >
                  < button class = "citation-format-btn" data - format = "oxford" > Oxford < / button >
                  < button class = "citation-format-btn" data - format = "mla" > MLA 9th < / button >
                  < button class = "citation-format-btn" data - format = "chicago" > Chicago < / button >
                < / div >

                < !-- Citation display area -- >
                < div class = "citation-content-wrapper" >
                  < div class = "citation-display" id = "citation-text" >
                    < div class = "citation-text-content" >
                      ${citations.apa || 'Citation not available'}
                    < / div >
                    < button class = "copy-citation-btn" id = "copy-btn" >
                      < svg class = "citation-icon" fill = "none" stroke = "currentColor" viewBox = "0 0 24 24" >
                        < path stroke - linecap = "round" stroke - linejoin = "round" stroke - width = "2" d = "M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" / >
                      < / svg >
                      < span > Copy < / span >
                    < / button >
                  < / div >
                < / div >

                < !-- Export section -- >
                < div class = "citation-export-section" >
                  < div class = "export-section-title" > Export for Reference Managers < / div >
                  < div class = "export-buttons" >
                    < button class = "export-btn" data - export = "bibtex" >
                      < svg class = "citation-icon" fill = "none" stroke = "currentColor" viewBox = "0 0 24 24" >
                        < path stroke - linecap = "round" stroke - linejoin = "round" stroke - width = "2" d = "M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" / >
                      < / svg >
                      BibTeX(.bib) {
                    < / button >
                    < button class = "export-btn" data - export = "ris" >
                      < svg class = "citation-icon" fill = "none" stroke = "currentColor" viewBox = "0 0 24 24" >
                        < path stroke - linecap = "round" stroke - linejoin = "round" stroke - width = "2" d = "M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" / >
                      < / svg >
                      RIS(.ris)
                    < / button >
                    < button class = "export-btn" data - export = "endnote" >
                      < svg class = "citation-icon" fill = "none" stroke = "currentColor" viewBox = "0 0 24 24" >
                        < path stroke - linecap = "round" stroke - linejoin = "round" stroke - width = "2" d = "M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" / >
                      < / svg >
                      EndNote
                    < / button >
                  < / div >
                < / div >

                < !-- Help link -- >
                < a href = "/content/referencing-resources-historical-research" class = "citation-help-link" >
                  Learn more about citation formats →
                < / a >
              < / div >
            `;
                  }

            // Replace the modal body content
            $('#citation-modal .citation-content').html(templateHtml);

            // Initialize all interactions
            const modalElement = document.getElementById('citation-modal');
            if (modalElement) {
                this.initializeFormatButtons($(modalElement));
                this.initializeModernCopyButton($(modalElement));
                this.initializeModernExportButtons($(modalElement));
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

            // Find existing close button and clean it up
            let $closeButton = $modal.find('.btn-close').first();

            // Clear any text content to let CSS handle the X
            if ($closeButton.length > 0) {
                $closeButton.empty();
            }

            // Explicitly set up the close button functionality
            $closeButton.off('click.citationClose').on('click.citationClose', function (e) {
                e.preventDefault();
                e.stopPropagation();
                Drupal.sahoCitation.hideModalWithjQuery($modal);
                return FALSE;
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
            $(document).off('keydown.citationModal');
            $modal.find('.copy-citation').off('click');
            $modal.find('.btn-copy-citation').off('click');

            // Additional event handler setup for all dismiss elements
            $modal.find('[data-bs-dismiss="modal"]').off('click.citationDismiss').on('click.citationDismiss', function (e) {
                e.preventDefault();
                e.stopPropagation();
                Drupal.sahoCitation.hideModalWithjQuery($modal);
                return FALSE;
            });

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

            // Initialize export buttons
            this.initializeExportButtons($modal);

            // Initialize close button functionality
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
            let $closeButton = $modal.find('.btn-close').first();

            // Only create if absolutely none exist
            if ($closeButton.length === 0) {
                $closeButton = $('<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>');
                $modal.find('.modal-header').append($closeButton);
            }

            // Remove any text content to prevent double X
            $closeButton.empty();

            // Set up click handler
            $closeButton.off('click.citationClose').on('click.citationClose', function (e) {
                e.preventDefault();
                e.stopPropagation();

                // Try Bootstrap API first if available
                if (self.modal && typeof self.modal.hide === 'function') {
                    self.modal.hide();
                } else {
                    // Fall back to jQuery
                    self.hideModalWithjQuery($modal);
                }
                return FALSE;
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
            $(document).off('keydown.citationModal');
            $modal.find('[data-bs-toggle="tab"]').off('click');

        },

        /**
         * Initialize format selector buttons.
         *
         * @param {jQuery} $modal
         *   The jQuery modal object.
         */
        initializeFormatButtons: function ($modal) {
            const self = this;

            $modal.find('.citation-format-btn').off('click').on('click', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const format = $btn.data('format');

                // Update active state
                $modal.find('.citation-format-btn').removeClass('active');
                $btn.addClass('active');

                // Update citation display
                self.currentFormat = format;
                let citationText = '';

                switch (format) {
                    case 'apa':
                        citationText = self.citationData.apa || 'APA citation not available';
                        break;

                    case 'harvard':
                        citationText = self.citationData.harvard || 'Harvard citation not available';
                        break;

                    case 'oxford':
                        citationText = self.citationData.oxford || 'Oxford citation not available';
                        break;

                    case 'mla':
                        citationText = self.generateMLACitation() || 'MLA citation coming soon';
                        break;

                    case 'chicago':
                        citationText = self.generateChicagoCitation() || 'Chicago citation coming soon';
                        break;

                    default:
                        citationText = 'Citation format not available';
                }

                $('#citation-text').html('<div class="citation-text-content">' + citationText + '</div>' + $('#copy-btn')[0].outerHTML);
            });
        },

        /**
         * Initialize modern copy button.
         *
         * @param {jQuery} $modal
         *   The jQuery modal object.
         */
        initializeModernCopyButton: function ($modal) {
            const self = this;

            $modal.on('click', '.copy-citation-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $btn = $(this);
                const $citationDisplay = $btn.closest('.citation-display');
                const $textContent = $citationDisplay.find('.citation-text-content');
                const citationText = $textContent.length ? $textContent.text().trim() : $citationDisplay.clone().find('.copy-citation-btn').remove().end().text().trim();

                if (citationText) {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(citationText).then(() => {
                            self.showModernFeedback($btn, 'Copied!', 'copied');
                        }).catch(() => {
                            self.fallbackCopy(citationText, $btn);
                        });
                    } else {
                        self.fallbackCopy(citationText, $btn);
                    }
                }
            });
        },

        /**
         * Initialize modern export buttons.
         *
         * @param {jQuery} $modal
         *   The jQuery modal object.
         */
        initializeModernExportButtons: function ($modal) {
            const self = this;

            $modal.find('.export-btn').off('click').on('click', function (e) {
                e.preventDefault();
                const $btn = $(this);
                const exportType = $btn.data('export');

                if (exportType === 'bibtex') {
                    self.exportCitation('bibtex');
                    self.showModernFeedback($btn, 'Exported!', 'exported');
                } else if (exportType === 'ris') {
                    self.exportCitation('ris');
                    self.showModernFeedback($btn, 'Exported!', 'exported');
                } else if (exportType === 'endnote') {
                    // EndNote uses RIS format
                    self.exportCitation('ris');
                    self.showModernFeedback($btn, 'Exported!', 'exported');
                }
            });
        },

        /**
         * Show modern feedback.
         *
         * @param {jQuery} $element
         *   The element to update.
         * @param {string} message
         *   The feedback message.
         * @param {string} className
         *   The CSS class to add.
         */
        showModernFeedback: function ($element, message, className) {
            const originalHtml = $element.html();
            const originalClass = $element.attr('class');

            $element.addClass(className);
            if (message === 'Copied!') {
                $element.html('<svg class="citation-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 100 4h2a2 2 0 100-4h-.5a1 1 0 000-2H9a2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/></svg><span>' + message + '</span>');
            }

            setTimeout(function () {
                $element.removeClass(className).attr('class', originalClass).html(originalHtml);
            }, 2000);
        },

        /**
         * Fallback copy method.
         *
         * @param {string} text
         *   The text to copy.
         * @param {jQuery} $btn
         *   The button element.
         */
        fallbackCopy: function (text, $btn) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
                this.showModernFeedback($btn, 'Copied!', 'copied');
            } catch (err) {
                this.showModernFeedback($btn, 'Failed', 'error');
            }

            document.body.removeChild(textarea);
        },

        /**
         * Generate MLA citation (placeholder).
         *
         * @return {string}
         *   The MLA citation.
         */
        generateMLACitation: function () {
            // This is a placeholder - implement MLA format if needed
            const nodeData = drupalSettings.sahoTools && drupalSettings.sahoTools.nodeData;
            if (nodeData) {
                const title = nodeData.title || document.title;
                const date = new Date();
                return '"' + title + '." <em>South African History Online</em>, ' + date.getDate() + ' ' +
                       ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][date.getMonth()] +
                       '. ' + date.getFullYear() + ', ' + window.location.href + '.';
            }
            return NULL;
        },

        /**
         * Generate Chicago citation (placeholder).
         *
         * @return {string}
         *   The Chicago citation.
         */
        generateChicagoCitation: function () {
            // This is a placeholder - implement Chicago format if needed
            const nodeData = drupalSettings.sahoTools && drupalSettings.sahoTools.nodeData;
            if (nodeData) {
                const title = nodeData.title || document.title;
                const date = new Date();
                return 'South African History Online. "' + title + '." Accessed ' +
                       ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'][date.getMonth()] +
                       ' ' + date.getDate() + ', ' + date.getFullYear() + '. ' + window.location.href + '.';
            }
            return NULL;
        },

        /**
         * Initialize export buttons in the modal.
         *
         * @param {jQuery} $modal
         *   The jQuery modal object.
         */
        initializeExportButtons: function ($modal) {
            const self = this;

            // Remove any existing click handlers first
            $modal.find('.export-bibtex, .export-ris').off('click');

            // Export button click handlers
            $modal.find('.export-bibtex, .export-ris').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $button = $(this);
                const format = $button.data('citation-format');

                if (format === 'bibtex' || format === 'ris') {
                    self.exportCitation(format);
                }

                return FALSE;
            });
        },

        /**
         * Export citation in the specified format.
         *
         * @param {string} format
         *   The export format ('bibtex' or 'ris').
         */
        exportCitation: function (format) {
            // Check if we have citation data
            if (!this.citationData) {
                return;
            }

            // Generate BibTeX or RIS from node data if not already available
            let citationContent;
            let fileName;
            let mimeType;

            const nodeData = drupalSettings.sahoTools && drupalSettings.sahoTools.nodeData;
            const pageTitle = (nodeData && nodeData.title) || document.title;

            // Generate a file-safe slug from the title
            const slug = pageTitle
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '')
                .substring(0, 50);

            if (format === 'bibtex') {
                // Check if BibTeX was returned from the server
                if (this.citationData.bibtex) {
                    citationContent = this.citationData.bibtex;
                } else {
                    // Generate BibTeX format locally as fallback
                    citationContent = this.generateBibTeXLocally(nodeData);
                }
                fileName = slug + '.bib';
                mimeType = 'application/x-bibtex';
            } else if (format === 'ris') {
                // Check if RIS was returned from the server
                if (this.citationData.ris) {
                    citationContent = this.citationData.ris;
                } else {
                    // Generate RIS format locally as fallback
                    citationContent = this.generateRISLocally(nodeData);
                }
                fileName = slug + '.ris';
                mimeType = 'application/x-research-info-systems';
            }

            // Create a Blob and download the file
            const blob = new Blob([citationContent], { type: mimeType + ';charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = fileName;

            // Trigger download
            document.body.appendChild(link);
            link.click();

            // Clean up
            setTimeout(function () {
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }, 100);

            // Show feedback
            this.showExportFeedback(format);
        },

        /**
         * Generate BibTeX format locally.
         *
         * @param {Object} nodeData
         *   The node data.
         *
         * @return {string}
         *   The BibTeX citation.
         */
        generateBibTeXLocally: function (nodeData) {
            const title = (nodeData && nodeData.title) || document.title;
            const url = window.location.href;
            const year = new Date().getFullYear();

            // Generate a cite key
            const citeKey = title
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '')
                .substring(0, 20) + year;

            let bibtex = '@online{' + citeKey + ',\n';
            bibtex += '  author = {{South African History Online (SAHO)}},\n';
            bibtex += '  title = {{' + title + '}},\n';
            bibtex += '  year = {' + year + '},\n';
            bibtex += '  url = {' + url + '},\n';
            bibtex += '  urldate = {' + new Date().toISOString().split('T')[0] + '},\n';
            bibtex += '  publisher = {{South African History Online}}\n';
            bibtex += '}';

            return bibtex;
        },

        /**
         * Generate RIS format locally.
         *
         * @param {Object} nodeData
         *   The node data.
         *
         * @return {string}
         *   The RIS citation.
         */
        generateRISLocally: function (nodeData) {
            const title = (nodeData && nodeData.title) || document.title;
            const url = window.location.href;
            const currentDate = new Date();
            const year = currentDate.getFullYear();
            const formattedDate = currentDate.toISOString().split('T')[0].replace(/-/g, '/');

            let ris = 'TY  - ELEC\n';
            ris += 'AU  - South African History Online (SAHO)\n';
            ris += 'TI  - ' + title + '\n';
            ris += 'PY  - ' + year + '\n';
            ris += 'DA  - ' + formattedDate + '\n';
            ris += 'UR  - ' + url + '\n';
            ris += 'Y2  - ' + formattedDate + '\n';
            ris += 'PB  - South African History Online\n';
            ris += 'ER  - \n';

            return ris;
        },

        /**
         * Show feedback after export.
         *
         * @param {string} format
         *   The export format.
         */
        showExportFeedback: function (format) {
            const formatName = format === 'bibtex' ? 'BibTeX' : 'RIS';
            const $buttons = $('.export-' + format);
            const originalHtml = $buttons.html();

            // Update button with success feedback
            $buttons.html(
                '<i class="bi bi-check-circle text-success"></i> ' + formatName + ' Exported!'
            );

            // Restore original button after 2 seconds
            setTimeout(function () {
                $buttons.html(originalHtml);
            }, 2000);
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

            // Individual copy buttons - updated to work with actual HTML structure
            $modal.find('.copy-individual').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                // Find the citation text in the same container
                const $button = $(this);
                const $citationContainer = $button.closest('.citation-content');

                // Get all text except the button text by cloning and removing button
                const $clone = $citationContainer.clone();
                $clone.find('button').remove();
                const citationText = $clone.text().trim();

                if (citationText) {
                    // Try modern clipboard API first
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(citationText).then(() => {
                            self.showIndividualCopyFeedback($button, 'Copied!');
                        }).catch(() => {
                            // Fall back to execCommand
                            self.fallbackIndividualCopy(citationText, $button);
                        });
                    } else {
                        // Fall back to execCommand
                        self.fallbackIndividualCopy(citationText, $button);
                    }
                } else {
                    self.showIndividualCopyFeedback($button, 'Error: No text found', TRUE);
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
