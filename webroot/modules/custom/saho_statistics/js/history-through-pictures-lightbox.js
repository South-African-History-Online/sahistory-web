/**
 * @file
 * GLightbox initialization for History Through Pictures gallery.
 *
 * One shared instance: re-attaching (e.g. after load-more appends tiles)
 * reloads it instead of stacking new instances per element.
 */
(function (Drupal, once) {
  Drupal.behaviors.historyThroughPicturesSort = {
    attach: function (context) {
      // Quiet mono sort row: the select applies on change; the Apply
      // button only exists for no-JS (#453).
      once('gallery-sort', '.gallery-sort select', context).forEach(function (select) {
        select.addEventListener('change', function () {
          select.form.submit();
        });
      });
    }
  };

  Drupal.behaviors.historyThroughPicturesLightbox = {
    attach: function (context) {
      const fresh = once('glightbox-init', '.history-picture-lightbox', context);
      if (!fresh.length) {
        return;
      }
      if (typeof GLightbox === 'undefined') {
        console.warn('GLightbox library not loaded');
        return;
      }
      if (Drupal.sahoHtpLightbox) {
        Drupal.sahoHtpLightbox.reload();
        return;
      }
      Drupal.sahoHtpLightbox = GLightbox({
        selector: '.history-picture-lightbox',
        touchNavigation: true,
        loop: true,
        autoplayVideos: false,
        closeOnOutsideClick: true,
        skin: 'saho-lightbox',
        descPosition: 'bottom',
      });
    }
  };
})(Drupal, once);
