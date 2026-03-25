/**
 * @file
 * SAHO Donate behaviours.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Auto-submit the PayFast redirect form.
   */
  Drupal.behaviors.sahoDonateRedirect = {
    attach(context) {
      once('saho-payfast-redirect', '#saho-payfast-form', context).forEach((form) => {
        form.submit();
      });
    },
  };

  /**
   * Snapscan QR overlay: open on button click, close on backdrop/close button.
   */
  Drupal.behaviors.sahoSnapscanOverlay = {
    attach(context) {
      once('saho-snapscan-overlay', '[data-donate-qr-modal]', context).forEach((btn) => {
        const overlay = btn.closest('.donate-pathway-card--snapscan').querySelector('.donate-snapscan-overlay');
        if (!overlay) return;

        btn.addEventListener('click', () => {
          overlay.removeAttribute('hidden');
          overlay.querySelector('.donate-snapscan-overlay__close').focus();
        });

        overlay.querySelectorAll('[data-donate-qr-close]').forEach((el) => {
          el.addEventListener('click', () => overlay.setAttribute('hidden', ''));
        });

        overlay.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') overlay.setAttribute('hidden', '');
        });
      });
    },
  };

})(Drupal, once);
