<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for filtering data.
 *
 * @Tamper(
 *   id = "array_filter",
 *   label = @Translation("Filter items"),
 *   description = @Translation("Filter empty items from a list."),
 *   category = "List",
 *   handle_multiples = TRUE
 * )
 */
class ArrayFilter extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_array($data)) {
      throw new TamperException('Input should be an array.');
    }
    return array_values(array_filter($data));
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
