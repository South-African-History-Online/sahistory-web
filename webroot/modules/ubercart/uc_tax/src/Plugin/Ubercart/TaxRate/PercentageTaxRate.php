<?php

namespace Drupal\uc_tax\Plugin\Ubercart\TaxRate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_tax\TaxRatePluginBase;

/**
 * Defines the fixed percentage tax rate.
 *
 * @UbercartTaxRate(
 *   id = "percentage_rate",
 *   label = @Translation("Percentage rate"),
 *   weight = 1,
 * )
 */
class PercentageTaxRate extends TaxRatePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'rate' => 0,
      'jurisdiction' => '',
      'field' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $fields = ['' => $this->t('- None -')];
    $result = \Drupal::entityQuery('field_config')
      ->condition('field_type', 'number')
      ->execute();
    foreach (FieldConfig::loadMultiple($result) as $field) {
      $fields[$field->getName()] = $field->label();
    }

    $form['rate'] = array(
      '#type' => 'number',
      '#title' => $this->t('Default tax rate'),
      '#min' => 0,
      '#step' => 'any',
      '#description' => $this->t('The percentage of the item price to add to the shipping cost for an item.'),
      '#default_value' => $this->configuration['rate'],
      '#field_suffix' => $this->t('% (percent)'),
      '#required' => TRUE,
    );
    $form['jurisdiction'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Jurisdiction'),
      '#description' => $this->t('Administrative label for the taxing authority, used to prepare reports of collected taxes.'),
      '#default_value' => $this->configuration['jurisdiction'],
      '#required' => FALSE,
    );

    $form['field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Tax rate override field'),
      '#description' => $this->t('Overrides the default percentage tax rate for a product, when the field is attached to a product content type and has a value.'),
      '#options' => $fields,
      '#default_value' => $this->configuration['field'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['rate'] = $form_state->getValue('rate');
    $this->configuration['field'] = $form_state->getValue('field');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return $this->t('Rate: @rate%', ['@rate' => $this->configuration['rate']]);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateTax(OrderInterface $order) {
    $rate = $this->configuration['rate'];
    $jurisdiction = $this->configuration['jurisdiction'];
    $field = $this->configuration['field'];

    foreach ($order->products as $product) {
      if (isset($product->nid->entity->$field->value)) {
        $product_rate = $product->nid->entity->$field->value * $product->qty->value;
      }
      else {
        $product_rate = $this->configuration['rate'] * $product->qty->value;
      }

      $rate += $product->price->value * floatval($product_rate) / 100;
    }

    return [$rate];
  }

}
