<?php

namespace Drupal\uc_payment;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining payment receipt entities.
 */
interface PaymentReceiptInterface extends ContentEntityInterface {

  /**
   * Returns the order that this payment was made for.
   *
   * @return \Drupal\uc_order\OrderInterface
   *   The order entity.
   */
  public function getOrder();

  /**
   * Returns the order ID that this payment was made for.
   *
   * @return int
   *   The order ID.
   */
  public function getOrderId();

  /**
   * Returns the payment method that was used for this payment.
   *
   * @return \Drupal\uc_payment\PaymentMethodInterface
   *   The payment method entity.
   */
  public function getMethod();

  /**
   * Returns the payment method ID that was used for this payment.
   *
   * @return string
   *   The payment method ID.
   */
  public function getMethodId();

  /**
   * Returns the amount that was paid.
   *
   * @return float
   *   The amount.
   */
  public function getAmount();

  /**
   * Returns the user that made the payment.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   */
  public function getUser();

  /**
   * Returns the user ID that made the payment.
   *
   * @return int
   *   The user ID.
   */
  public function getUserId();

  /**
   * Returns the comment attached to the payment.
   *
   * @return string
   *   The comment.
   */
  public function getComment();

  /**
   * Returns the time that the payment was made.
   *
   * @return int
   *   The timestamp.
   */
  public function getReceived();

}
