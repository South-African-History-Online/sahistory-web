<?php

namespace Drupal\uc_product\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the Ubercart price widget.
 *
 * @FieldWidget(
 *   id = "uc_price",
 *   label = @Translation("Price"),
 *   field_types = {
 *     "uc_price",
 *   }
 * )
 */
class UcPriceWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : NULL;

    $element += array(
      '#type' => 'uc_price',
      '#default_value' => $value,
      '#empty_zero' => FALSE,
    );

    return array('value' => $element);
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element['value'];
  }

}
