<?php

namespace Drupal\uc_payment;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Defines an interface for payment method plugins that redirect off-site.
 */
interface OffsitePaymentMethodPluginInterface extends PaymentMethodPluginInterface {

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being processed.
   *
   * @return array
   *   The form structure.
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, OrderInterface $order);

}
