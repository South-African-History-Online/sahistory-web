/**
 * @file
 * Defines checkbox theme functions.
 * Credit to: Pierre Dureau (pdureau) for the initial code.
 */

((Drupal) => {
  /**
   * Theme function for a checkbox.
   *
   * @return {string}
   *   The HTML markup for the checkbox.
   */
  Drupal.theme.checkbox = () =>
    `<input type="checkbox" class="form-checkbox form-check-input"/>`;
})(Drupal);
