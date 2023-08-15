<?php

/**
 * @file
 * Hooks provided by the Store module.
 */

use Drupal\Core\Render\Element;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows modules to modify forms before Drupal invokes hook_form_alter().
 *
 * This hook will normally be used by core modules so any form modifications
 * they make can be further modified by contrib modules using a normal
 * hook_form_alter(). At this point, drupal_prepare_form() has not been called,
 * so none of the automatic form data (e.g.: #parameters, #build_id, etc.) has
 * been added yet.
 *
 * @see hook_form_alter()
 */
function hook_uc_form_alter(&$form, &$form_state, $form_id) {
  // If the node has a product list, add attributes to them
  if (isset($form['products']) && count(Element::children($form['products']))) {
    foreach (Element::children($form['products']) as $key) {
      $form['products'][$key]['attributes'] = _uc_attribute_alter_form(node_load($key));
      if (is_array($form['products'][$key]['attributes'])) {
        $form['products'][$key]['attributes']['#tree'] = TRUE;
        $form['products'][$key]['#type'] = 'details';
      }
    }
  }
  // If not, add attributes to the node.
  else {
    $form['attributes'] = _uc_attribute_alter_form($node);

    if (is_array($form['attributes'])) {
      $form['attributes']['#tree'] = TRUE;
      $form['attributes']['#weight'] = -1;
    }
  }
}

/**
 * Adds status messages to the "Store administration" page.
 *
 * This hook is used to add items to the store status table on the main store
 * administration screen. Each item gets a row in the table that consists of a
 * status icon, title, and description. These items should be used to give
 * special instructions, notifications, or indicators for components of the cart
 * enabled by the modules. At a glance, a store owner should be able to look
 * here and see if a critical component of your module is not functioning
 * properly.
 *
 * For example, if the catalog module is installed and it cannot find the
 * catalog taxonomy vocabulary, it will show an error message here to alert the
 * store administrator.
 *
 * @return
 *   An array of store status items which are arrays with the following keys:
 *   - status: "ok", "warning", or "error" depending on the message.
 *   - title: The title of the status message or module that defines it.
 *   - desc: The description; can be any message, including links to pages and
 *     forms that deal with the issue being reported.
 */
function hook_uc_store_status() {
  if ($key = uc_credit_encryption_key()) {
    $statuses[] = array(
      'status' => 'ok',
      'title' => t('Credit card encryption'),
      'desc' => t('Credit card data in the database is currently being encrypted.'),
    );
  }
  return $statuses;
}

/**
 * @} End of "addtogroup hooks".
 */
