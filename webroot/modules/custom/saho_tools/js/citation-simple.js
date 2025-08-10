/**
 * Modern Citation and Sharing Handler - Works with existing modals
 */
(function ($, Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.sahoCitationSimple = {
    attach: function (context) {
      // Handle citation button clicks
      const citationButtons = once('citation-trigger', '.cite-button, [data-citation-trigger]', context);
      citationButtons.forEach(function (button) {
        button.addEventListener('click', function (e) {
          e.preventDefault();
          openCitationModal();
        });
      });

      // Handle sharing button clicks
      const sharingButtons = once('sharing-trigger', '.share-button, [data-sharing-trigger]', context);
      sharingButtons.forEach(function (button) {
        button.addEventListener('click', function (e) {
          e.preventDefault();
          openSharingModal();
        });
      });

      // Setup copy buttons for existing modals on page load
      setupCitationCopyButtons();
      setupSharingCopyButton();
    }
  };

  function openCitationModal() {
    const modal = document.getElementById('citation-modal');
    if (!modal) {
      return;
    }

    // Generate citations immediately
    generateCitations();

    // Show modal using Bootstrap if available, fallback to custom method
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();
    } else {
      // Custom modal show
      showModal(modal);
    }

    // Setup copy buttons
    setupCitationCopyButtons();
  }

  function openSharingModal() {
    const modal = document.getElementById('sharing-modal');
    if (!modal) {
      return;
    }

    // Show modal using Bootstrap if available, fallback to custom method
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();
    } else {
      // Custom modal show
      showModal(modal);
    }

    // Setup copy button for URL
    setupSharingCopyButton();
  }

  function showModal(modal) {
    // Add Bootstrap classes for styling
    modal.classList.add('show');
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');

    // Add backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.id = modal.id + '-backdrop';
    document.body.appendChild(backdrop);

    // Prevent body scroll
    document.body.classList.add('modal-open');

    // Focus first focusable element
    const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (firstFocusable) {
      firstFocusable.focus();
    }

    // Setup close handlers
    setupModalCloseHandlers(modal, backdrop);
  }

  function closeModal(modal, backdrop) {
    // Hide modal
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');

    // Remove backdrop
    if (backdrop) {
      backdrop.remove();
    }

    // Restore body scroll
    document.body.classList.remove('modal-open');
  }

  function setupModalCloseHandlers(modal, backdrop) {
    // Close button
    const closeBtn = modal.querySelector('.btn-close, [data-bs-dismiss="modal"]');
    if (closeBtn) {
      closeBtn.onclick = () => closeModal(modal, backdrop);
    }

    // Backdrop click
    if (backdrop) {
      backdrop.onclick = () => closeModal(modal, backdrop);
    }

    // Escape key
    const escapeHandler = (e) => {
      if (e.key === 'Escape') {
        closeModal(modal, backdrop);
        document.removeEventListener('keydown', escapeHandler);
      }
    };
    document.addEventListener('keydown', escapeHandler);
  }

  function generateCitations() {
    const title = document.title || 'Untitled Page';
    const url = window.location.href;
    const today = new Date();
    const date = today.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });

    // Get page data if available
    const nodeData = drupalSettings.sahoTools ? .nodeData;
    const pageTitle = nodeData ? .title || title;

    // Generate citations
    const apa = `South African History Online. (${today.getFullYear()}). <em > ${pageTitle} < / em > . Retrieved ${date}, from ${url}`;
    const harvard = `South African History Online ${today.getFullYear()}, < em > ${pageTitle} < / em > , viewed ${date}, < ${url} > `;
    const oxford = `South African History Online, "${pageTitle}", ${date}, accessed ${date}, ${url}.`;

    // Update content in existing modal structure
    const modal = document.getElementById('citation-modal');
    if (modal) {
      // Try to find citation content areas in the existing modal
      const apaContent = modal.querySelector('.apa-citation .citation-content, .apa-citation');
      const harvardContent = modal.querySelector('.harvard-citation .citation-content, .harvard-citation');
      const oxfordContent = modal.querySelector('.oxford-citation .citation-content, .oxford-citation');

      if (apaContent) { apaContent.innerHTML = apa;
      }
      if (harvardContent) { harvardContent.innerHTML = harvard;
      }
      if (oxfordContent) { oxfordContent.innerHTML = oxford;
      }

    }
  }

  function setupCitationCopyButtons() {
    const modal = document.getElementById('citation-modal');
    if (!modal) { return;
    }

    // Individual copy buttons
    modal.querySelectorAll('.copy-individual, [data-format]').forEach(btn => {
      btn.onclick = function (e) {
        e.preventDefault();
        const format = this.dataset.format || this.textContent.toLowerCase().split(' ')[1]; // Extract format from button text
        let text = '';

        // Try to find the citation text
        const citationElement = modal.querySelector(`.${format} - citation .citation - content, .${format} - citation`);
        if (citationElement) {
          text = citationElement.textContent || citationElement.innerText;
        }

        if (text) {
          copyToClipboard(text, this);
        }
      };
    });

  }

  function setupSharingCopyButton() {
    const modal = document.getElementById('sharing-modal');
    if (!modal) { return;
    }

    const copyBtn = modal.querySelector('.url-copy-btn, .copy-url-btn');
    if (copyBtn) {
      copyBtn.onclick = function (e) {
        e.preventDefault();
        const urlInput = modal.querySelector('#page-url-input, .url-input');
        if (urlInput) {
          copyToClipboard(urlInput.value || window.location.href, this);
        }
      };
    }
  }

  function copyToClipboard(text, button) {
    const originalText = button.textContent;

    // Modern clipboard API with fallback
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(() => {
        showCopySuccess(button, originalText);
      }).catch(() => {
        fallbackCopy(text, button, originalText);
      });
    } else {
      fallbackCopy(text, button, originalText);
    }
  }

  function fallbackCopy(text, button, originalText) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    try {
      document.execCommand('copy');
      showCopySuccess(button, originalText);
    } catch (err) {
      button.textContent = 'Copy Failed';
      setTimeout(() => button.textContent = originalText, 2000);
    }

    document.body.removeChild(textarea);
  }

  function showCopySuccess(button, originalText) {
    button.textContent = 'Copied!';
    button.classList.add('copied');
    setTimeout(() => {
      button.textContent = originalText;
      button.classList.remove('copied');
    }, 2000);
  }

})(jQuery, Drupal, drupalSettings, once);