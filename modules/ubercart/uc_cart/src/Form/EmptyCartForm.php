<?php

namespace Drupal\uc_cart\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Confirm that the customer wants to empty their cart.
 */
class EmptyCartForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to empty your shopping cart?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('uc_cart.cart');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_empty_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::service('uc_cart.manager')->emptyCart();
    $form_state->setRedirect('uc_cart.cart');
  }

}
