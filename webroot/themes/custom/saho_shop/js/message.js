/**
 * @file
 * message.js
 * Credit to: Pierre Dureau (pdureau) for the initial code.
 */
((Drupal) => {
  /**
   * Helper function to map Drupal types to Bootstrap classes.
   *
   * @return {Object<String, String>}
   *   A map of classes, keyed by message type.
   */
  Drupal.Message.getMessageTypeClasses = () => {
    return {
      status: 'success',
      error: 'danger',
      warning: 'warning',
      info: 'info',
    };
  };

  /**
   * Retrieves the classes for a specific message type.
   *
   * @param {String} type
   *   The type of message.
   *
   * @return {String}
   *   The classes to add, space separated.
   */
  Drupal.Message.getMessageTypeClass = (type) => {
    const classes = Drupal.Message.getMessageTypeClasses();
    return `alert alert-${classes[type] || 'success'}`;
  };

  /**
   * @inheritDoc
   */
  Drupal.Message.getMessageTypeLabels = () => {
    return {
      status: Drupal.t('Status message'),
      error: Drupal.t('Error message'),
      warning: Drupal.t('Warning message'),
      info: Drupal.t('Informative message'),
    };
  };

  /**
   * Retrieves a label for a specific message type.
   *
   * @param {String} type
   *   The type of message.
   *
   * @return {String}
   *   The message type label.
   */
  Drupal.Message.getMessageTypeLabel = (type) => {
    const labels = Drupal.Message.getMessageTypeLabels();
    return labels[type];
  };

  /**
   * Map of the message type aria-role values.
   *
   * @return {Object<String, String>}
   *   A map of roles, keyed by message type.
   */
  Drupal.Message.getMessageTypeRoles = () => {
    return {
      status: 'status',
      error: 'alert',
      warning: 'alert',
      info: 'status',
    };
  };

  /**
   * Retrieves the aria-role for a specific message type.
   *
   * @param {String} type
   *   The type of message.
   *
   * @return {String}
   *   The message type role.
   */
  Drupal.Message.getMessageTypeRole = (type) => {
    const labels = Drupal.Message.getMessageTypeRoles();
    return labels[type];
  };

  /**
   * @inheritDoc
   */
  Drupal.theme.message = (message, options) => {
    // @todo use the pattern alert directly if possible in JS.
    options = options || {};
    const wrapper = Drupal.theme(
      'messageWrapper',
      options.id || new Date().getTime(),
      options.type || 'status',
    );

    if (options.dismissible === undefined || !!options.dismissible) {
      wrapper.classList.add('alert-dismissible', 'fade', 'show');
      wrapper.appendChild(Drupal.theme('messageClose'));
    }
    wrapper.innerHTML += message && message.text;

    return wrapper;
  };

  /**
   * Themes the message container.
   *
   * @param {String} id
   *   The message identifier.
   * @param {String} type
   *   The type of message.
   *
   * @return {HTMLElement}
   *   A constructed HTMLElement.
   */
  Drupal.theme.messageWrapper = (id, type) => {
    const wrapper = document.createElement('div');
    const label = Drupal.Message.getMessageTypeLabel(type);
    wrapper.setAttribute('class', Drupal.Message.getMessageTypeClass(type));
    wrapper.setAttribute('role', Drupal.Message.getMessageTypeRole(type));
    wrapper.setAttribute('aria-label', label);
    wrapper.setAttribute('data-drupal-message-id', id);
    wrapper.setAttribute('data-drupal-message-type', type);
    if (label) {
      wrapper.appendChild(Drupal.theme('messageLabel', label));
    }
    return wrapper;
  };

  /**
   * Themes the message close button.
   *
   * @return {HTMLElement}
   *   A constructed HTMLElement.
   */
  Drupal.theme.messageClose = () => {
    // @todo use the pattern button_close directly if possible in JS.
    const element = document.createElement('button');
    element.setAttribute('class', 'btn-close');
    element.setAttribute('type', 'button');
    element.setAttribute('role', 'button');
    element.setAttribute('data-bs-dismiss', 'alert');
    element.setAttribute('aria-label', Drupal.t('Close'));
    return element;
  };

  /**
   * Themes the message container.
   *
   * @param {String} label
   *   The message label.
   *
   * @return {HTMLElement}
   *   A constructed HTMLElement.
   */
  Drupal.theme.messageLabel = (label) => {
    const element = document.createElement('h4');
    element.setAttribute('class', 'visually-hidden');
    element.innerHTML = label;
    return element;
  };
})(Drupal);
