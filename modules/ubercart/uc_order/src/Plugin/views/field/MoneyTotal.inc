<?php

namespace Drupal\uc_order\Plugin\views\field;

/**
 * Total price field handler.
 *
 * Displays a price multiplied by the quantity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_order_actions")
 */
class MoneyTotal extends MoneyAmount {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensure_my_table();

    $table = $this->table_alias;
    $field = $this->real_field;
    $params = $this->options['group_type'] != 'group' ? array('function' => $this->options['group_type']) : array();
    $this->field_alias = $this->query->add_field(NULL, "$table.$field * $table.qty", $this->table . '_' . $this->field, $params);
  }

}
