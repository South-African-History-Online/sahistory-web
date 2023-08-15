<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for casting to integer.
 *
 * @Tamper(
 *   id = "cast_to_int",
 *   label = @Translation("Cast to integer"),
 *   description = @Translation("This plugin will convert any value to its integer form."),
 *   category = "Text"
 * )
 */
class CastToInt extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    return (int) $data;
  }

}
