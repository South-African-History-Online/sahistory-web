<?php

namespace Drupal\uc_cart\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Gives customers the option to finish checkout or revise their information.
 */
class CheckoutReviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_checkout_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $order = NULL) {
    if (!$form_state->has('uc_order')) {
      $form_state->set('uc_order', $order);
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['back'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#validate' => array('::skipValidation'),
      '#submit' => array(array($this, 'back')),
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit order'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $order = $form_state->get('uc_order');
    $session = \Drupal::service('session');
    $session->remove('uc_checkout_review_' . $order->id());
    $session->set('uc_checkout_complete_' . $order->id(), TRUE);
    $form_state->setRedirect('uc_cart.checkout_complete');
  }

  /**
   * Ensures no validation is performed for the back button.
   */
  public function skipValidation(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Returns the customer to the checkout page to edit their information.
   */
  public function back(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('uc_cart.checkout');
  }

}
