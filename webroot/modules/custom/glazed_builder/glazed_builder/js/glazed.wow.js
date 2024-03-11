/**
 * @file
 * A JavaScript file initiates the wow.js library
 * With additional percentage offset support
 *
 */

(function ($, Drupal, window, document, undefined) {
// Hook into Glazed Restore event
$(document).on("glazed_restore", function(sender, data) {
  $(data.dom).find('.animated').removeClass('animated');
});

Drupal.behaviors.GlazedWOWjs = {
  attach: function(context, settings) {
		var windowHeight = $(window).height();
		var offset = 0;
		$( ".wow" ).each(function() {
			if ($(this).attr('data-wow-center-offset')) {
				offset = windowHeight / 100 * $(this).attr('data-wow-center-offset');
			}
			else {
				offset = windowHeight * 0.25;
			}
			offset = offset + $(this).height() / 2;
		  $(this).attr( "data-wow-offset", parseInt(offset) );
		});
	}
};

})(jQuery, Drupal, this, this.document);

new WOW().init();
