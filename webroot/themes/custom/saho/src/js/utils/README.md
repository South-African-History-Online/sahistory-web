# SAHO Theme JavaScript Utilities

Shared utilities for SAHO theme JavaScript development. Reduces code duplication and provides consistent patterns across the theme.

## Installation

```javascript
// Import all utilities
import saho from './utils/saho-utils.js';

// Or import specific modules
import { storage, dom, events, animate, a11y, utils } from './utils/saho-utils.js';
```

## Modules

### Storage Utilities

Safe localStorage operations with JSON serialization.

```javascript
// Get with default value
const history = storage.get('saho-search-history', []);

// Set value (auto JSON stringify)
storage.set('saho-search-view', 'grid');

// Remove value
storage.remove('old-key');

// Check if localStorage is available
if (storage.isAvailable()) {
  // Use storage
}
```

### DOM Utilities

Modern DOM manipulation helpers.

```javascript
// Query selector (returns single element or null)
const header = dom.$('.saho-header');

// Query selector all (returns array)
const cards = dom.$$('.saho-card');

// Create element with attributes
const button = dom.create('button', {
  class: 'saho-button',
  'aria-label': 'Close',
  dataset: { action: 'close' },
  onclick: () => console.log('clicked'),
}, 'Close');

// Class manipulation
dom.addClass(element, 'active', isActive);
dom.toggleClass(element, 'expanded');
dom.hasClass(element, 'visible');

// Wait for element to appear
dom.waitFor('.dynamic-content').then(el => {
  console.log('Element loaded:', el);
});
```

### Event Utilities

Event handling and performance helpers.

```javascript
// Delegate events (better performance for dynamic content)
events.delegate(document.body, '.saho-card', 'click', function(e) {
  console.log('Card clicked:', this);
});

// Debounce (wait until user stops typing)
const search = events.debounce((query) => {
  performSearch(query);
}, 300);
input.addEventListener('input', (e) => search(e.target.value));

// Throttle (limit calls per time period)
const handleScroll = events.throttle(() => {
  updateScrollPosition();
}, 100);
window.addEventListener('scroll', handleScroll);

// DOM ready
events.ready(() => {
  initializeApp();
});
```

### Animation Utilities

Smooth animations without jQuery.

```javascript
// Fade in/out
animate.fadeIn(element, 300);
animate.fadeOut(element, 300);

// Slide up/down
animate.slideDown(element, 300);
animate.slideUp(element, 300);
```

### Accessibility Utilities

ARIA and focus management.

```javascript
// Set ARIA attributes
a11y.setAria(button, 'label', 'Close dialog');
a11y.setAria(menu, 'expanded', 'true');

// Toggle aria-expanded
const isExpanded = a11y.toggleExpanded(dropdown);

// Announce to screen readers
a11y.announce('Search results updated', 'polite');
a11y.announce('Error occurred', 'assertive');

// Trap focus in modal
const releaseFocus = a11y.trapFocus(modal);
// Later: releaseFocus(); to remove trap
```

### General Utilities

URL, string, and device detection.

```javascript
// URL parameters
const page = utils.getUrlParam('page'); // Get param
utils.setUrlParam('view', 'grid'); // Set without reload

// String operations
const clean = utils.sanitize(userInput); // XSS protection
const short = utils.truncate(longText, 100, '...');

// Device detection
if (utils.isTouchDevice()) {
  // Add touch-specific behavior
}

// Viewport dimensions
const { width, height } = utils.viewport();
if (width < 768) {
  // Mobile layout
}
```

## Usage Examples

### Replace localStorage calls

**Before:**
```javascript
const savedHistory = localStorage.getItem('saho-search-history');
const history = savedHistory ? JSON.parse(savedHistory) : [];
history.push(newSearch);
localStorage.setItem('saho-search-history', JSON.stringify(history));
```

**After:**
```javascript
import { storage } from './utils/saho-utils.js';

const history = storage.get('saho-search-history', []);
history.push(newSearch);
storage.set('saho-search-history', history);
```

### Replace querySelector patterns

**Before:**
```javascript
const forms = Array.from(document.querySelectorAll('.search-form'));
forms.forEach(form => {
  // ...
});
```

**After:**
```javascript
import { dom } from './utils/saho-utils.js';

dom.$$('.search-form').forEach(form => {
  // ...
});
```

### Replace manual debounce

**Before:**
```javascript
let timeout;
input.addEventListener('input', (e) => {
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    search(e.target.value);
  }, 300);
});
```

**After:**
```javascript
import { events } from './utils/saho-utils.js';

const debouncedSearch = events.debounce(search, 300);
input.addEventListener('input', (e) => debouncedSearch(e.target.value));
```

## Benefits

- **DRY**: Single source of truth for common operations
- **Consistent**: Same patterns across all theme JavaScript
- **Safe**: Error handling and fallbacks built-in
- **Accessible**: ARIA helpers ensure better a11y
- **Modern**: ES6+ syntax, no jQuery dependency
- **Tested**: Battle-tested utilities from production code
- **Small**: Tree-shakeable imports

## Migration Guide

1. Import utilities at top of file
2. Replace manual patterns with utility calls
3. Test functionality
4. Remove redundant code

## Browser Support

- Modern browsers (ES6+)
- IE11 not supported (Drupal 11 requirement)
- Uses native APIs (no polyfills needed)

## Performance

- Utilities use native APIs (faster than jQuery)
- Event delegation reduces memory usage
- Debounce/throttle prevent excessive calls
- requestAnimationFrame for smooth animations

## License

Part of SAHO theme - South African History Online
