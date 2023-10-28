<?php

namespace Drupal\uc_store\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\uc_store\Address;
use Drupal\uc_store\AddressInterface;

/**
 * Provides a form element for Ubercart address input.
 *
 * @FormElement("uc_address")
 */
class UcAddress extends Element\FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#required' => TRUE,
      '#process' => array(
        array($class, 'processAddress'),
      ),
      '#attributes' => array('class' => array('uc-store-address-field')),
      '#theme_wrappers' => array('container'),
      '#hidden' => FALSE,
    );
  }

  /**
   * #process callback for address fields.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic input element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processAddress(&$element, FormStateInterface $form_state, &$complete_form) {
    $labels = array(
      'first_name' => t('First name'),
      'last_name' => t('Last name'),
      'company' => t('Company'),
      'street1' => t('Street address'),
      'street2' => ' ',
      'city' => t('City'),
      'zone' => t('State/Province'),
      'country' => t('Country'),
      'postal_code' => t('Postal code'),
      'phone' => t('Phone number'),
      'email' => t('E-mail'),
    );

    $element['#tree'] = TRUE;
    $config = \Drupal::config('uc_store.settings')->get('address_fields');
    $value = $element['#value'];
    $wrapper = Html::getClass('uc-address-' . $element['#name'] . '-zone-wrapper');
    $country_names = \Drupal::service('country_manager')->getEnabledList();

    // Force the selected country to a valid one, so the zone dropdown matches.
    if ($country_keys = array_keys($country_names)) {
      if (isset($value->country) && !isset($country_names[$value->country])) {
        $value->country = $country_keys[0];
      }
    }

    // Iterating on the Address object excludes non-public properties, which
    // is exactly what we want to do.
    $address = Address::create();
    foreach ($address as $field => $field_value) {
      switch ($field) {
        case 'country':
          if ($country_names) {
            $subelement = array(
              '#type' => 'select',
              '#options' => $country_names,
              '#ajax' => array(
                'callback' => array(get_class(), 'updateZone'),
                'wrapper' => $wrapper,
                'progress' => array(
                  'type' => 'throbber',
                ),
              ),
            );
          }
          else {
            $subelement = array(
              '#type' => 'hidden',
              '#required' => FALSE,
            );
          }
          break;

        case 'zone':
          $subelement = array(
            '#prefix' => '<div id="' . $wrapper . '">',
            '#suffix' => '</div>',
          );

          $zones = $value->country ? \Drupal::service('country_manager')->getZoneList($value->country) : [];
          if ($zones) {
            natcasesort($zones);
            $subelement += array(
              '#type' => 'select',
              '#options' => $zones,
              '#empty_value' => '',
              '#after_build' => [[get_class(), 'resetZone']],
            );
          }
          else {
            $subelement += array(
              '#type' => 'hidden',
              '#value' => '',
              '#required' => FALSE,
            );
          }
          break;

        case 'postal_code':
          $subelement = array(
            '#type' => 'textfield',
            '#size' => 10,
            '#maxlength' => 10,
          );
          break;

        case 'phone':
          $subelement = array(
            '#type' => 'tel',
            '#size' => 16,
            '#maxlength' => 32,
          );
          break;

        default:
          $subelement = array(
            '#type' => 'textfield',
            '#size' => 32,
          );
      }

      // Copy JavaScript states from the parent element.
      if (isset($element['#states'])) {
        $subelement['#states'] = $element['#states'];
      }

      // Set common values for all address fields.
      $element[$field] = $subelement + array(
        '#title' => $labels[$field],
        '#default_value' => $value->$field,
        '#access' => !$element['#hidden'] && !empty($config[$field]['status']),
        '#required' => $element['#required'] && !empty($config[$field]['required']),
        '#weight' => isset($config[$field]['weight']) ? $config[$field]['weight'] : 0,
      );
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      return Address::create($input);
    }
    elseif ($element['#default_value'] instanceof AddressInterface) {
      return $element['#default_value'];
    }
    elseif (is_array($element['#default_value'])) {
      // @todo Remove when all callers supply objects.
      return Address::create($element['#default_value']);
    }
    else {
      return Address::create();
    }
  }

  /**
   * Ajax callback: updates the zone select box when the country is changed.
   */
  public static function updateZone($form, FormStateInterface $form_state) {
    $element = &$form;
    $triggering_element = $form_state->getTriggeringElement();
    foreach (array_slice($triggering_element['#array_parents'], 0, -1) as $field) {
      $element = &$element[$field];
    }
    return $element['zone'];
  }

  /**
   * Resets the zone dropdown when the country is changed.
   */
  public static function resetZone($element, FormStateInterface $form_state) {
    if (!isset($element['#options'][$element['#default_value']])) {
      $element['#value'] = $element['#empty_value'];
    }
    return $element;
  }

}
