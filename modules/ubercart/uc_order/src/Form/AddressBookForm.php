<?php

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Presents previously entered addresses as selectable options.
 */
class AddressBookForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_address_book_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = 0, $type = 'billing', $func = '') {
    $select = uc_select_address($uid, $type, $func);

    if ($uid == 0) {
      $form['desc'] = array(
        '#prefix' => '<br />',
        '#markup' => $this->t('You must select a customer before address<br />information is available.<br />'),
        '#suffix' => '<br />',
      );
    }
    elseif (is_null($select)) {
      $form['desc'] = array(
        '#prefix' => '<br />',
        '#markup' => $this->t('No addresses found for customer.'),
        '#suffix' => '<br />',
      );
    }
    else {
      $form['addresses'] = uc_select_address($uid, $type, $func, $this->t('Select an address'));
// @todo: remove the CSS, put into uc_order.css
      $form['addresses']['#prefix'] = '<div style="float: left; margin-right: 1em;">';
      $form['addresses']['#suffix'] = '</div>';
    }

    // Need to pass along address type selector for use in the JavaScript.
    $form['#attached']['drupalSettings']['addressTypeId'] = '#' . $type . '-address-select';
    $form['close'] = array(
      '#type' => 'button',
      '#value' => $this->t('Close'),
      '#attributes' => array('id' => 'close-address-select'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
