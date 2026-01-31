/**
 * @file
 * SAHO Theme Utility Functions
 *
 * Shared utilities for DOM manipulation, storage, animations, and accessibility.
 * Reduces code duplication across theme JavaScript files.
 */

/**
 * Local Storage Utilities
 */
export const storage = {
  /**
   * Get item from localStorage with JSON parsing.
   */
  get(key, defaultValue = null) {
    try {
      const item = localStorage.getItem(key);
      return item ? JSON.parse(item) : defaultValue;
    } catch (e) {
      console.warn(`Failed to parse localStorage item: ${key}`, e);
      return defaultValue;
    }
  },

  /**
   * Set item in localStorage with JSON stringification.
   */
  set(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value));
      return true;
    } catch (e) {
      console.warn(`Failed to set localStorage item: ${key}`, e);
      return false;
    }
  },

  /**
   * Remove item from localStorage.
   */
  remove(key) {
    try {
      localStorage.removeItem(key);
      return true;
    } catch (e) {
      console.warn(`Failed to remove localStorage item: ${key}`, e);
      return false;
    }
  },

  /**
   * Check if localStorage is available.
   */
  isAvailable() {
    try {
      const test = '__localStorage_test__';
      localStorage.setItem(test, test);
      localStorage.removeItem(test);
      return true;
    } catch (e) {
      return false;
    }
  },
};

/**
 * DOM Utilities
 */
export const dom = {
  /**
   * Query selector with error handling.
   */
  $(selector, context = document) {
    return context.querySelector(selector);
  },

  /**
   * Query selector all as array.
   */
  $$(selector, context = document) {
    return Array.from(context.querySelectorAll(selector));
  },

  /**
   * Create element with attributes and content.
   */
  create(tag, attributes = {}, content = '') {
    const element = document.createElement(tag);

    Object.entries(attributes).forEach(([key, value]) => {
      if (key === 'class') {
        element.className = value;
      } else if (key === 'dataset') {
        Object.entries(value).forEach(([dataKey, dataValue]) => {
          element.dataset[dataKey] = dataValue;
        });
      } else if (key.startsWith('on')) {
        element.addEventListener(key.slice(2).toLowerCase(), value);
      } else {
        element.setAttribute(key, value);
      }
    });

    if (content) {
      if (typeof content === 'string') {
        element.innerHTML = content;
      } else {
        element.appendChild(content);
      }
    }

    return element;
  },

  /**
   * Add class with optional condition.
   */
  addClass(element, className, condition = true) {
    if (condition) {
      element.classList.add(className);
    }
    return element;
  },

  /**
   * Toggle class.
   */
  toggleClass(element, className, force) {
    return element.classList.toggle(className, force);
  },

  /**
   * Check if element has class.
   */
  hasClass(element, className) {
    return element.classList.contains(className);
  },

  /**
   * Get closest ancestor matching selector.
   */
  closest(element, selector) {
    return element.closest(selector);
  },

  /**
   * Wait for element to exist in DOM.
   */
  waitFor(selector, timeout = 5000) {
    return new Promise((resolve, reject) => {
      const element = document.querySelector(selector);
      if (element) {
        resolve(element);
        return;
      }

      const observer = new MutationObserver(() => {
        const element = document.querySelector(selector);
        if (element) {
          observer.disconnect();
          resolve(element);
        }
      });

      observer.observe(document.body, {
        childList: true,
        subtree: true,
      });

      setTimeout(() => {
        observer.disconnect();
        reject(new Error(`Element ${selector} not found within ${timeout}ms`));
      }, timeout);
    });
  },
};

/**
 * Event Utilities
 */
export const events = {
  /**
   * Delegate event listener.
   */
  delegate(parent, selector, event, handler) {
    parent.addEventListener(event, (e) => {
      const target = e.target.closest(selector);
      if (target) {
        handler.call(target, e);
      }
    });
  },

  /**
   * Debounce function calls.
   */
  debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  },

  /**
   * Throttle function calls.
   */
  throttle(func, limit = 300) {
    let inThrottle;
    return function executedFunction(...args) {
      if (!inThrottle) {
        func(...args);
        inThrottle = true;
        setTimeout(() => (inThrottle = false), limit);
      }
    };
  },

  /**
   * Run callback when DOM is ready.
   */
  ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
    } else {
      callback();
    }
  },
};

/**
 * Animation Utilities
 */
