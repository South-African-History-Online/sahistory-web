<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for strtotime.
 *
 * @Tamper(
 *   id = "strtotime",
 *   label = @Translation("String to Unix Timestamp"),
 *   description = @Translation("This will take a string containing an English date format and convert it into a Unix Timestamp."),
 *   category = "Date/time"
 * )
 */
class StrToTime extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }
    return strtotime($data);
  }

}
