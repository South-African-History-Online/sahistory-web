/**
 * @file
 * Replaced Drupal cores ajax throbber(s), see: https://www.drupal.org/node/2974681
 *
 */
(function ($, Drupal) {
  Drupal.theme.ajaxProgressThrobber = () => `
    <span class="ajax-spinner ajax-spinner--inline fs-sm me-2">
        <span class="spinner-grow spinner-grow-sm text-secondary" role="status"></span>
    </span>`;
  Drupal.theme.ajaxProgressIndicatorFullscreen = () =>
    `
    <div class="ajax-spinner ajax-spinner--fullscreen">
      <span class="ajax-spinner__label">${Drupal.t(
        'Loading&nbsp;&hellip;',
        {},
        { context: 'Loading text for Drupal cores Ajax throbber (fullscreen)' },
      )}
      </span>
    </div>`;
  // You can also customize only throbber message:
  // Drupal.theme.ajaxProgressMessage = message => '<div class="my-message">' + message + '</div>';
})(jQuery, Drupal);
