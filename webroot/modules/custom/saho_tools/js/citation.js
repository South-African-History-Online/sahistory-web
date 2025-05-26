/**
 * @file
 * JavaScript for the citation functionality.
 */
(function ($, Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Citation behavior.
   */
  Drupal.behaviors.sahoCitation = {
    attach: function (context, settings) {
      console.log('SAHO Citation behavior attached');
      
      // Check if the library is loaded correctly
      if (drupalSettings.sahoTools && drupalSettings.sahoTools.debug) {
        console.log('Citation library loaded:', drupalSettings.sahoTools.debug);
      } else {
        console.warn('Citation library debug information not found in drupalSettings');
      }
      
      // Check if Bootstrap is available
      if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
        console.log('Bootstrap Modal is available');
      } else {
        console.error('Bootstrap Modal is not available! This will prevent the citation modal from working.');
        console.log('Bootstrap object:', typeof bootstrap !== 'undefined' ? bootstrap : 'undefined');
      }
      
      // Target both links and buttons with data-citation-trigger attribute or href="#cite"
      const citeLinks = document.querySelectorAll('a[data-citation-trigger], a[href="#cite"], button[data-citation-trigger]');
      console.log('Found cite elements:', citeLinks.length);
      
      if (citeLinks.length === 0) {
        console.warn('No citation triggers found on page. Looking for elements with these selectors:');
        console.warn('- a[data-citation-trigger]');
        console.warn('- a[href="#cite"]');
        console.warn('- button[data-citation-trigger]');
      }
      
      once('sahoCitation', 'a[data-citation-trigger], a[href="#cite"], button[data-citation-trigger]', context).forEach(function (element) {
        console.log('Attaching click handler to:', element);
        
        // Update the element to use our citation functionality
        $(element).on('click', function (e) {
          console.log('Citation element clicked');
          e.preventDefault();
          Drupal.sahoCitation.openCitationModal();
        });
      });

      // Initialize the copy citation button
      once('sahoCitationCopy', '.copy-citation', context).forEach(function (element) {
        $(element).on('click', function (e) {
          e.preventDefault();
          Drupal.sahoCitation.copyCitation();
        });
      });
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
      console.log('Opening citation modal');
      
      // Check if the modal element exists
      const modalElement = document.getElementById('citation-modal');
      if (!modalElement) {
        console.error('Citation modal element not found! Make sure the HTML is added to the page.');
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

      // Get the node data from drupalSettings
      const nodeData = drupalSettings.sahoTools && drupalSettings.sahoTools.nodeData;
      const pageData = drupalSettings.sahoTools && drupalSettings.sahoTools.pageData;
      
      console.log('Node data:', nodeData);
      console.log('Page data:', pageData);
      
      if (nodeData) {
        // If we have node data, try to load citation data from the API
        // But also generate a basic citation as a fallback
        this.loadCitationData(nodeData.nid);
        
        // Generate a basic citation from node data as a fallback
        this.generateBasicCitationFromNodeData(nodeData);
      } 
      else if (pageData) {
        // For non-node pages, generate basic citation
        this.generateBasicCitation(pageData);
      }
      else {
        // Fallback for when no data is available
        console.error('No page data available for citation.');
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
      console.log('Generating basic citation from node data as fallback');
      
      // Always use South African History Online (SAHO) as the author
      const author = 'South African History Online (SAHO)';
      
      // Format dates
      const currentDate = new Date();
      const accessDate = currentDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
      
      // Try to get creation date from nodeData
      let creationDate = accessDate;
      let creationYear = currentDate.getFullYear();
      
      if (nodeData.created) {
        const nodeCreated = new Date(nodeData.created * 1000);
        creationDate = nodeCreated.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
        creationYear = nodeCreated.getFullYear();
      }
      
      // Get page URL
      const pageUrl = window.location.href;
      
      // Get page title
      const pageTitle = nodeData.title || document.title;
      
      // Generate Harvard citation - without year after author
      const harvardCitation = author + ' <em>' + 
        pageTitle + '</em>' + '. Available at: ' + pageUrl + ' (Accessed: ' + accessDate + ').';
      
      // Generate APA citation
      const apaCitation = author + '. (' + creationDate + '). <em>' + 
        pageTitle + '</em>. ' + pageUrl;
      
      // Generate Chicago citation
      const chicagoCitation = author + '. "' + pageTitle + '." ' + 
        creationDate + '. Accessed ' + accessDate + '. ' + pageUrl + '.';
      
      // Update citation content
      this.updateCitationContent({
        harvard: harvardCitation,
        apa: apaCitation,
        chicago: chicagoCitation
      });
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
      const formattedDate = currentDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
      
      // Generate Harvard citation - without year after author
      const harvardCitation = author + ' <em>' + 
        pageData.title + '</em>' + '. Available at: ' + pageData.url + ' (Accessed: ' + formattedDate + ').';
      
      // Generate APA citation
      const apaCitation = author + '. (' + formattedDate + '). <em>' + 
        pageData.title + '</em>. ' + pageData.url;
      
      // Generate Chicago citation
      const chicagoCitation = author + '. "' + pageData.title + '." ' + 
        formattedDate + '. Accessed ' + formattedDate + '. ' + pageData.url + '.';
      
      // Update citation content
      this.updateCitationContent({
        harvard: harvardCitation,
        apa: apaCitation,
        chicago: chicagoCitation
      });
    },

    /**
     * Load citation data from the server.
     *
     * @param {number} nid
     *   The node ID.
     */
    loadCitationData: function (nid) {
      const self = this;
      
      // Show loading state
      $('.citation-content').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
      
      // Log the URL we're requesting
      const apiUrl = Drupal.url('api/citation/' + nid);
      console.log('Requesting citation data from:', apiUrl);
      
      // Make an AJAX request to get the citation data
      $.ajax({
        url: apiUrl,
        type: 'GET',
        dataType: 'json',
        success: function (response) {
          console.log('Citation data response:', response);
          if (response && response.citations) {
            // Update the citation content
            self.updateCitationContent(response.citations);
            
            // Check if we have image data and update the modal
            if (response.image_info && response.image_info.has_image) {
              self.addImageToModal(response.image_info);
            }
          }
          else {
            console.error('Invalid response format:', response);
            // Show error message
            $('.citation-content').html('<div class="alert alert-danger">Failed to load citation data. Invalid response format.</div>');
          }
        },
        error: function (xhr, status, error) {
          console.error('Error loading citation data:', error);
          console.error('Status:', status);
          console.error('Response:', xhr.responseText);
          
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
      });
    },
    
    /**
     * Update citation content in the modal.
     *
     * @param {Object} citations
     *   The citation data.
     */
    updateCitationContent: function (citations) {
      // Update each citation format
      $('.harvard-citation').html(citations.harvard);
      $('.apa-citation').html(citations.apa);
      $('.chicago-citation').html(citations.chicago);
      
      // Add copy buttons for each citation
      $('.citation-content').each(function() {
        const $container = $(this);
        if (!$container.find('.copy-individual').length) {
          $container.append('<button class="btn btn-sm btn-outline-secondary copy-individual mt-2">Copy this format</button>');
        }
      });
      
      // Add click handler for individual copy buttons
      once('citationCopyIndividual', '.copy-individual', document).forEach(function(button) {
        $(button).on('click', function(e) {
          e.preventDefault();
          const citationText = $(this).parent().clone().children('button').remove().end().text().trim();
          Drupal.sahoCitation.copyTextToClipboard(citationText, $(this).parent());
          
          // Update button text temporarily
          const $button = $(this);
          const originalText = $button.text();
          $button.text('Copied!');
          setTimeout(function() {
            $button.text(originalText);
          }, 1500);
        });
      });
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
      const $image = $('<img>', {
        src: imageInfo.image_url,
        alt: imageInfo.image_alt || 'Image for citation',
        class: 'img-fluid rounded mb-2'
      });
      
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
     */
    copyTextToClipboard: function (text, $element) {
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
        setTimeout(function () {
          $element.removeClass('copying');
        }, 1500);
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
        $closeButton.css({
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
        }).html('&times;');
      }
      
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
      
      // Handle close button clicks
      $modal.find('[data-bs-dismiss="modal"]').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Close button clicked');
        Drupal.sahoCitation.hideModalWithjQuery($modal);
        return false;
      });
      
      // Handle ESC key
      $(document).on('keydown.citationModal', function(e) {
        if (e.key === 'Escape') {
          console.log('ESC key pressed');
          Drupal.sahoCitation.hideModalWithjQuery($modal);
        }
      });
      
      // Handle backdrop clicks
      $('.modal-backdrop').on('click', function() {
        console.log('Backdrop clicked');
        Drupal.sahoCitation.hideModalWithjQuery($modal);
      });
      
      // Handle tab switching without Bootstrap
      $modal.find('[data-bs-toggle="tab"]').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $this = $(this);
        const target = $this.attr('data-bs-target') || $this.attr('href');
        console.log('Tab clicked:', target);
        
        // Remove active class from all tabs and tab panes
        $modal.find('.nav-link').removeClass('active');
        $modal.find('.tab-pane').removeClass('show active');
        
        // Add active class to clicked tab and its target pane
        $this.addClass('active');
        $(target).addClass('show active');
        
        return false;
      });
      
      // Also handle regular tab links that might not have data-bs-toggle
      $modal.find('.nav-tabs a').off('click').on('click', function(e) {
        if (!$(this).attr('data-bs-toggle')) {
          e.preventDefault();
          e.stopPropagation();
          const $this = $(this);
          const target = $this.attr('href');
          console.log('Tab link clicked:', target);
          
          // Remove active class from all tabs and tab panes
          $modal.find('.nav-link').removeClass('active');
          $modal.find('.tab-pane').removeClass('show active');
          
          // Add active class to clicked tab and its target pane
          $this.addClass('active');
          $(target).addClass('show active');
          
          return false;
        }
      });
      
      // Initialize copy buttons
      this.initializeCopyButtons($modal);
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
      
      // Main copy citation button - make it more prominent
      const $copyButton = $modal.find('.copy-citation');
      $copyButton.off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Main copy button clicked');
        self.copyCitation();
        return false;
      });
      
      // Make the copy button more prominent
      $copyButton.addClass('btn-primary').removeClass('btn-secondary btn-outline-secondary')
        .css({
          'font-weight': 'bold',
          'font-size': '1.1rem',
          'padding': '0.5rem 1rem',
          'margin-top': '1rem',
          'width': 'auto',
          'display': 'block'
        })
        .html('<i class="fas fa-copy"></i> Copy Citation');
      
      // If Font Awesome isn't available, use a simple text
      if ($copyButton.find('i').length === 0) {
        $copyButton.text('📋 Copy Citation');
      }
      
      // Individual format copy buttons
      $modal.find('.btn-copy-citation').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Individual copy button clicked');
        const format = $(this).data('format');
        const $citationElement = $modal.find('.' + format + '-citation');
        const citationText = $citationElement.clone().children('button').remove().end().text().trim();
        
        // Copy the text and show feedback
        self.copyTextToClipboard(citationText, $citationElement);
        
        // Update the button text temporarily
        const $button = $(this);
        const originalText = $button.text();
        $button.text('Copied!');
        setTimeout(function () {
          $button.text(originalText);
        }, 1500);
        
        return false;
      });
    },

    /**
     * Copy the active citation to the clipboard.
     */
    copyCitation: function () {
      // Since all citations are now visible, we'll copy all of them
      let allCitationsText = '';
      
      // Get all citation content
      const formats = ['apa', 'chicago', 'harvard'];
      const formatLabels = {
        'apa': 'APA (7th edition)',
        'chicago': 'Oxford (Footnote style)',
        'harvard': 'Harvard'
      };
      
      formats.forEach(function(format) {
        const $citationElement = $('.' + format + '-citation');
        const citationText = $citationElement.clone().children('button').remove().end().text().trim();
        
        if (citationText) {
          allCitationsText += formatLabels[format] + ':\n' + citationText + '\n\n';
        }
      });
      
      // Trim the extra newlines at the end
      allCitationsText = allCitationsText.trim();
      
      // Copy the text and show feedback
      this.copyTextToClipboard(allCitationsText, $('.citation-formats'));
      
      // Update the button text temporarily
      const $button = $('.copy-citation');
      const originalText = $button.text();
      $button.text('All Formats Copied!');
      setTimeout(function () {
        $button.text(originalText);
      }, 1500);
      
      console.log('All citations copied:', allCitationsText);
    }
  };

})(jQuery, Drupal, drupalSettings, once);