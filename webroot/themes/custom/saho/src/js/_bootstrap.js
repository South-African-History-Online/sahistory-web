// Bootstrap JS.
// Comment out the library you are not using.
// -----------------------------------------------------------------------------
import Alert from 'bootstrap/js/dist/alert';
import Button from 'bootstrap/js/dist/button';
import Carousel from 'bootstrap/js/dist/carousel';
import Collapse from 'bootstrap/js/dist/collapse';
import Dropdown from 'bootstrap/js/dist/dropdown';
import Offcanvas from 'bootstrap/js/dist/offcanvas';
import Modal from 'bootstrap/js/dist/modal';
import Popover from 'bootstrap/js/dist/popover';
import ScrollSpy from 'bootstrap/js/dist/scrollspy';
import Tab from 'bootstrap/js/dist/tab';
import Tooltip from 'bootstrap/js/dist/tooltip';
import Toast from 'bootstrap/js/dist/toast';
import "./_tooltip-init";
import "./_toast-init";

// Expose Bootstrap components globally for other scripts to use
window.bootstrap = {
  Alert: Alert,
  Button: Button,
  Carousel: Carousel,
  Collapse: Collapse,
  Dropdown: Dropdown,
  Modal: Modal,
  Offcanvas: Offcanvas,
  Popover: Popover,
  ScrollSpy: ScrollSpy,
  Tab: Tab,
  Tooltip: Tooltip,
  Toast: Toast
};