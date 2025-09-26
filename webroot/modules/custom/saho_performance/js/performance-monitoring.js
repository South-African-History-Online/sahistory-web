/**
 * @file
 * Performance monitoring for SAHO site.
 */

(function () {
  'use strict';

  // Only run if Performance API is available
  if (!window.performance || !window.performance.timing) {
    return;
  }

  window.addEventListener('load', function() {
    setTimeout(function() {
      var timing = performance.timing;
      var metrics = {
        // Navigation timing
        dns: timing.domainLookupEnd - timing.domainLookupStart,
        tcp: timing.connectEnd - timing.connectStart,
        request: timing.responseStart - timing.requestStart,
        response: timing.responseEnd - timing.responseStart,
        dom: timing.domComplete - timing.domLoading,
        load: timing.loadEventEnd - timing.navigationStart,

        // Paint timing
        firstPaint: 0,
        firstContentfulPaint: 0,
        largestContentfulPaint: 0
      };

      // Get paint timing if available
      if (performance.getEntriesByType) {
        var paintEntries = performance.getEntriesByType('paint');
        paintEntries.forEach(function(entry) {
          if (entry.name === 'first-paint') {
            metrics.firstPaint = Math.round(entry.startTime);
          }
          if (entry.name === 'first-contentful-paint') {
            metrics.firstContentfulPaint = Math.round(entry.startTime);
          }
        });

        // Get LCP if available
        if (window.PerformanceObserver) {
          try {
            var lcpObserver = new PerformanceObserver(function(entryList) {
              var entries = entryList.getEntries();
              var lastEntry = entries[entries.length - 1];
              metrics.largestContentfulPaint = Math.round(lastEntry.startTime);
            });
            lcpObserver.observe({ type: 'largest-contentful-paint', buffered: true });
          } catch (e) {
            // LCP not supported
          }
        }
      }

      // Log metrics to console in development
      if (window.location.hostname === 'localhost' || window.location.hostname.includes('.ddev.site')) {
        console.group('Page Performance Metrics');
        console.log('DNS Lookup:', metrics.dns + 'ms');
        console.log('TCP Connection:', metrics.tcp + 'ms');
        console.log('Request Time:', metrics.request + 'ms');
        console.log('Response Time:', metrics.response + 'ms');
        console.log('DOM Processing:', metrics.dom + 'ms');
        console.log('Total Load Time:', metrics.load + 'ms');
        if (metrics.firstPaint) {
          console.log('First Paint:', metrics.firstPaint + 'ms');
        }
        if (metrics.firstContentfulPaint) {
          console.log('First Contentful Paint:', metrics.firstContentfulPaint + 'ms');
        }
        if (metrics.largestContentfulPaint) {
          console.log('Largest Contentful Paint:', metrics.largestContentfulPaint + 'ms');
        }
        console.groupEnd();
      }

      // Send metrics to analytics if needed
      if (window.gtag && metrics.load > 0) {
        window.gtag('event', 'page_load_time', {
          event_category: 'Performance',
          value: Math.round(metrics.load),
          page_path: window.location.pathname
        });
      }

    }, 0);
  });

  // Monitor long tasks
  if (window.PerformanceObserver) {
    try {
      var longTaskObserver = new PerformanceObserver(function(list) {
        for (var entry of list.getEntries()) {
          // Log long tasks in development
          if (window.location.hostname === 'localhost' || window.location.hostname.includes('.ddev.site')) {
            console.warn('Long task detected:', {
              duration: Math.round(entry.duration) + 'ms',
              startTime: Math.round(entry.startTime) + 'ms'
            });
          }
        }
      });
      longTaskObserver.observe({ entryTypes: ['longtask'] });
    } catch (e) {
      // Long task monitoring not supported
    }
  }

})();