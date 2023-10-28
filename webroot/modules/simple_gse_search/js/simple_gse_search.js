(function ($) {

  Drupal.behaviors.simple_gse_search = {
    attach: function(context, settings) {
      // Only run this script on full documents, not ajax requests.
      if (context !== document) {
        return;
      }
      // This chunk comes from the 'Get Code' option from GSE. Only the cx value
      // has been altered.
      var gcse = document.createElement('script');
      gcse.type = 'text/javascript';
      gcse.async = true;
      gcse.src = 'https://cse.google.com/cse.js?cx=' + settings.simple_gse_search.cx;
      var s = document.getElementsByTagName('script')[0];
      s.parentNode.insertBefore(gcse, s);
    }
  };

}(jQuery));
