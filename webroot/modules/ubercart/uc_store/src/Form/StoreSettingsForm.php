<?php

namespace Drupal\uc_store\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure store settings for this site.
 */
class StoreSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_store_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_store.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('uc_store.settings');

    $form['store'] = array('#type' => 'vertical_tabs');

    $form['basic'] = array(
      '#type' => 'details',
      '#title' => $this->t('Basic information'),
      '#group' => 'store',
    );
    $form['basic']['uc_store_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Store name'),
      '#default_value' => uc_store_name(),
    );
    $form['basic']['uc_store_email'] = array(
      '#type' => 'email',
      '#title' => $this->t('E-mail address'),
      '#size' => 32,
      '#required' => TRUE,
      '#default_value' => uc_store_email(),
    );
    $form['basic']['uc_store_email_include_name'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include the store name in the "From" line of store e-mails.'),
      '#description' => $this->t('May not be available on all server configurations. Turn off if this causes problems.'),
      '#default_value' => $config->get('mail_include_name'),
    );
    $form['basic']['uc_store_phone'] = array(
      '#type' => 'tel',
      '#title' => $this->t('Phone number'),
      '#default_value' => $config->get('phone'),
    );
    $form['basic']['uc_store_fax'] = array(
      '#type' => 'tel',
      '#title' => $this->t('Fax number'),
      '#default_value' => $config->get('fax'),
    );
    $form['basic']['uc_store_help_page'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Store help page'),
      '#description' => $this->t('The Drupal page for the store help link.'),
      '#default_value' => $config->get('help_page'),
      '#size' => 32,
      '#field_prefix' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
    );

    $form['address'] = array(
      '#type' => 'details',
      '#title' => $this->t('Store address'),
      '#group' => 'store',
    );
    $form['address']['address'] = array(
      '#type' => 'uc_address',
      '#default_value' => $config->get('address'),
      '#required' => FALSE,
    );

    $form['currency'] = array(
      '#type' => 'details',
      '#title' => $this->t('Currency format'),
      '#group' => 'store',
    );
    $form['currency']['uc_currency_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Currency code'),
      '#description' => $this->t('While not used directly in formatting, the currency code is used by other modules as the primary currency for your site.  Enter here your three character <a href=":url">ISO 4217</a> currency code.', [':url' => Url::fromUri('http://en.wikipedia.org/wiki/ISO_4217#Active_codes')->toString()]),
      '#default_value' => $config->get('currency.code'),
      '#maxlength' => 3,
      '#size' => 5,
    );
    $form['currency']['example'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Current format'),
      '#value' => uc_currency_format(1000.1234),
      '#disabled' => TRUE,
      '#size' => 10,
    );
    $form['currency']['uc_currency_sign'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Currency sign'),
      '#default_value' => $config->get('currency.symbol'),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['currency']['uc_sign_after_amount'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display currency sign after amount.'),
      '#default_value' => $config->get('currency.symbol_after'),
    );
    $form['currency']['uc_currency_thou'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Thousands marker'),
      '#default_value' => $config->get('currency.thousands_marker'),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['currency']['uc_currency_dec'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Decimal marker'),
      '#default_value' => $config->get('currency.decimal_marker'),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['currency']['uc_currency_prec'] = array(
      '#type' => 'select',
      '#title' => $this->t('Number of decimal places'),
      '#options' => array(0 => 0, 1 => 1, 2 => 2),
      '#default_value' => $config->get('currency.precision'),
    );

    $form['weight'] = array(
      '#type' => 'details',
      '#title' => $this->t('Weight format'),
      '#group' => 'store',
    );
    $form['weight']['uc_weight_unit'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default weight units'),
      '#default_value' => $config->get('weight.units'),
      '#options' => array(
        'lb' => $this->t('Pounds'),
        'oz' => $this->t('Ounces'),
        'kg' => $this->t('Kilograms'),
        'g' => $this->t('Grams'),
      ),
    );
    $form['weight']['uc_weight_thou'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Thousands marker'),
      '#default_value' => $config->get('weight.thousands_marker'),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['weight']['uc_weight_dec'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Decimal marker'),
      '#default_value' => $config->get('weight.decimal_marker'),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['weight']['uc_weight_prec'] = array(
      '#type' => 'select',
      '#title' => $this->t('Number of decimal places'),
      '#options' => array(0 => 0, 1 => 1, 2 => 2),
      '#default_value' => $config->get('weight.precision'),
    );

    $form['length'] = array(
      '#type' => 'details',
      '#title' => $this->t('Length format'),
      '#group' => 'store',
    );
    $form['length']['uc_length_unit'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default length units'),
      '#default_value' => $config->get('length.units'),
      '#options' => array(
        'in' => $this->t('Inches'),
        'ft' => $this->t('Feet'),
        'cm' => $this->t('Centimeters'),
        'mm' => $this->t('Millimeters'),
      ),
    );
    $form['length']['uc_length_thou'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Thousands marker'),
      '#default_value' => $config->get('length.thousands_marker'),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['length']['uc_length_dec'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Decimal marker'),
      '#default_value' => $config->get('length.decimal_marker'),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['length']['uc_length_prec'] = array(
      '#type' => 'select',
      '#title' => $this->t('Number of decimal places'),
      '#options' => array(0 => 0, 1 => 1, 2 => 2),
      '#default_value' => $config->get('length.precision'),
    );

    $form['display'] = array(
      '#type' => 'details',
      '#title' => $this->t('Display settings'),
      '#group' => 'store',
    );
    $form['display']['uc_customer_list_address'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Primary customer address'),
      '#description' => $this->t('Select the address to be used on customer lists and summaries.'),
      '#options' => array(
        'billing' => $this->t('Billing address'),
        'shipping' => $this->t('Shipping address'),
      ),
      '#default_value' => $config->get('customer_address'),
    );
    $form['display']['uc_order_capitalize_addresses'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Capitalize address on order screens'),
      '#default_value' => $config->get('capitalize_address'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('uc_store.settings')
      ->set('name', $form_state->getValue('uc_store_name'))
      ->set('mail', $form_state->getValue('uc_store_email'))
      ->set('mail_include_name', $form_state->getValue('uc_store_email_include_name'))
      ->set('phone', $form_state->getValue('uc_store_phone'))
      ->set('fax', $form_state->getValue('uc_store_fax'))
      ->set('help_page', $form_state->getValue('uc_store_help_page'))
      ->set('address', $form_state->getValue('address'))
      ->set('currency.code', $form_state->getValue('uc_currency_code'))
      ->set('currency.symbol', $form_state->getValue('uc_currency_sign'))
      ->set('currency.symbol_after', $form_state->getValue('uc_sign_after_amount'))
      ->set('currency.thousands_marker', $form_state->getValue('uc_currency_thou'))
      ->set('currency.decimal_marker', $form_state->getValue('uc_currency_dec'))
      ->set('currency.precision', $form_state->getValue('uc_currency_prec'))
      ->set('weight.units', $form_state->getValue('uc_weight_unit'))
      ->set('weight.thousands_marker', $form_state->getValue('uc_weight_thou'))
      ->set('weight.decimal_marker', $form_state->getValue('uc_weight_dec'))
      ->set('weight.precision', $form_state->getValue('uc_weight_prec'))
      ->set('length.units', $form_state->getValue('uc_length_unit'))
      ->set('length.thousands_marker', $form_state->getValue('uc_length_thou'))
      ->set('length.decimal_marker', $form_state->getValue('uc_length_dec'))
      ->set('length.precision', $form_state->getValue('uc_length_prec'))
      ->set('customer_address', $form_state->getValue('uc_customer_list_address'))
      ->set('capitalize_address', $form_state->getValue('uc_order_capitalize_addresses'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
