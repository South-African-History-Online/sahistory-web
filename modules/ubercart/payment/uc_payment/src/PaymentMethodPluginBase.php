<?php

namespace Drupal\uc_payment;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Defines a base payment method plugin implementation.
 */
abstract class PaymentMethodPluginBase extends PluginBase implements PaymentMethodPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration += $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label) {
    return ['#plain_text' => $label];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReviewTitle() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function cartReview(OrderInterface $order) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function orderDelete(OrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditDetails(OrderInterface $order) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function orderLoad(OrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderSave(OrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(OrderInterface $order) {
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function customerView(OrderInterface $order) {
    return $this->orderView($order);
  }

}
