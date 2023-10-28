/**
 * @file
 * Utility functions to control behavior of cart block.
 */

(function ($) {

  'use strict';

  /**
   * Sets the behavior to (un)collapse the cart block on a click.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for cart block collapse/expand.
   */
  Drupal.behaviors.uc_cart_block = {
    attach: function (context) {
      $(context).find('.cart-block-arrow').once('uc_cart_block').click(function () {
        $(context).find('.cart-block-arrow, .cart-block-items').toggleClass('collapsed');
      });
    }
  };

})(jQuery);
