<?php

namespace Drupal\uc_payment\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler for payment methods.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("uc_payment_method")
 */
class Method extends InOperator {

  /**
   * Overrides InOperator::getValueOptions().
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueOptions = array();

      foreach (uc_payment_method_list() as $id => $method) {
        $this->valueOptions[$id] = $method['name'];
      }
    }
  }

}
