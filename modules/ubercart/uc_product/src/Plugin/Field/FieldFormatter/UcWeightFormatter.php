<?php

namespace Drupal\uc_product\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the Ubercart 'uc_weight' formatter.
 *
 * @FieldFormatter(
 *   id = "uc_weight",
 *   label = @Translation("Weight"),
 *   field_types = {
 *     "uc_weight",
 *   }
 * )
 */
class UcWeightFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if ((float) $item->value) {
        $elements[$delta] = array('#markup' => uc_weight_format($item->value, $item->units));
      }
    }

    return $elements;
  }

}
