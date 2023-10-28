<?php

namespace Drupal\uc_quote\Plugin\Ubercart\ShippingQuote;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_quote\ShippingQuotePluginBase;

/**
 * Assigns a shipping rate to products based on weight.
 *
 * @UbercartShippingQuote(
 *   id = "weightquote",
 *   admin_label = @Translation("Weight quote")
 * )
 */
class WeightQuote extends ShippingQuotePluginBase {

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

    $unit = \Drupal::config('uc_store.settings')->get('weight.units');
    $form['base_rate'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Base price'),
      '#description' => $this->t('The starting price for weight-based shipping costs.'),
      '#default_value' => $this->configuration['base_rate'],
      '#required' => TRUE,
    );
    $form['product_rate'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Default cost adjustment per @unit', ['@unit' => $unit]),
      '#description' => $this->t('The amount per weight unit to add to the shipping cost for an item.'),
      '#default_value' => $this->configuration['product_rate'],
      '#field_suffix' => $this->t('per @unit', ['@unit' => $unit]),
      '#required' => TRUE,
    );
    $form['field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Product shipping rate override field'),
      '#description' => $this->t('Overrides the default shipping rate per @unit for this shipping method, when the field is attached to a product content type and has a value.', ['@unit' => $unit]),
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
    return $this->t('@base_rate + @product_rate per @unit', ['@base_rate' => uc_currency_format($this->configuration['base_rate']), '@product_rate' => uc_currency_format($this->configuration['product_rate']), '@unit' => \Drupal::config('uc_store.settings')->get('weight.units')]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuotes(OrderInterface $order) {
    $rate = $this->configuration['base_rate'];
    $field = $this->configuration['field'];

    foreach ($order->products as $product) {
      if (isset($product->nid->entity->$field->value)) {
        $product_rate = $product->nid->entity->$field->value * $product->qty->value;
      }
      else {
        $product_rate = $this->configuration['product_rate'] * $product->qty->value;
      }

      $rate += $product_rate  * $product->weight->value * uc_weight_conversion($product->weight->units);
    }

    return [$rate];
  }

}
