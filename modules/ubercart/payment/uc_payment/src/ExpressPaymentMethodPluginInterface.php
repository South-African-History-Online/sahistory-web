<?php

namespace Drupal\uc_payment;

/**
 * Defines an interface for payment methods that bypass standard checkout.
 */
interface ExpressPaymentMethodPluginInterface extends PaymentMethodPluginInterface {

  /**
   * Form constructor.
   *
   * @return array
   *   A Form API button element that will bypass standard checkout.
   */
  public function getExpressButton($method_id);

}
