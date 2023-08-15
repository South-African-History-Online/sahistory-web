<?php

namespace Drupal\basic_cart\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'addtocart' widget.
 *
 * @FieldWidget(
 *   id = "addtocart",
 *   module = "basic_cart",
 *   label = @Translation("Add to cart"),
 *   field_types = {
 *     "addtocart"
 *   }
 * )
 */
class AddToCartWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element += array(
      '#type' => 'checkbox',
      '#default_value' => $value,
      '#size' => 1,
      '#maxlength' => 1,
    );
    return array('value' => $element);
  }
  
}
