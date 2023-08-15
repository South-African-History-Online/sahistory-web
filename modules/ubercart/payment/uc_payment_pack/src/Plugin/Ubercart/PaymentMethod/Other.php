<?php

namespace Drupal\uc_payment_pack\Plugin\Ubercart\PaymentMethod;

use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines a generic payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "other",
 *   name = @Translation("Other"),
 * )
 */
class Other extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    if ($description = db_query('SELECT description FROM {uc_payment_other} WHERE order_id = :id', [':id' => $order->id()])->fetchField()) {
      return array('#markup' => $this->t('Description: @desc', ['@desc' => $description]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditDetails(OrderInterface $order) {
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => isset($order->payment_details['description']) ? $order->payment_details['description'] : '',
      '#size' => 32,
      '#maxlength' => 64,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function orderLoad(OrderInterface $order) {
    $description = db_query('SELECT description FROM {uc_payment_other} WHERE order_id = :id', [':id' => $order->id()])->fetchField();
    if (isset($description)) {
      $order->payment_details['description'] = $description;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderSave(OrderInterface $order) {
    if (empty($order->payment_details['description'])) {
      db_delete('uc_payment_other')
        ->condition('order_id', $order->id())
        ->execute();
    }
    else {
      db_merge('uc_payment_other')
        ->key(array(
          'order_id' => $order->id(),
        ))
        ->fields(array(
          'description' => $order->payment_details['description'],
        ))
        ->execute();
    }
  }

}
