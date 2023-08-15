<?php

namespace Drupal\uc_store\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for Ubercart quantity input.
 *
 * @FormElement("uc_quantity")
 */
class UcQuantity extends Element\FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#size' => 5,
      '#maxlength' => 6,
      '#process' => array(
        array($class, 'processAjaxForm'),
      ),
      '#element_validate' => array(
        array($class, 'validateQuantity'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderQuantity'),
      ),
      '#theme' => 'input__textfield',
      '#theme_wrappers' => array('form_element'),
      '#allow_zero' => FALSE,
    );
  }

  /**
   * Form element validation handler for #type 'uc_quantity'.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validateQuantity(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!preg_match('/^\d+$/', $element['#value'])) {
      $form_state->setError($element, t('The quantity must be an integer.'));
    }
    elseif (empty($element['#allow_zero']) && !$element['#value']) {
      $form_state->setError($element, t('The quantity cannot be zero.'));
    }
  }

  /**
   * Prepares a #type 'uc_quantity' render element for theme_input().
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #min, #max, #step, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for theme_input().
   */
  public static function preRenderQuantity($element) {
    $element['#attributes']['type'] = 'number';
    $element['#attributes']['min'] = 0;
    $element['#attributes']['step'] = 1;
    Element::setAttributes($element, array('id', 'name', 'value', 'size', 'maxlength', 'placeholder', 'min', 'max', 'step'));
    static::setAttributes($element, array('form-uc-quantity'));

    return $element;
  }

}
