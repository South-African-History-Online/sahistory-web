/**
 * @file
 * Defines ajax throbber theme functions.
 * Credit to: Pierre Dureau (pdureau) for the initial code.
 */

((Drupal) => {
  /**
   * An animated progress throbber and container element for AJAX operations.
   *
   * @param {string} [message]
   *   (optional) The message shown on the UI.
   * @return {string}
   *   The HTML markup for the throbber.
   */
  Drupal.theme.ajaxProgressThrobber = (message) => {
    // Build markup without adding extra white space since it affects rendering.
    const messageMarkup =
      typeof message === 'string'
        ? Drupal.theme('ajaxProgressMessage', message)
        : '';

    if (messageMarkup === '') {
      const defaultMessage = Drupal.t('Loading...');
      return `<div class="ajax-progress ajax-progress-throbber">
        <div class="spinner-border spinner-border-sm" role="status">
          <span class="visually-hidden">${defaultMessage}</span>
        </div>
      </div>`;
    }

    return `<div class="ajax-progress ajax-progress-throbber">
      <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
      ${messageMarkup}
    </div>`;
  };

  /**
   * Formats text accompanying the AJAX progress throbber.
   *
   * @param {string} message
   *   The message shown on the UI.
   * @return {string}
   *   The HTML markup for the throbber.
   */
  Drupal.theme.ajaxProgressMessage = (message) =>
    `<span role="status">${message}</span>`;
})(Drupal);
