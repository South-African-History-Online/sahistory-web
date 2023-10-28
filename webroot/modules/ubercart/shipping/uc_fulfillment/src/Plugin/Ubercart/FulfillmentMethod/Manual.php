<?php

namespace Drupal\uc_fulfillment\Plugin\Ubercart\FulfillmentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_fulfillment\Package;
use Drupal\uc_fulfillment\Shipment;
use Drupal\uc_fulfillment\FulfillmentMethodPluginBase;

/**
 * Provides a manual fulfillment plugin.
 *
 * @UbercartFulfillmentMethod(
 *   id = "manual",
 *   admin_label = @Translation("Ship Manually"),
 *   no_ui = TRUE
 * )
 */
class Manual extends FulfillmentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'base_rate' => 0,
      'product_rate' => 0,
      'field' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $fields = ['' => $this->t('- None -')];
    $result = \Drupal::entityQuery('field_config')
      ->condition('field_type', 'uc_price')
      ->execute();
    foreach (FieldConfig::loadMultiple($result) as $field) {
      $fields[$field->getName()] = $field->label();
    }

    $form['base_rate'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Base price'),
      '#description' => $this->t('The starting price for shipping costs.'),
      '#default_value' => $this->configuration['base_rate'],
      '#required' => TRUE,
    );
    $form['product_rate'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Default product shipping rate'),
      '#description' => $this->t('Additional shipping cost per product in cart.'),
      '#default_value' => $this->configuration['product_rate'],
      '#required' => TRUE,
    );
    $form['field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Product shipping rate override field'),
      '#description' => $this->t('Overrides the default shipping rate per product for this flat rate shipping method, when the field is attached to a product content type and has a value.'),
      '#options' => $fields,
      '#default_value' => $this->configuration['field'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['base_rate'] = $form_state->getValue('base_rate');
    $this->configuration['product_rate'] = $form_state->getValue('product_rate');
    $this->configuration['field'] = $form_state->getValue('field');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('No specific carrier, no pre-paid shipping labels');
  }

  /**
   * {@inheritdoc}
   */
  public function fulfillOrder(OrderInterface $order, array $package_ids) {
    $shipment = Shipment::create();
    $shipment->setOrderId($order->id());
    $packages = array();
    foreach ($package_ids as $id) {
      $package = Package::load($id);
      $packages[$id] = $package;
    }
    $shipment->setPackages($packages);

    return \Drupal::formBuilder()->getForm('\Drupal\uc_fulfillment\Form\ShipmentEditForm', $order, $shipment);
  }

}
