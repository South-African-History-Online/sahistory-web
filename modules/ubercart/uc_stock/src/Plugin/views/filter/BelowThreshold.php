<?php

namespace Drupal\uc_stock\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;


/**
 * Filters nodes based on comparison of stock value to stock threshold.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("uc_stock_below_threshold")
 */
class BelowThreshold extends BooleanOperator {

  /**
   * {@inheritdoc}
   */
  function query() {
    $this->ensure_my_table();
    $this->query->add_where_expression($this->options['group'], "$this->table_alias.stock " . (empty($this->value) ? '>=' : '<') . " $this->table_alias.threshold");
  }
}
