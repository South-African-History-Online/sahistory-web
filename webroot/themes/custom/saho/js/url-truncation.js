/**
 * @file
 * URL truncation enhancements for SAHO further reading and references sections.
 * 
 * Provides JavaScript enhancements for better UX with truncated URLs including:
 * - Tooltip functionality showing full URLs
 * - Click-to-copy functionality
 * - Improved accessibility
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Initialize URL truncation enhancements.
   */
  Drupal.behaviors.sahoUrlTruncation = {
    attach: function (context, settings) {
      // Enhanced selector to cover all sidebar content areas
      const selectors = [
        '.saho-further-reading a',
        '.saho-references a', 
        '.saho-saho-sources a',
        '.saho-reference-list a',
        '.saho-content-group a',
        '.saho-resource-section a',
        '.saho-taxonomy-section a',
        '.saho-metadata-content a',
        '.saho-sidebar-tabs a[href*="http"]',
        '.field--type-link a',
        '.field--type-text-long a[href*="http"]',
        '.field--type-text a[href*="http"]',
        'p a[href*="http"]',
        '.field__item a[href*="http"]',
        '.text-formatted a[href*="http"]'
      ].join(', ');

      once('saho-url-truncation', selectors, context).forEach(function (element) {
        const $link = $(element);
        const fullUrl = $link.attr('href');
        const linkText = $link.text().trim();
        
        // Process if the link text appears to be a URL and is long, or if href is a URL
        if ((isUrl(linkText) && linkText.length > 50) || (fullUrl && isUrl(fullUrl) && fullUrl.length > 50)) {
          enhanceUrlLink($link, fullUrl, linkText || fullUrl);
        }
      });
    }
  };

  /**
   * Check if a string appears to be a URL.
   * 
   * @param {string} str - The string to check
   * @returns {boolean} - True if it appears to be a URL
   */
  function isUrl(str) {
    return str.match(/^https?:\/\//) || str.includes('www.') || str.includes('.com') || str.includes('.org') || str.includes('.net');
  }

  /**
   * Enhance a URL link with truncation and tooltip functionality.
   * 
   * @param {jQuery} $link - The link element
   * @param {string} fullUrl - The full URL
   * @param {string} linkText - The original link text
   */
  function enhanceUrlLink($link, fullUrl, linkText) {
    // Add classes and data attributes
    $link.addClass('saho-url-truncated');
    $link.attr('data-full-url', linkText);
    $link.attr('title', 'Full URL: ' + linkText + ' (Click to visit, Ctrl+Click to copy)');
    
    // Add click-to-copy functionality (Ctrl+Click)
    $link.on('click', function (e) {
      if (e.ctrlKey || e.metaKey) {
        e.preventDefault();
        copyToClipboard(linkText);
        showCopyNotification($link);
      }
    });

    // Add keyboard accessibility
    $link.on('keydown', function (e) {
      // Space or Enter to copy URL
      if ((e.key === ' ' || e.key === 'Enter') && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        copyToClipboard(linkText);
        showCopyNotification($link);
      }
    });

    // Improve accessibility
    $link.attr('aria-label', 'Link to: ' + linkText);
  }

  /**
   * Copy text to clipboard.
   * 
   * @param {string} text - The text to copy
   */
  function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
      // Use modern clipboard API
      navigator.clipboard.writeText(text).catch(function (err) {
        fallbackCopyToClipboard(text);
      });
    } else {
      // Fallback for older browsers
      fallbackCopyToClipboard(text);
    }
  }

  /**
   * Fallback clipboard copy method.
   * 
   * @param {string} text - The text to copy
   */
  function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
      document.execCommand('copy');
    } catch (err) {
      // Fallback copy failed
    }
    
    document.body.removeChild(textArea);
  }

  /**
   * Show a temporary notification that URL was copied.
   * 
   * @param {jQuery} $link - The link that was copied
   */
  function showCopyNotification($link) {
    // Remove any existing notifications
    $('.saho-copy-notification').remove();
    
    // Create notification element
    const $notification = $('<div class="saho-copy-notification">URL copied to clipboard!</div>');
    $notification.css({
      position: 'absolute',
      background: '#28a745',
      color: 'white',
      padding: '0.5rem 1rem',
      borderRadius: '0.25rem',
      fontSize: '0.875rem',
      zIndex: 9999,
      whiteSpace: 'nowrap',
      boxShadow: '0 2px 8px rgba(0, 0, 0, 0.15)'
    });
    
    // Position relative to the link
    const linkOffset = $link.offset();
    $notification.css({
      top: linkOffset.top - 40,
      left: linkOffset.left
    });
    
    // Add to DOM and animate
    $('body').append($notification);
    $notification.fadeIn(200);
    
    // Remove after 2 seconds
    setTimeout(function () {
      $notification.fadeOut(200, function () {
        $notification.remove();
      });
    }, 2000);
  }

  /**
   * Create enhanced tooltip for long URLs.
   * 
   * @param {jQuery} $link - The link element
   * @param {string} fullUrl - The full URL to display
   */
  function createTooltip($link, fullUrl) {
    let tooltipTimeout;
    
    $link.on('mouseenter focus', function () {
      tooltipTimeout = setTimeout(function () {
        showTooltip($link, fullUrl);
      }, 500); // Show after 500ms delay
    });
    
    $link.on('mouseleave blur', function () {
      clearTimeout(tooltipTimeout);
      hideTooltip();
    });
  }

  /**
   * Show tooltip with full URL.
   * 
   * @param {jQuery} $link - The link element
   * @param {string} fullUrl - The full URL to display
   */
  function showTooltip($link, fullUrl) {
    // Remove existing tooltips
    $('.saho-url-tooltip').remove();
    
    const $tooltip = $('<div class="saho-url-tooltip"></div>');
    $tooltip.text(fullUrl);
    $tooltip.css({
      position: 'absolute',
      background: '#333',
      color: '#fff',
      padding: '0.5rem',
      borderRadius: '0.25rem',
      fontSize: '0.875rem',
      maxWidth: '400px',
      wordBreak: 'break-all',
      zIndex: 9999,
      boxShadow: '0 2px 8px rgba(0, 0, 0, 0.15)',
      opacity: 0
    });
    
    const linkOffset = $link.offset();
    $tooltip.css({
      top: linkOffset.top + $link.outerHeight() + 5,
      left: linkOffset.left
    });
    
    $('body').append($tooltip);
    $tooltip.animate({ opacity: 1 }, 200);
  }

  /**
   * Hide tooltip.
   */
  function hideTooltip() {
    $('.saho-url-tooltip').fadeOut(200, function () {
      $(this).remove();
    });
  }

})(jQuery, Drupal, once);