<?php

namespace Drupal\uc_order\Plugin\views\field;

use Drupal\uc_order\Entity\Order;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to provide action icons.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_order_actions")
 */
class Actions extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $order = Order::load($this->getValue($values));
    return uc_order_actions($order, TRUE);
  }

}
