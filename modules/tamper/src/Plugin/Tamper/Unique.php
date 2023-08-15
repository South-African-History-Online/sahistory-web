<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for unique tamper.
 *
 * @Tamper(
 *   id = "unique",
 *   label = @Translation("Unique"),
 *   description = @Translation("Makes the elements in a multivalued field unique."),
 *   category = "List",
 *   handle_multiples = TRUE
 * )
 */
class Unique extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_array($data)) {
      throw new TamperException('Input should be an array.');
    }
    return array_values(array_unique($data));
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
