<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation for html entity encode.
 *
 * @Tamper(
 *   id = "html_entity_encode",
 *   label = @Translation("HTML entity encode"),
 *   description = @Translation("This will convert all HTML special characters such as &gt; and &amp; to &amp;gt; and &amp;apm;."),
 *   category = "Text"
 * )
 */
class HtmlEntityEncode extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    return Html::escape($data);
  }

}
