/**
 * @file
 * Editorial Register sort row: the select applies on change; the Apply
 * button only exists for no-JS.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.sahoFeaturedRegisterSort = {
    attach(context) {
      once('featured-sort', '[data-featured-sort] select', context).forEach((select) => {
        select.addEventListener('change', () => {
          select.form.submit();
        });
      });
    },
  };
})(Drupal, once);
