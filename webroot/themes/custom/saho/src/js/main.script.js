// * Bootstrap libraries
import './_bootstrap';

// * Any other global site-wide JavaScript should be placed below.
document.addEventListener('DOMContentLoaded', () => {
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
  const offcanvasList = offcanvasElementList.map(
    (offcanvasEl) => new bootstrap.Offcanvas(offcanvasEl)
  );

  // Enhanced dropdown functionality for multilevel menus
  const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
  for (const dropdownToggleEl of dropdownElementList) {
    const dropdown = new bootstrap.Dropdown(dropdownToggleEl, {
      autoClose: 'outside', // Prevents closing when clicking inside dropdown
    });

    // For touch devices, first tap opens dropdown, second tap follows link
    if ('ontouchstart' in document.documentElement) {
      dropdownToggleEl.addEventListener('click', function (e) {
        const parent = this.parentNode;
        if (
          parent.classList.contains('show') &&
          this.getAttribute('href') &&
          this.getAttribute('href') !== '#'
        ) {
          // If dropdown is already open and has a real href, follow the link
          return true;
        }
        // Otherwise just toggle the dropdown
        e.preventDefault();
        e.stopPropagation();
        dropdown.toggle();
      });
    }
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
          console.error('Failed to copy URL: ', err);
        }
      }
    });
  }

  // Initialize tooltips if needed
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  const tooltipList = tooltipTriggerList.map(
    (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
  );

  // Initialize popovers if needed
  const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
  const popoverList = popoverTriggerList.map(
    (popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl)
  );
});
