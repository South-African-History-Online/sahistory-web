// Bootstrap JS - Optimized imports
// Only importing components actually used in SAHO theme
// This reduces bundle size by ~40KB compared to full Bootstrap
// -----------------------------------------------------------------------------

// Core components used across the site
import Dropdown from 'bootstrap/js/dist/dropdown';
import Modal from 'bootstrap/js/dist/modal';
import Offcanvas from 'bootstrap/js/dist/offcanvas';
import Popover from 'bootstrap/js/dist/popover';
import Tooltip from 'bootstrap/js/dist/tooltip';

// Uncomment if you use these components (currently not detected in main.script.js):
// import Alert from 'bootstrap/js/dist/alert';
// import Button from 'bootstrap/js/dist/button';
// import Carousel from 'bootstrap/js/dist/carousel';
// import Collapse from 'bootstrap/js/dist/collapse';
// import ScrollSpy from 'bootstrap/js/dist/scrollspy';
// import Tab from 'bootstrap/js/dist/tab';
// import Toast from 'bootstrap/js/dist/toast';

import './_tooltip-init';

// Expose Bootstrap components globally for other scripts and Drupal behaviors
window.bootstrap = {
  Dropdown: Dropdown,
  Modal: Modal,
  Offcanvas: Offcanvas,
  Popover: Popover,
  Tooltip: Tooltip,
};
