/**
 * @file
 * Primary JavaScript file for the Pop-up announcement module.
 */

(function ($) {

Drupal.behaviors.popup_announcement = {
  attach: function (context) {
    if (Drupal.settings && Drupal.settings.popup_announcement) {

      /**
       * Vars
       */
      var delay = Drupal.settings.popup_announcement.delay * 1000;

      /**
       * Create announcement
       */
      function popup_announcement_create() {

        var left = Math.floor(($(window).width() - Drupal.settings.popup_announcement.width) / 2);
        var top = Math.floor(($(window).height() - Drupal.settings.popup_announcement.height) / 2);

        var overlay = $('#popup-announcement-overlay', context);
        var announcement = $('#popup-announcement-wrap', context);
        var close = $('#popup-announcement-close', context);

        overlay.show();
        $('BODY').append(announcement);
        announcement.show().css({
          'width': Drupal.settings.popup_announcement.width,
          'height': Drupal.settings.popup_announcement.height,
          'left': left,
          'top': top
        });

        // close announcement by keyboard or mouse
        close.click(function() {
          overlay.fadeOut();
          announcement.fadeOut();
        });
        overlay.click(function() {
          overlay.fadeOut();
          announcement.fadeOut();
        });
        $(document).keyup(function(e) {
          if (e.keyCode == 27) {
            overlay.fadeOut();
            announcement.fadeOut();
          }
        });

      }

      /**
       * Logic
       */

      // run
      if (delay > 0) {
        timeout_id = window.setTimeout(popup_announcement_create, delay);
      }
      else {
        popup_announcement_create();
      }

    }
  }
};

})(jQuery);