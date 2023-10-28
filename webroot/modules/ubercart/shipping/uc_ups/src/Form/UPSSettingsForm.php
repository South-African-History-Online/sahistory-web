<?php

namespace Drupal\uc_ups\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_ups\UPSUtilities;

/**
 * Configures UPS settings.
 *
 * Records UPS account information necessary to use the service. Allows testing
 * or production mode. Configures which UPS services are quoted to customers.
 */
class UPSSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_ups_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_ups.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $ups_config = $this->config('uc_ups.settings');

    // Put fieldsets into vertical tabs
    $form['ups-settings'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'library' => array(
          'uc_ups/uc_ups.scripts',
        ),
      ),
    );

    // Container for credential forms
    $form['credentials'] = array(
      '#type'          => 'details',
      '#title'         => $this->t('Credentials'),
      '#description'   => $this->t('Account number and authorization information.'),
      '#group'         => 'ups-settings',
    );

    $form['credentials']['access_license'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('UPS OnLine Tools XML Access Key'),
      '#default_value' => $ups_config->get('access_license'),
      '#required' => TRUE,
    );
    $form['credentials']['shipper_number'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('UPS Shipper #'),
      '#description' => $this->t('The 6-character string identifying your UPS account as a shipper.'),
      '#default_value' => $ups_config->get('shipper_number'),
      '#required' => TRUE,
    );
    $form['credentials']['user_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('UPS.com user ID'),
      '#default_value' => $ups_config->get('user_id'),
      '#required' => TRUE,
    );
    $form['credentials']['password'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $ups_config->get('password'),
    );
    $form['credentials']['connection_address'] = array(
      '#type' => 'select',
      '#title' => $this->t('Server mode'),
      '#description' => $this->t('Use the Testing server while developing and configuring your site. Switch to the Production server only after you have demonstrated that transactions on the Testing server are working and you are ready to go live.'),
      '#options' => array(
        'https://wwwcie.ups.com/ups.app/xml/' => $this->t('Testing'),
        'https://onlinetools.ups.com/ups.app/xml/' => $this->t('Production'),
      ),
      '#default_value' => $ups_config->get('connection_address'),
    );

    $form['services'] = array(
      '#type' => 'details',
      '#title' => $this->t('Service options'),
      '#description' => $this->t('Set the conditions that will return a UPS quote.'),
      '#group'         => 'ups-settings',
    );

    $form['services']['uc_ups_services'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('UPS services'),
      '#default_value' => $ups_config->get('services'),
      '#options' => UPSUtilities::services(),
      '#description' => $this->t('Select the UPS services that are available to customers.'),
    );

    // Container for quote options
    $form['quote_options'] = array(
      '#type'          => 'details',
      '#title'         => $this->t('Quote options'),
      '#description'   => $this->t('Preferences that affect computation of quote.'),
      '#group'         => 'ups-settings',
    );

    $form['quote_options']['all_in_one'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Product packages'),
      '#default_value' => $ups_config->get('all_in_one'),
      '#options' => array(
        0 => $this->t('Each product in its own package'),
        1 => $this->t('All products in one package'),
      ),
      '#description' => $this->t('Indicate whether each product is quoted as shipping separately or all in one package. Orders with one kind of product will still use the package quantity to determine the number of packages needed, however.'),
    );

    // Form to select package types
    $form['quote_options']['package_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default Package Type'),
      '#default_value' => $ups_config->get('package_type'),
      '#options' => UPSUtilities::packageTypes(),
      '#description' => $this->t('Type of packaging to be used.  May be overridden on a per-product basis via the product node edit form.'),
    );
    $form['quote_options']['classification'] = array(
      '#type' => 'select',
      '#title' => $this->t('UPS Customer classification'),
      '#options' => array(
        '01' => $this->t('Wholesale'),
        '03' => $this->t('Occasional'),
        '04' => $this->t('Retail'),
      ),
      '#default_value' => $ups_config->get('classification'),
      '#description' => $this->t('The kind of customer you are to UPS. For daily pickups the default is wholesale; for customer counter pickups the default is retail; for other pickups the default is occasional.'),
    );

    $form['quote_options']['negotiated_rates'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Negotiated rates'),
      '#default_value' => $ups_config->get('negotiated_rates'),
      '#options' => array(1 => $this->t('Yes'), 0 => $this->t('No')),
      '#description' => $this->t('Is your UPS account receiving negotiated rates on shipments?'),
    );

    // Form to select pickup type
    $form['quote_options']['pickup_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Pickup type'),
      '#options' => array(
        '01' => 'Daily Pickup',
        '03' => 'Customer Counter',
        '06' => 'One Time Pickup',
        '07' => 'On Call Air',
        '11' => 'Suggested Retail Rates',
        '19' => 'Letter Center',
        '20' => 'Air Service Center',
      ),
      '#default_value' => $ups_config->get('pickup_type'),
    );

    $form['quote_options']['residential_quotes'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Assume UPS shipping quotes will be delivered to'),
      '#default_value' => $ups_config->get('residential_quotes'),
      '#options' => array(
        0 => $this->t('Business locations'),
        1 => $this->t('Residential locations (extra fees)'),
      ),
    );

    $form['quote_options']['unit_system'] = array(
      '#type' => 'select',
      '#title' => $this->t('System of measurement'),
      '#default_value' => $ups_config->get('unit_system', \Drupal::config('uc_store.settings')->get('length.units')),
      '#options' => array(
        'in' => $this->t('Imperial'),
        'cm' => $this->t('Metric'),
      ),
      '#description' => $this->t('Choose the standard system of measurement for your country.'),
    );

    $form['quote_options']['insurance'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Package insurance'),
      '#default_value' => $ups_config->get('insurance'),
      '#description' => $this->t('When enabled, the quotes presented to the customer will include the cost of insurance for the full sales price of all products in the order.'),
    );

    // Container for markup forms
    $form['markups'] = array(
      '#type'          => 'details',
      '#title'         => $this->t('Markups'),
      '#description'   => $this->t('Modifiers to the shipping weight and quoted rate.'),
      '#group'         => 'ups-settings',
    );

    // Form to select type of rate markup
    $form['markups']['rate_markup_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Rate markup type'),
      '#default_value' => $ups_config->get('rate_markup_type'),
      '#options' => array(
        'percentage' => $this->t('Percentage (%)'),
        'multiplier' => $this->t('Multiplier (×)'),
        'currency' => $this->t('Addition (@currency)', ['@currency' => \Drupal::config('uc_store.settings')->get('currency.symbol')]),
      ),
    );

    // Form to select rate markup amount
    $form['markups']['rate_markup'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Shipping rate markup'),
      '#default_value' => $ups_config->get('rate_markup'),
      '#description' => $this->t('Markup shipping rate quote by currency amount, percentage, or multiplier.'),
    );

    // Form to select type of weight markup
    $form['markups']['weight_markup_type'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Weight markup type'),
      '#default_value' => $ups_config->get('weight_markup_type'),
      '#options'       => array(
        'percentage' => $this->t('Percentage (%)'),
        'multiplier' => $this->t('Multiplier (×)'),
        'mass'       => $this->t('Addition (@mass)', ['@mass' => '#']),
      ),
      '#disabled' => TRUE,
    );

    // Form to select weight markup amount
    $form['markups']['weight_markup'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Shipping weight markup'),
      '#default_value' => $ups_config->get('weight_markup'),
      '#description'   => $this->t('Markup UPS shipping weight on a per-package basis before quote, by weight amount, percentage, or multiplier.'),
      '#disabled' => TRUE,
    );

    // Container for label printing
    $form['labels'] = array(
      '#type'          => 'details',
      '#title'         => $this->t('Label Printing'),
      '#description'   => $this->t('Preferences for UPS Shipping Label Printing.  Additional permissions from UPS are required to use this feature.'),
      '#group'         => 'ups-settings',
    );

    $intervals = array(86400, 302400, 604800, 1209600, 2419200, 0);
    $period = array_map(array(\Drupal::service('date.formatter'), 'formatInterval'), array_combine($intervals, $intervals));
    $period[0] = $this->t('Forever');

    // Form to select how long labels stay on server
    $form['labels']['label_lifetime'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Label lifetime'),
      '#default_value' => $ups_config->get('label_lifetime'),
      '#options'       => $period,
      '#description'   => $this->t('Controls how long labels are stored on the server before being automatically deleted. Cron must be enabled for automatic deletion. Default is never delete the labels, keep them forever.'),
    );

    // Taken from system_settings_form(). Only, don't use its submit handler.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('entity.uc_quote_method.collection'),
    );

    if (!empty($_POST) && $form_state->getErrors()) {
      drupal_set_message($this->t('The settings have not been saved because of the errors.'), 'error');
    }
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'system_settings_form';
    }

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $old_password = $this->config('uc_ups.settings')->get('password');
    if (!$form_state->getValue('uc_ups_password')) {
      if ($old_password) {
        $form_state->setValueForElement($form['credentials']['password'], $old_password);
      }
      else {
        $form_state->setErrorByName('password', $this->t('Password field is required.'));
      }
    }

    if (!is_numeric($form_state->getValue('rate_markup'))) {
      $form_state->setErrorByName('rate_markup', $this->t('Rate markup must be a numeric value.'));
    }
    if (!is_numeric($form_state->getValue('weight_markup'))) {
      $form_state->setErrorByName('weight_markup', $this->t('Weight markup must be a numeric value.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ups_config = $this->config('uc_ups.settings');

    $values = $form_state->getValues();
    $ups_config
      ->set('access_license', $values['access_license'])
      ->set('shipper_number', $values['shipper_number'])
      ->set('user_id', $values['user_id'])
      ->set('password', $values['password'])
      ->set('connection_address', $values['connection_address'])
      ->set('services', $values['uc_ups_services'])
      ->set('pickup_type', $values['pickup_type'])
      ->set('package_type', $values['package_type'])
      ->set('classification', $values['classification'])
      ->set('negotiated_rates', $values['negotiated_rates'])
      ->set('residential_quotes', $values['residential_quotes'])
      ->set('rate_markup_type', $values['rate_markup_type'])
      ->set('rate_markup', $values['rate_markup'])
      ->set('weight_markup_type', $values['weight_markup_type'])
      ->set('weight_markup', $values['weight_markup'])
      ->set('label_lifetime', $values['label_lifetime'])
      ->set('all_in_one', $values['all_in_one'])
      ->set('unit_system', $values['unit_system'])
      ->set('insurance', $values['insurance'])
      ->save();

    drupal_set_message($this->t('The configuration options have been saved.'));

    // @todo: Still need these two lines?
    //cache_clear_all();
    //drupal_theme_rebuild();

    parent::submitForm($form, $form_state);
  }

}
