/**
 * @file
 * JS code for setting print view of page.
 */

jQuery(document).ready(function($) {
  $("#printlink").click(function(e) {
    $("link").attr("href",$(this).attr('rel'));
    e.preventDefault();
  });
});
