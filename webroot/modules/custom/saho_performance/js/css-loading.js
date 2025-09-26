/**
 * @file
 * Handles progressive CSS loading for better performance.
 */

(function () {
  'use strict';

  // Function to load CSS asynchronously
  function loadCSS(href, media, callback) {
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    link.media = media || 'all';

    if (callback) {
      link.onload = callback;
    }

    document.head.appendChild(link);
  }

  // Convert print stylesheets to screen stylesheets after load
  var printStylesheets = document.querySelectorAll('link[media="print"][onload]');
  printStylesheets.forEach(function(link) {
    // This handles the deferred CSS loading pattern
    if (link.media === 'print' && link.onload) {
      // The onload will switch it to 'all'
      link.media = 'all';
    }
  });

  // Fallback for browsers that don't support onload for link elements
  var links = document.querySelectorAll('link[rel="stylesheet"][media="print"]');
  var loadedCount = 0;

  links.forEach(function(link) {
    if (!link.onload) {
      // Use a timeout as fallback
      setTimeout(function() {
        link.media = 'all';
      }, 0);
    }
  });

  // Handle CSS loading errors
  window.addEventListener('error', function(e) {
    if (e.target.tagName === 'LINK' && e.target.rel === 'stylesheet') {
      console.error('Failed to load stylesheet:', e.target.href);
      // Retry loading after a delay
      setTimeout(function() {
        loadCSS(e.target.href, e.target.media);
      }, 1000);
    }
  }, true);

})();