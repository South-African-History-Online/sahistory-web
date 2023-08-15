<?php

namespace Drupal\uc_payment\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines a free order payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "free_order",
 *   name = @Translation("Free order"),
 *   no_ui = TRUE
 * )
 */
class FreeOrder extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    return array(
      '#markup' => $this->t('Continue with checkout to complete your order.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(OrderInterface $order) {
    if ($order->getTotal() >= 0.01) {
      return $this->t('We cannot process your order without payment.');
    }

    uc_payment_enter($order->id(), 'free_order', 0, 0, NULL, $this->t('Checkout completed for a free order.'));
  }

}
