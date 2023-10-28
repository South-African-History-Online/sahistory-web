<?php

namespace Drupal\uc_payment;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Defines an interface for payment method plugins.
 */
interface PaymentMethodPluginInterface extends PluginInspectionInterface, PluginFormInterface, ConfigurablePluginInterface {

  /**
   * Returns the payment method label with logo.
   *
   * @param string $label
   *   The payment method label to be styled.
   *
   * @return array
   *   A render array containing the formatted payment method label.
   */
  public function getDisplayLabel($label);

  /**
   * Returns the form or render array to be displayed at checkout.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order which is being processed.
   * @param array $form
   *   The checkout form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The checkout form state array.
   *
   * @return array
   *   A form or render array.
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Called when checkout is submitted with this payment method selected.
   *
   * Use this method to process any form elements output by the cartDetails()
   * method.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order which is being processed.
   * @param array $form
   *   The checkout form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The checkout form state array.
   *
   * @return bool
   *   Return FALSE to abort the checkout process, or any other value to
   *   continue the checkout process.
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Returns the payment method title to be used on the checkout review page.
   *
   * @return string
   *   The payment method title.
   */
  public function cartReviewTitle();

  /**
   * Returns the payment method review details.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being processed.
   *
   * @return array
   */
  public function cartReview(OrderInterface $order);

  /**
   * Called when an order is being deleted.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being deleted.
   */
  public function orderDelete(OrderInterface $order);

  /**
   * Called when an order is being edited with this payment method.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being edited.
   *
   * @return array
   *   A form array.
   */
  public function orderEditDetails(OrderInterface $order);

  /**
   * Called when an order is being submitted after being edited.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being edited.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state array.
   *
   * @return array
   *   An array of changes to log against the order.
   */
  public function orderEditProcess(OrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Called when an order is being loaded with this payment method.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being loaded.
   */
  public function orderLoad(OrderInterface $order);

  /**
   * Called when an order is being saved with this payment method.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being saved.
   */
  public function orderSave(OrderInterface $order);

  /**
   * Called when an order is being submitted with this payment method.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being submitted.
   *
   * @return string|null
   *   An error message that can be shown to the user if the payment failed,
   *   or NULL if everything was successful.
   */
  public function orderSubmit(OrderInterface $order);

  /**
   * Called when an order is being viewed by an administrator.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being viewed.
   *
   * @return array
   *   A render array.
   */
  public function orderView(OrderInterface $order);

  /**
   * Called when an order is being viewed by a customer.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being viewed.
   *
   * @return array
   *   A render array.
   */
  public function customerView(OrderInterface $order);

}
