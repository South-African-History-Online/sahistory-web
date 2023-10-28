<?php

namespace Drupal\uc_order\Plugin\views\filter;

use Drupal\uc_order\Entity\OrderStatus;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler for order statuses.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("uc_order_status")
 */
class Status extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueTitle = $this->t('Order status');
      $this->valueOptions = array_merge(['_active' => $this->t('Active')], OrderStatus::getOptionsList());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (is_array($this->value) && in_array('_active', $this->value)) {
      $active = \Drupal::entityQuery('uc_order_status')
        ->condition('weight', 0, '>=')
        ->execute();
      $this->value = array_merge($this->value, $active);
    }

    parent::query();
  }

}
