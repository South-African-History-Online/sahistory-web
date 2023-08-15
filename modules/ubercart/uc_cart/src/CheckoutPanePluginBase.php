<?php

namespace Drupal\uc_cart;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Defines a base checkout pane plugin implementation.
 */
abstract class CheckoutPanePluginBase extends PluginBase implements CheckoutPanePluginInterface {

  /**
   * Whether the pane is enabled or not.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * The weight of the checkout pane.
   *
   * @var int|string
   */
  protected $weight = '';

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array(
      'status' => $this->isEnabled(),
      'weight' => $this->getWeight(),
      'settings' => $this->configuration,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += array(
      'status' => $this->pluginDefinition['status'],
      'weight' => $this->pluginDefinition['weight'],
      'settings' => array(),
    );
    $this->status = $configuration['status'];
    $this->weight = $configuration['weight'];
    $this->configuration = $configuration['settings'] + $this->defaultConfiguration();
    return $this;
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
  public function prepare(OrderInterface $order, array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order, array $form, FormStateInterface $form_state) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return (string) $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}
