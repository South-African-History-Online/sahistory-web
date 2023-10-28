<?php

namespace Drupal\uc_store\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for Ubercart price input.
 *
 * @FormElement("uc_price")
 */
class UcPrice extends Element\FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $config = \Drupal::config('uc_store.settings')->get('currency');
    $sign_flag = $config['symbol_after'];
    $currency_sign = $config['symbol'];
    return array(
      '#input' => TRUE,
      '#size' => 15,
      '#maxlength' => 15,
      '#process' => array(
        array($class, 'processAjaxForm'),
      ),
      '#element_validate' => array(
        array($class, 'validatePrice'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderPrice'),
      ),
      '#theme' => 'input__textfield',
      '#theme_wrappers' => array('form_element'),
      '#field_prefix' => $sign_flag ? '' : $currency_sign,
      '#field_suffix' => $sign_flag ? $currency_sign : '',
      '#allow_negative' => FALSE,
      '#empty_zero' => TRUE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE && !empty($element['#default_value'])) {
      $exact = rtrim(rtrim(number_format($element['#default_value'], 6, '.', ''), '0'), '.');
      $round = number_format($element['#default_value'], \Drupal::config('uc_store.settings')->get('currency.precision'), '.', '');
      return $exact == rtrim($round, '0') ? $round : $exact;
    }
    elseif (empty($input) && empty($element['#required']) && !empty($element['#empty_zero'])) {
      // Empty non-required prices should be treated as zero.
      return 0;
    }
  }

  /**
   * Form element validation handler for #type 'uc_price'.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validatePrice(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];
    if ($value === '') {
      return;
    }

    $name = empty($element['#title']) ? $element['#parents'][0] : $element['#title'];

    // Ensure the input is numeric.
    if (!is_numeric($value)) {
      $form_state->setError($element, t('%name must be a number.', ['%name' => $name]));
      return;
    }

    // Ensure that the input is not negative, if specified.
    if (empty($element['#allow_negative']) && $value < 0) {
      $form_state->setError($element, t('%name must not be negative.', ['%name' => $name]));
    }
  }

  /**
   * Prepares a #type 'uc_price' render element for theme_input().
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for theme_input().
   */
  public static function preRenderPrice($element) {
    $element['#attributes']['type'] = 'number';
    $element['#attributes']['step'] = 'any';
    if (empty($element['#allow_negative'])) {
      $element['#attributes']['min'] = 0;
    }
    Element::setAttributes($element, array('id', 'name', 'value', 'size', 'maxlength', 'placeholder'));
    static::setAttributes($element, array('form-uc-price'));

    return $element;
  }

}
