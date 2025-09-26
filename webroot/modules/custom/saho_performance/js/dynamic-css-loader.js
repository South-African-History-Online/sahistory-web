/**
 * @file
 * Dynamic CSS loader for non-critical stylesheets.
 */

(function () {
  'use strict';

  // CSS files that should be loaded based on page interactions
  var conditionalCSS = {
    // Load when user hovers over citation elements
    citation: [
      '/modules/custom/saho_tools/css/citation.css',
      '/modules/custom/saho_tools/css/citation-modern.css'
    ],

    // Load when user interacts with sharing elements
    sharing: [
      '/modules/custom/saho_tools/css/sharing-modern.css'
    ]
  };

  // Track loaded CSS to avoid duplicates
  var loadedCSS = new Set();

  /**
   * Load CSS file dynamically
   */
  function loadCSS(href) {
    if (loadedCSS.has(href)) {
      return;
    }

    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    link.media = 'all';

    // Add to head and track as loaded
    document.head.appendChild(link);
    loadedCSS.add(href);

    console.log('Dynamically loaded CSS:', href);
  }

  /**
   * Load CSS group
   */
  function loadCSSGroup(group) {
    if (conditionalCSS[group]) {
      conditionalCSS[group].forEach(loadCSS);
    }
  }

  // Load citation CSS when user hovers over citation elements
  document.addEventListener('mouseover', function (e) {
    if (e.target.closest('[data-cite], .citation, .cite')) {
      loadCSSGroup('citation');
    }
  }, { once: TRUE, passive: TRUE });

  // Load sharing CSS when user interacts with sharing elements
  document.addEventListener('mouseover', function (e) {
    if (e.target.closest('[data-share], .sharing, .share-button')) {
      loadCSSGroup('sharing');
    }
  }, { once: TRUE, passive: TRUE });

  // Load all remaining CSS on user interaction (scroll, click, touch)
  var loadRemainingCSS = function () {
    Object.keys(conditionalCSS).forEach(loadCSSGroup);

    // Remove event listeners after loading
    window.removeEventListener('scroll', loadRemainingCSS);
    window.removeEventListener('touchstart', loadRemainingCSS);
    window.removeEventListener('click', loadRemainingCSS);
  };

  // Load remaining CSS on first user interaction
  window.addEventListener('scroll', loadRemainingCSS, { once: TRUE, passive: TRUE });
  window.addEventListener('touchstart', loadRemainingCSS, { once: TRUE, passive: TRUE });
  window.addEventListener('click', loadRemainingCSS, { once: TRUE, passive: TRUE });

  // Fallback: load remaining CSS after 3 seconds
  setTimeout(loadRemainingCSS, 3000);

})();