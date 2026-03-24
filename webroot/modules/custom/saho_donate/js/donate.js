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

})(Drupal, once);
