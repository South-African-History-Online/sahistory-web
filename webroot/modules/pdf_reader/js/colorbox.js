(function ($) {
  Drupal.behaviors.pdf_reader_colorbox = {
    attach: function(context, settings) {
      $(".iframe").colorbox({iframe:true, width:"80%", height:"80%"});
    }}})(jQuery);