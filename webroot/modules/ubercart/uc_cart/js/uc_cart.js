/**
 * @file
 * Utility functions to handled order submission on /cart/checkout/review page.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Adds a throbber to the submit order button on the review order form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior to prevent double click of submit button.
   */
  Drupal.behaviors.ucCart = {
    attach: function (context) {
      $(context).find('#uc-cart-checkout-review-form #edit-submit').once('uc_cart').click(function () {
        $(this).clone().insertAfter(this).prop('disabled', true).after('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>').end().hide();
        $(context).find('#uc-cart-checkout-review-form #edit-back').prop('disabled', true);
      });
    }
  };

})(jQuery, Drupal);
