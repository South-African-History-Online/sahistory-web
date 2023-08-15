<?php

namespace Drupal\uc_product\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the Ubercart dimensions formatter.
 *
 * @FieldFormatter(
 *   id = "uc_dimensions",
 *   label = @Translation("Dimensions"),
 *   field_types = {
 *     "uc_dimensions",
 *   }
 * )
 */
class UcDimensionsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $dimensions = [];
      foreach (['length', 'width', 'height'] as $dimension) {
        if ((float) $item->$dimension) {
          $dimensions[] = uc_length_format($item->$dimension, $item->units);
        }
      }
      if ($dimensions) {
        $elements[$delta] = array('#markup' => implode(' × ', $dimensions));
      }
    }

    return $elements;
  }

}
