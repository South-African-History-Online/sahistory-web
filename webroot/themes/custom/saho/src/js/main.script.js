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
        } catch (err) {
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

  // Enhanced dropdown functionality for all devices
  const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
  for (const dropdownToggleEl of dropdownElementList) {
    const dropdown = new bootstrap.Dropdown(dropdownToggleEl, {
      autoClose: 'outside', // Prevents closing when clicking inside dropdown
    });

    // Handle clicks for all devices (both desktop and mobile)
    dropdownToggleEl.addEventListener('click', function (e) {
      const parent = this.parentNode;
      
      // For touch devices, handle the two-tap behavior
      if ('ontouchstart' in document.documentElement) {
        if (
          parent.classList.contains('show') &&
          this.getAttribute('href') &&
          this.getAttribute('href') !== '#'
        ) {
          // If dropdown is already open and has a real href, follow the link
          return true;
        }
      }
      
      // Always prevent default and stop propagation for dropdown toggles
      e.preventDefault();
      e.stopPropagation();
      
      // Toggle the dropdown
      dropdown.toggle();
    });
    
    // Handle keyboard accessibility
    dropdownToggleEl.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        dropdown.toggle();
      }
    });
  }

  // Share modal URL copy functionality
  const copyShareUrlBtn = document.getElementById('copyShareUrl');
  if (copyShareUrlBtn) {
    copyShareUrlBtn.addEventListener('click', function () {
      const shareUrlInput = document.getElementById('shareUrl');
      if (shareUrlInput) {
        shareUrlInput.select();
        shareUrlInput.setSelectionRange(0, 99999); // For mobile devices

        try {
          // Copy the text to clipboard
          document.execCommand('copy');

          // Change button text temporarily to provide feedback
          const originalText = this.textContent;
          this.textContent = 'Copied!';
          this.classList.add('btn-success');
          this.classList.remove('btn-outline-secondary');

          // Reset button after 2 seconds
          setTimeout(() => {
            this.textContent = originalText;
            this.classList.remove('btn-success');
            this.classList.add('btn-outline-secondary');
          }, 2000);
        } catch (err) {
          // Failed to copy URL
        }
      }
    });
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
        webP.onload = webP.onerror = function () {
          resolve(webP.height === 2);
        };
        webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
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
        testImg.onload = function() {
          // WebP version exists, create picture element
          const picture = document.createElement('picture');
          const source = document.createElement('source');
          const newImg = img.cloneNode(true);
          
          source.srcset = webpSrc;
          source.type = 'image/webp';
          
          picture.appendChild(source);
          picture.appendChild(newImg);
          
          // Copy attributes from original image to picture
          Array.from(img.attributes).forEach(attr => {
            if (attr.name !== 'src') {
              picture.setAttribute(attr.name, attr.value);
            }
          });
          
          // Replace original image with picture element
          img.parentNode.replaceChild(picture, img);
        };
        testImg.onerror = function() {
          // WebP version doesn't exist, mark as converted to avoid retrying
          img.setAttribute('data-webp-converted', 'true');
        };
        testImg.src = webpSrc;
      }

      img.setAttribute('data-webp-converted', 'true');
    }

    // Apply WebP conversion if supported
    supportsWebP().then(supported => {
      if (supported) {
        // Convert existing images
        const images = document.querySelectorAll('img:not([data-webp-converted])');
        images.forEach(tryWebPConversion);

        // Watch for new images added dynamically
        const observer = new MutationObserver(mutations => {
          mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
              if (node.nodeType === Node.ELEMENT_NODE) {
                if (node.tagName === 'IMG') {
                  tryWebPConversion(node);
                } else {
                  const imgs = node.querySelectorAll?.('img:not([data-webp-converted])');
                  imgs?.forEach(tryWebPConversion);
                }
              }
            });
          });
        });

        observer.observe(document.body, {
          childList: true,
          subtree: true
        });
      }
    });
  }

  // Initialize WebP conversion
  convertImagesToWebP();
});
