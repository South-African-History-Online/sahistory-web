// * Bootstrap libraries
import './_bootstrap';

// * Any other global site-wide JavaScript should be placed below.
document.addEventListener('DOMContentLoaded', () => {
  // Add Better Social Sharing Buttons to blockquotes
  const blockquotes = document.querySelectorAll('blockquote');
  blockquotes.forEach((blockquote, index) => {
    // Get the quote text
    const quoteText = blockquote.textContent.trim();

    // Create a unique ID for this blockquote
    const blockquoteId = `blockquote-${index}`;
    blockquote.id = blockquoteId;

    // Create the sharing container
    const sharingContainer = document.createElement('div');
    sharingContainer.className = 'blockquote-sharing';

    // Add the sharing text
    const sharingText = document.createElement('div');
    sharingText.className = 'blockquote-sharing-text';
    sharingText.textContent = 'Share this quote:';
    sharingContainer.appendChild(sharingText);

    // Create the buttons container
    const buttonsContainer = document.createElement('div');
    buttonsContainer.className = 'blockquote-sharing-buttons better-social-sharing-buttons';

    // Get the current page URL and quote text
    const pageUrl = window.location.href;
    const shortQuote = quoteText.substring(0, 280);

    // Add data attributes for Better Social Sharing Buttons
    buttonsContainer.setAttribute('data-share-url', pageUrl);
    buttonsContainer.setAttribute('data-share-title', shortQuote);
    buttonsContainer.setAttribute('data-share-description', shortQuote);

    // Add modern, responsive sharing buttons
    buttonsContainer.innerHTML = `
      <div class="social-sharing-buttons blockquote-buttons">
        <!-- Facebook -->
        <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(pageUrl)}&quote=${encodeURIComponent(shortQuote)}" 
           target="_blank" rel="noopener noreferrer" 
           class="social-sharing-buttons-button share-facebook" 
           title="Share to Facebook" aria-label="Share to Facebook">
          <svg class="social-sharing-buttons-icon" width="16" height="16">
            <use href="/modules/contrib/better_social_sharing_buttons/assets/dist/sprites/social-icons--no-color.svg#facebook"></use>
          </svg>
        </a>
        
        <!-- Twitter/X -->
        <a href="https://twitter.com/intent/tweet?text=${encodeURIComponent(shortQuote)}&url=${encodeURIComponent(pageUrl)}" 
           target="_blank" rel="noopener noreferrer" 
           class="social-sharing-buttons-button share-x" 
           title="Share to X" aria-label="Share to X">
          <svg class="social-sharing-buttons-icon" width="16" height="16">
            <use href="/modules/contrib/better_social_sharing_buttons/assets/dist/sprites/social-icons--no-color.svg#x"></use>
          </svg>
        </a>
        
        <!-- LinkedIn -->
        <a href="https://www.linkedin.com/shareArticle?mini=true&url=${encodeURIComponent(pageUrl)}&summary=${encodeURIComponent(shortQuote)}" 
           target="_blank" rel="noopener noreferrer" 
           class="social-sharing-buttons-button share-linkedin" 
           title="Share to LinkedIn" aria-label="Share to LinkedIn">
          <svg class="social-sharing-buttons-icon" width="16" height="16">
            <use href="/modules/contrib/better_social_sharing_buttons/assets/dist/sprites/social-icons--no-color.svg#linkedin"></use>
          </svg>
        </a>
        
        <!-- Email -->
        <a href="mailto:?subject=Quote from SAHO&body=${encodeURIComponent(shortQuote)}%0A%0ARead more at: ${encodeURIComponent(pageUrl)}" 
           class="social-sharing-buttons-button share-email" 
           title="Share via Email" aria-label="Share via Email">
          <svg class="social-sharing-buttons-icon" width="16" height="16">
            <use href="/modules/contrib/better_social_sharing_buttons/assets/dist/sprites/social-icons--no-color.svg#email"></use>
          </svg>
        </a>
        
        <!-- Copy -->
        <a href="#" class="social-sharing-buttons-button share-copy btn-copy" 
           title="Copy link" aria-label="Copy link" data-quote="${blockquoteId}">
          <svg class="social-sharing-buttons-icon" width="16" height="16">
            <use href="/modules/contrib/better_social_sharing_buttons/assets/dist/sprites/social-icons--no-color.svg#copy"></use>
          </svg>
        </a>
      </div>
    `;

    // Append the sharing container to the blockquote
    sharingContainer.appendChild(buttonsContainer);
    blockquote.appendChild(sharingContainer);
  });

  // Add click handler for copy buttons
  document.addEventListener('click', (e) => {
    if (e.target.closest('.btn-copy')) {
      e.preventDefault();
      const button = e.target.closest('.btn-copy');
      const blockquoteId = button.getAttribute('data-quote');
      const blockquote = document.getElementById(blockquoteId);

      if (blockquote) {
        // Get the quote text (excluding the sharing buttons we added)
        const quoteText = blockquote.childNodes[0].textContent.trim();

        // Create a temporary textarea to copy from
        const textarea = document.createElement('textarea');
        textarea.value = quoteText;
        document.body.appendChild(textarea);
        textarea.select();

        try {
          // Copy the text to clipboard
          document.execCommand('copy');

          // Visual feedback
          button.classList.add('copied');
          setTimeout(() => {
            button.classList.remove('copied');
          }, 2000);
        } catch (_err) {
          // Failed to copy quote
        }

        document.body.removeChild(textarea);
      }
    }
  });

  // Enhanced mobile menu functionality with improved positioning
  const mobileToggle = document.querySelector('.saho-mobile-toggle');
  const mobileMenu = document.getElementById('sahoMobileMenu');

  if (mobileToggle && mobileMenu) {
    // Create offcanvas instance with explicit configuration
    const offcanvasInstance = new bootstrap.Offcanvas(mobileMenu, {
      backdrop: true,
      keyboard: true,
      scroll: false,
    });

    // Add body class when menu is shown to prevent scrolling
    mobileMenu.addEventListener('shown.bs.offcanvas', () => {
      document.body.classList.add('offcanvas-open');
    });

    // Remove body class when menu is hidden
    mobileMenu.addEventListener('hidden.bs.offcanvas', () => {
      document.body.classList.remove('offcanvas-open');
    });

    // Ensure toggle button works properly
    mobileToggle.addEventListener('click', (e) => {
      e.preventDefault();
      offcanvasInstance.show();
    });

    // Close button inside mobile menu
    const closeButton = mobileMenu.querySelector('.btn-close');
    if (closeButton) {
      closeButton.addEventListener('click', () => {
        offcanvasInstance.hide();
      });
    }

    // Close menu when clicking outside (backup for backdrop click)
    document.addEventListener('click', (e) => {
      if (
        mobileMenu.classList.contains('show') &&
        !mobileMenu.contains(e.target) &&
        e.target !== mobileToggle &&
        !mobileToggle.contains(e.target)
      ) {
        offcanvasInstance.hide();
      }
    });

    // Fix for iOS devices where the menu might go off-screen
    function fixIOSViewport() {
      if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
        const viewportHeight = window.innerHeight;
        mobileMenu.style.height = `${viewportHeight}px`;
      }
    }

    // Call on load and resize
    fixIOSViewport();
    window.addEventListener('resize', fixIOSViewport);
  }

  // Initialize all other offcanvas elements
  const offcanvasElementList = [].slice.call(
    document.querySelectorAll('.offcanvas:not(#sahoMobileMenu)')
  );
  const _offcanvasList = offcanvasElementList.map(
    (offcanvasEl) => new bootstrap.Offcanvas(offcanvasEl)
  );

  // Initialize Bootstrap dropdowns with proper configuration
  const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
  const _dropdownList = dropdownElementList.map(
    (dropdownToggleEl) =>
      new bootstrap.Dropdown(dropdownToggleEl, {
        autoClose: 'outside',
      })
  );

  // Citation and Share Modal Functionality

  // Initialize cite modal when shown
  const citeModal = document.getElementById('citeModal');
  if (citeModal) {
    citeModal.addEventListener('shown.bs.modal', () => {
      generateCitations();
    });
  }

  // Initialize share modal when shown
  const shareModal = document.getElementById('shareModal');
  if (shareModal) {
    shareModal.addEventListener('shown.bs.modal', () => {
      initializeShareModal();
    });
  }

  // Generate citations function
  function generateCitations() {
    const pageTitle = document.title;
    const pageUrl = window.location.href;
    const siteName = 'South African History Online';
    const currentDate = new Date().toLocaleDateString();
    const accessDate = new Date().toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });

    // MLA Format
    const mlaCitation = `"${pageTitle}." ${siteName}, ${currentDate}, ${pageUrl}.`;
    document.getElementById('citeMLA').textContent = mlaCitation;

    // APA Format
    const apaCitation = `${pageTitle}. (${new Date().getFullYear()}). ${siteName}. Retrieved ${accessDate}, from ${pageUrl}`;
    document.getElementById('citeAPA').textContent = apaCitation;

    // Chicago Format
    const chicagoCitation = `"${pageTitle}." ${siteName}. Accessed ${accessDate}. ${pageUrl}.`;
    document.getElementById('citeChicago').textContent = chicagoCitation;
  }

  // Initialize share modal
  function initializeShareModal() {
    const pageUrl = window.location.href;
    const pageTitle = document.title;

    // Set the URL input
    document.getElementById('shareUrl').value = pageUrl;

    // Configure social media links
    const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(pageUrl)}`;
    const twitterUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(pageTitle)}&url=${encodeURIComponent(pageUrl)}`;
    const linkedInUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(pageUrl)}`;
    const emailUrl = `mailto:?subject=${encodeURIComponent(pageTitle)}&body=${encodeURIComponent(`${pageTitle} - ${pageUrl}`)}`;

    document.getElementById('shareFacebook').href = facebookUrl;
    document.getElementById('shareTwitter').href = twitterUrl;
    document.getElementById('shareLinkedIn').href = linkedInUrl;
    document.getElementById('shareEmail').href = emailUrl;
  }

  // Global functions for copy functionality
  window.copyCitation = (elementId) => {
    const element = document.getElementById(elementId);
    const text = element.textContent;

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(() => {
        showCopyFeedback(elementId);
      });
    } else {
      // Fallback for older browsers
      const textarea = document.createElement('textarea');
      textarea.value = text;
      document.body.appendChild(textarea);
      textarea.select();
      try {
        document.execCommand('copy');
        showCopyFeedback(elementId);
      } catch (_err) {
        console.error('Failed to copy citation');
      }
      document.body.removeChild(textarea);
    }
  };

  window.copyShareUrl = () => {
    const shareUrlInput = document.getElementById('shareUrl');
    const copyButton = shareUrlInput.nextElementSibling;

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(shareUrlInput.value).then(() => {
        const originalText = copyButton.textContent;
        copyButton.textContent = 'Copied!';
        copyButton.classList.remove('btn-outline-primary');
        copyButton.classList.add('btn-success');

        setTimeout(() => {
          copyButton.textContent = originalText;
          copyButton.classList.remove('btn-success');
          copyButton.classList.add('btn-outline-primary');
        }, 2000);
      });
    } else {
      // Fallback for older browsers
      shareUrlInput.select();
      shareUrlInput.setSelectionRange(0, 99999);
      try {
        document.execCommand('copy');
        const originalText = copyButton.textContent;
        copyButton.textContent = 'Copied!';
        copyButton.classList.remove('btn-outline-primary');
        copyButton.classList.add('btn-success');

        setTimeout(() => {
          copyButton.textContent = originalText;
          copyButton.classList.remove('btn-success');
          copyButton.classList.add('btn-outline-primary');
        }, 2000);
      } catch (_err) {
        console.error('Failed to copy URL');
      }
    }
  };

  function showCopyFeedback(elementId) {
    const button = document.querySelector(`#${elementId} + .btn`);
    if (button) {
      const originalText = button.textContent;
      button.textContent = 'Copied!';
      button.classList.remove('btn-outline-primary');
      button.classList.add('btn-success');

      setTimeout(() => {
        button.textContent = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-primary');
      }, 2000);
    }
  }

  // Initialize tooltips if needed
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  const _tooltipList = tooltipTriggerList.map(
    (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
  );

  // Initialize popovers if needed
  const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
  const _popoverList = popoverTriggerList.map(
    (popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl)
  );

  // WebP conversion for existing images
  function convertImagesToWebP() {
    // Check if browser supports WebP
    function supportsWebP() {
      return new Promise((resolve) => {
        const webP = new Image();
        webP.onload = webP.onerror = () => {
          resolve(webP.height === 2);
        };
        webP.src =
          'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
      });
    }

    // Convert image to WebP if available
    function tryWebPConversion(img) {
      if (!img.src || img.getAttribute('data-webp-converted')) {
        return;
      }

      const originalSrc = img.src;

      // Only convert images from our own domain and common formats
      if (!originalSrc.includes(window.location.hostname) && !originalSrc.startsWith('/')) {
        return;
      }

      // Check if it's a convertible image format
      if (!/\.(jpe?g|png)(\?.*)?$/i.test(originalSrc)) {
        return;
      }

      // Try to find a WebP equivalent by replacing extension
      let webpSrc = originalSrc;

      // For direct file URLs, try adding .webp
      if (originalSrc.includes('/sites/default/files/')) {
        // Try converting: image.jpg -> image.jpg.webp
        webpSrc = originalSrc.replace(/\.(jpe?g|png)(\?.*)?$/i, '.webp$2');

        // Create a test image to see if WebP version exists
        const testImg = new Image();
        testImg.onload = () => {
          // WebP version exists, create picture element
          const picture = document.createElement('picture');
          const source = document.createElement('source');
          const newImg = img.cloneNode(true);

          source.srcset = webpSrc;
          source.type = 'image/webp';

          picture.appendChild(source);
          picture.appendChild(newImg);

          // Copy attributes from original image to picture
          Array.from(img.attributes).forEach((attr) => {
            if (attr.name !== 'src') {
              picture.setAttribute(attr.name, attr.value);
            }
          });

          // Replace original image with picture element
          img.parentNode.replaceChild(picture, img);
        };
        testImg.onerror = () => {
          // WebP version doesn't exist, mark as converted to avoid retrying
          img.setAttribute('data-webp-converted', 'true');
        };
        testImg.src = webpSrc;
      }

      img.setAttribute('data-webp-converted', 'true');
    }

    // Apply WebP conversion if supported
    supportsWebP().then((supported) => {
      if (supported) {
        // Convert existing images
        const images = document.querySelectorAll('img:not([data-webp-converted])');
        images.forEach(tryWebPConversion);

        // Watch for new images added dynamically
        const observer = new MutationObserver((mutations) => {
          mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
              if (node.nodeType === Node.ELEMENT_NODE) {
                if (node.tagName === 'IMG') {
                  tryWebPConversion(node);
                } else if (node.querySelectorAll) {
                  const imgs = node.querySelectorAll('img:not([data-webp-converted])');
                  if (imgs) {
                    imgs.forEach(tryWebPConversion);
                  }
                }
              }
            });
          });
        });

        observer.observe(document.body, {
          childList: true,
          subtree: true,
        });
      }
    });
  }

  // Initialize WebP conversion
  convertImagesToWebP();
});
