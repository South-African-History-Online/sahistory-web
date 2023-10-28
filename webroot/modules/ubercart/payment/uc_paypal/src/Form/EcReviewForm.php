<?php

namespace Drupal\uc_paypal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Returns the form for the custom Review Payment screen for Express Checkout.
 */
class EcReviewForm extends FormBase {

  /**
   * The order that is being reviewed.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_paypal_ec_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $this->order = $order;
    $form = \Drupal::service('plugin.manager.uc_payment.method')
      ->createFromOrder($this->order)
      ->getExpressReviewForm($form, $form_state, $this->order);

    if (empty($form)) {
      \Drupal::service('session')->set('uc_checkout_review_' . $this->order->id(), TRUE);
      return $this->redirect('uc_cart.checkout_review');
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Continue checkout'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::service('plugin.manager.uc_payment.method')
      ->createFromOrder($this->order)
      ->submitExpressReviewForm($form, $form_state, $this->order);

    \Drupal::service('session')->set('uc_checkout_review_' . $this->order->id(), TRUE);
    $form_state->setRedirect('uc_cart.checkout_review');
  }

}
