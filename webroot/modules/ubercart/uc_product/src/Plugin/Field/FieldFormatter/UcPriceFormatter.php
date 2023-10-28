<?php

namespace Drupal\uc_product\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the Ubercart price formatter.
 *
 * @FieldFormatter(
 *   id = "uc_price",
 *   label = @Translation("Price"),
 *   field_types = {
 *     "uc_price",
 *   }
 * )
 */
class UcPriceFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => uc_currency_format($item->value));
    }

    return $elements;
  }

}
