<?php

namespace Drupal\uc_order\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_store\Plugin\views\field\Weight;
use Drupal\views\ResultRow;

/**
 * Total weight field handler.
 *
 * Displays a weight multiplied by the quantity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_order_order_weight_total")
 */
class OrderWeightTotal extends Weight {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $store_config = \Drupal::config('uc_store.settings');
    $options['weight_units'] = array('default' => $store_config->get('weight.units'));
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['weight_units'] = array(
      '#type' => 'select',
      '#title' => $this->t('Unit of measurement'),
      '#default_value' => $this->options['weight_units'],
      '#options' => array(
        'lb' => $this->t('Pounds'),
        'kg' => $this->t('Kilograms'),
        'oz' => $this->t('Ounces'),
        'g' => $this->t('Grams'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $oid = $values->{$this->aliases['order_id']};
    $order = Order::load($oid);
    $total = 0;

    foreach ($order->products as $product) {
      $unit_conversion = uc_weight_conversion($product->weight_units, $this->options['weight_units']);
      $total += $product->qty * $product->weight * $unit_conversion;
    }

    $this->field_alias = 'order_weight';
    $values->{$this->field_alias} = $total;

    if ($this->options['format'] == 'numeric') {
      return parent::render($values);
    }

    if ($this->options['format'] == 'uc_weight') {
      return uc_weight_format($values->{$this->field_alias}, $this->options['weight_units']);
    }
  }

}