export const animate = {
  /**
   * Fade in element.
   */
  fadeIn(element, duration = 300) {
    element.style.opacity = '0';
    element.style.display = 'block';

    let start = null;
    const step = (timestamp) => {
      if (!start) start = timestamp;
      const progress = timestamp - start;
      element.style.opacity = Math.min(progress / duration, 1);

      if (progress < duration) {
        requestAnimationFrame(step);
      }
    };

    requestAnimationFrame(step);
  },

  /**
   * Fade out element.
   */
  fadeOut(element, duration = 300) {
    let start = null;
    const initialOpacity = parseFloat(getComputedStyle(element).opacity);

    const step = (timestamp) => {
      if (!start) start = timestamp;
      const progress = timestamp - start;
      element.style.opacity = Math.max(initialOpacity - (progress / duration), 0);

      if (progress < duration) {
        requestAnimationFrame(step);
      } else {
        element.style.display = 'none';
      }
    };

    requestAnimationFrame(step);
  },

  /**
   * Slide down element.
   */
  slideDown(element, duration = 300) {
    element.style.removeProperty('display');
    let display = window.getComputedStyle(element).display;
    if (display === 'none') display = 'block';
    element.style.display = display;

    const height = element.offsetHeight;
    element.style.overflow = 'hidden';
    element.style.height = '0';
    element.style.paddingTop = '0';
    element.style.paddingBottom = '0';
    element.style.marginTop = '0';
    element.style.marginBottom = '0';
    element.offsetHeight; // Force reflow

    element.style.transition = `height ${duration}ms ease, padding ${duration}ms ease, margin ${duration}ms ease`;
    element.style.height = `${height}px`;
    element.style.removeProperty('padding-top');
    element.style.removeProperty('padding-bottom');
    element.style.removeProperty('margin-top');
    element.style.removeProperty('margin-bottom');

    setTimeout(() => {
      element.style.removeProperty('height');
      element.style.removeProperty('overflow');
      element.style.removeProperty('transition');
    }, duration);
  },

  /**
   * Slide up element.
   */
  slideUp(element, duration = 300) {
    element.style.height = `${element.offsetHeight}px`;
    element.offsetHeight; // Force reflow

    element.style.transition = `height ${duration}ms ease, padding ${duration}ms ease, margin ${duration}ms ease`;
    element.style.overflow = 'hidden';
    element.style.height = '0';
    element.style.paddingTop = '0';
    element.style.paddingBottom = '0';
    element.style.marginTop = '0';
    element.style.marginBottom = '0';

    setTimeout(() => {
      element.style.display = 'none';
      element.style.removeProperty('height');
      element.style.removeProperty('padding-top');
      element.style.removeProperty('padding-bottom');
      element.style.removeProperty('margin-top');
      element.style.removeProperty('margin-bottom');
      element.style.removeProperty('overflow');
      element.style.removeProperty('transition');
    }, duration);
  },
};

/**
 * Accessibility Utilities
 */
export const a11y = {
  /**
   * Set ARIA attribute.
   */
  setAria(element, attribute, value) {
    element.setAttribute(`aria-${attribute}`, value);
  },

  /**
   * Toggle ARIA expanded.
   */
  toggleExpanded(element, force) {
    const expanded = force !== undefined ? force : element.getAttribute('aria-expanded') !== 'true';
    element.setAttribute('aria-expanded', expanded);
    return expanded;
  },

  /**
   * Announce to screen readers.
   */
  announce(message, priority = 'polite') {
    const announcer = document.getElementById('saho-announcer') || this.createAnnouncer();
    announcer.setAttribute('aria-live', priority);
    announcer.textContent = message;

    setTimeout(() => {
      announcer.textContent = '';
    }, 1000);
  },

  /**
   * Create live region for announcements.
   */
  createAnnouncer() {
    const announcer = document.createElement('div');
    announcer.id = 'saho-announcer';
    announcer.className = 'visually-hidden';
    announcer.setAttribute('aria-live', 'polite');
    announcer.setAttribute('aria-atomic', 'true');
    document.body.appendChild(announcer);
    return announcer;
  },

  /**
   * Trap focus within element.
   */
  trapFocus(element) {
    const focusableElements = element.querySelectorAll(
      'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
    );

    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements[focusableElements.length - 1];

    const handleTab = (e) => {
      if (e.key !== 'Tab') return;

      if (e.shiftKey) {
        if (document.activeElement === firstFocusable) {
          e.preventDefault();
          lastFocusable.focus();
        }
      } else {
        if (document.activeElement === lastFocusable) {
          e.preventDefault();
          firstFocusable.focus();
        }
      }
    };

    element.addEventListener('keydown', handleTab);

    return () => element.removeEventListener('keydown', handleTab);
  },
};

/**
 * URL and String Utilities
 */
export const utils = {
  /**
   * Get URL parameter.
   */
  getUrlParam(param) {
    const params = new URLSearchParams(window.location.search);
    return params.get(param);
  },

  /**
   * Set URL parameter without reload.
   */
  setUrlParam(param, value) {
    const url = new URL(window.location);
    url.searchParams.set(param, value);
    window.history.pushState({}, '', url);
  },

  /**
   * Sanitize HTML string.
   */
  sanitize(str) {
    const temp = document.createElement('div');
    temp.textContent = str;
    return temp.innerHTML;
  },

  /**
   * Truncate string with ellipsis.
   */
  truncate(str, length = 100, suffix = '...') {
    if (str.length <= length) return str;
    return str.substring(0, length - suffix.length) + suffix;
  },

  /**
   * Check if device is touch-enabled.
   */
  isTouchDevice() {
    return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
  },

  /**
   * Get viewport dimensions.
   */
  viewport() {
    return {
      width: Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0),
      height: Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0),
    };
  },
};

// Default export with all utilities
export default {
  storage,
  dom,
  events,
  animate,
  a11y,
  utils,
};
