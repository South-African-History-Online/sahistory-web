<?php

namespace Drupal\uc_payment;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining payment method entities.
 */
interface PaymentMethodInterface extends ConfigEntityInterface {

  /**
   * Returns the weight of this payment method (used for sorting).
   *
   * @return int
   *   The payment method weight.
   */
  public function getWeight();

  /**
   * Determines if this payment method is locked.
   *
   * @return bool
   *   TRUE if the payment method is locked, FALSE otherwise.
   */
  public function isLocked();

  /**
   * Sets the lock status of this payment method.
   *
   * @param bool $locked
   *   TRUE to lock payment method.
   *
   * @return $this
   */
  public function setLocked($locked);

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\uc_payment\PaymentMethodPluginInterface
   *   The plugin instance for this payment method.
   */
  public function getPlugin();

  /**
   * Returns the payment method label with logo.
   *
   * @return string
   *   A string containing the HTML rendered label.
   */
  public function getDisplayLabel();

}
