<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines the attribute option add/edit edit form.
 */
abstract class OptionFormBase extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_attribute_option_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $aid = NULL) {
    $form['aid'] = array('#type' => 'value', '#value' => $aid);

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('This name will appear to customers on product add to cart forms.'),
      '#default_value' => '',
      '#required' => TRUE,
      '#weight' => 0,
    );
    $form['ordering'] = array(
      '#type' => 'weight',
      '#delta' => 50,
      '#title' => $this->t('List position'),
      '#description' => $this->t('Options will be listed sorted by this value and then by their name.<br />May be overridden at the product level.'),
      '#default_value' => 0,
      '#weight' => 4,
    );
    $form['adjustments'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Default adjustments'),
      '#description' => $this->t('Enter a positive or negative value for each adjustment applied when this option is selected.<br />Any of these may be overriden at the product level.'),
      '#weight' => 8,
    );
    $form['adjustments']['cost'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Cost'),
      '#default_value' => 0,
      '#weight' => 1,
      '#allow_negative' => TRUE,
    );
    $form['adjustments']['price'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Price'),
      '#default_value' => 0,
      '#weight' => 2,
      '#allow_negative' => TRUE,
    );
    $form['adjustments']['weight'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Weight'),
      '#default_value' => 0,
      '#weight' => 3,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => 10,
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('uc_attribute.options', ['aid' => $aid]),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $pattern = '/^-?\d*(\.\d*)?$/';
    if (!is_numeric($form_state->getValue('weight')) && !preg_match($pattern, $form_state->getValue('weight'))) {
      $form_state->setErrorByName('weight', $this->t('This must be in a valid number format. No commas and only one decimal point.'));
    }
  }

}
