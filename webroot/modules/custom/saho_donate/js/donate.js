// phpcs:ignoreFile
/**
 * @file
 * SAHO Donate behaviours.
 */

(function (Drupal, once) {
  'use strict';

  // Single source of truth for the last-supported-tier memory.
  // 30-day TTL so a year-old visit doesn't keep marking buttons.
  var SUPPORTER_KEY = 'saho_last_supported_action';
  var SUPPORTER_TTL_MS = 30 * 24 * 60 * 60 * 1000;

  function readLastSupporter() {
    try {
      var raw = localStorage.getItem(SUPPORTER_KEY);
      if (!raw) {
        return null;
      }
      var parsed = JSON.parse(raw);
      if (!parsed || !parsed.href || !parsed.at) {
        return null;
      }
      if (Date.now() - parsed.at > SUPPORTER_TTL_MS) {
        localStorage.removeItem(SUPPORTER_KEY);
        return null;
      }
      return parsed.href;
    } catch (_) {
      return null;
    }
  }

  function writeLastSupporter(href) {
    try {
      localStorage.setItem(
        SUPPORTER_KEY,
        JSON.stringify({ href: href, at: Date.now() })
      );
    } catch (_) {
      /* localStorage may be blocked; ignore */
    }
  }

  /**
   * Auto-submit the PayFast redirect form.
   */
  Drupal.behaviors.sahoDonateRedirect = {
    attach: function (context) {
      once('saho-payfast-redirect', '#saho-payfast-form', context).forEach(function (form) {
        form.submit();
      });
    }
  };

  /**
   * Snapscan QR overlay: open on button click, close on backdrop/close button.
   * Works for both the donate-pathways block and the page-footer support banner.
   */
  Drupal.behaviors.sahoSnapscanOverlay = {
    attach: function (context) {
      once('saho-snapscan-overlay', '[data-donate-qr-modal]', context).forEach(function (btn) {
        var overlayId = btn.getAttribute('data-donate-qr-modal');
        var overlay = null;
        if (overlayId) {
          overlay = document.getElementById(overlayId);
        }
        if (!overlay) {
          var ancestor = btn.closest('.donate-pathway-card--snapscan, .footer-support-banner');
          if (ancestor) {
            overlay = ancestor.querySelector('.donate-snapscan-overlay');
          }
        }
        if (!overlay) {
          return;
        }

        function focusable() {
          return (
            overlay.querySelector('.donate-snapscan-overlay__close')
            || overlay.querySelector('[data-donate-qr-close]')
          );
        }

        btn.addEventListener('click', function (e) {
          e.preventDefault();
          overlay.removeAttribute('hidden');
          var target = focusable();
          if (target) {
            target.focus();
          }
        });

        overlay.querySelectorAll('[data-donate-qr-close]').forEach(function (el) {
          el.addEventListener('click', function () {
            overlay.setAttribute('hidden', '');
          });
        });

        // Esc to close - listen at document level since overlay isn't focused
        // when the user is reading the QR code.
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && !overlay.hasAttribute('hidden')) {
            overlay.setAttribute('hidden', '');
            btn.focus();
          }
        });
      });
    }
  };

  /**
   * Selected-state memory for donate pathway / champion tier buttons.
   *
   * Persists which CTA the visitor last clicked across pages so returning
   * users immediately see which option they picked. Applies a `--selected`
   * modifier on the matching anchor.
   *
   * Match: any anchor with `data-supporter-track` OR any `.donate-btn`,
   * `.support-btn` linking to a champion / donate URL, OR the footer's
   * primary/secondary support CTAs.
   */
  Drupal.behaviors.sahoSupporterMemory = {
    attach: function (context) {
      var selector = [
        '[data-supporter-track]',
        '.donate-btn[href]',
        '.support-btn[href*="/product/saho-champion"]',
        '.support-btn[href*="/champion"]',
        '.footer-cta-primary[href]',
        '.footer-cta-secondary[href]'
      ].join(', ');

      once('saho-supporter-memory', selector, context).forEach(function (btn) {
        var href = btn.getAttribute('href');
        if (!href) {
          return;
        }

        // Restore selection from previous visit.
        if (href === readLastSupporter()) {
          if (btn.classList.contains('donate-btn')) {
            btn.classList.add('donate-btn--selected');
          }
          if (btn.classList.contains('support-btn')) {
            btn.classList.add('support-btn--selected');
          }
          if (btn.classList.contains('footer-cta-primary')) {
            btn.classList.add('footer-cta-primary--selected');
          }
          if (btn.classList.contains('footer-cta-secondary')) {
            btn.classList.add('footer-cta-secondary--selected');
          }
        }

        btn.addEventListener('click', function () {
          writeLastSupporter(href);
        });
      });
    }
  };

})(Drupal, once);
