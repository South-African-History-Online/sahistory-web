<?php

namespace Drupal\uc_usps\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_usps\USPSUtilities;

/**
 * Configures USPS settings.
 */
class USPSSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_usps_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_usps.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $usps_config = $this->config('uc_usps.settings');

    // Put fieldsets into vertical tabs
    $form['usps-settings'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'library' => array(
          'uc_usps/uc_usps.scripts',
        ),
      ),
    );

    // Container for credential forms
    $form['credentials'] = array(
      '#type'          => 'details',
      '#title'         => $this->t('Credentials'),
      '#description'   => $this->t('Account number and authorization information.'),
      '#group'         => 'usps-settings',
    );

    $form['credentials']['user_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('USPS user ID'),
      '#description' => $this->t('To acquire or locate your user ID, refer to the <a href=":url">USPS documentation</a>.', [':url' => Url::fromUri('http://drupal.org/node/1308256')->toString()]),
      '#default_value' => $usps_config->get('user_id'),
    );

    $form['domestic'] = array(
      '#type' => 'details',
      '#title' => $this->t('USPS Domestic'),
      '#description' => $this->t('Set the conditions that will return a USPS quote.'),
      '#group'         => 'usps-settings',
    );

    $form['domestic']['online_rates'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display USPS "online" rates'),
      '#default_value' => $usps_config->get('online_rates'),
      '#description' => $this->t('Show your customer standard USPS rates (default) or discounted "online" rates.  Online rates apply only if you, the merchant, pay for and print out postage from the USPS <a href="https://cns.usps.com/labelInformation.shtml">Click-N-Ship</a> web site.'),
    );

    $form['domestic']['env_services'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('USPS envelope services'),
      '#default_value' => $usps_config->get('env_services'),
      '#options' => USPSUtilities::envelopeServices(),
      '#description' => $this->t('Select the USPS services that are available to customers. Be sure to include the services that the Postal Service agrees are available to you.'),
    );

    $form['domestic']['services'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('USPS parcel services'),
      '#default_value' => $usps_config->get('services'),
      '#options' => USPSUtilities::services(),
      '#description' => $this->t('Select the USPS services that are available to customers. Be sure to include the services that the Postal Service agrees are available to you.'),
    );

    $form['international'] = array(
      '#type' => 'details',
      '#title' => $this->t('USPS International'),
      '#description' => $this->t('Set the conditions that will return a USPS International quote.'),
      '#group'         => 'usps-settings',
    );

    $form['international']['intl_env_services'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('USPS international envelope services'),
      '#default_value' => $usps_config->get('intl_env_services'),
      '#options' => USPSUtilities::internationalEnvelopeServices(),
      '#description' => $this->t('Select the USPS services that are available to customers. Be sure to include the services that the Postal Service agrees are available to you.'),
    );

    $form['international']['intl_services'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('USPS international parcel services'),
      '#default_value' => $usps_config->get('intl_services'),
      '#options' => USPSUtilities::internationalServices(),
      '#description' => $this->t('Select the USPS services that are available to customers. Be sure to include the services that the Postal Service agrees are available to you.'),
    );

    // Container for quote options
    $form['quote_options'] = array(
      '#type'          => 'details',
      '#title'         => $this->t('Quote options'),
      '#description'   => $this->t('Preferences that affect computation of quote.'),
      '#group'         => 'usps-settings',
    );

    $form['quote_options']['all_in_one'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Product packages'),
      '#default_value' => $usps_config->get('all_in_one'),
      '#options' => array(
        0 => $this->t('Each product in its own package'),
        1 => $this->t('All products in one package'),
      ),
      '#description' => $this->t('Indicate whether each product is quoted as shipping separately or all in one package. Orders with one kind of product will still use the package quantity to determine the number of packages needed, however.'),
    );

    // Insurance
    $form['quote_options']['insurance'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Package insurance'),
      '#default_value' => $usps_config->get('insurance'),
      '#description' => $this->t('When enabled, the quotes presented to the customer will include the cost of insurance for the full sales price of all products in the order.'),
      '#disabled' => TRUE,
    );

    // Delivery Confirmation
    $form['quote_options']['delivery_confirmation'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Delivery confirmation'),
      '#default_value' => $usps_config->get('delivery_confirmation'),
      '#description' => $this->t('When enabled, the quotes presented to the customer will include the cost of delivery confirmation for all packages in the order.'),
      '#disabled' => TRUE,
    );

    // Signature Confirmation
    $form['quote_options']['signature_confirmation'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Signature confirmation'),
      '#default_value' => $usps_config->get('signature_confirmation'),
      '#description' => $this->t('When enabled, the quotes presented to the customer will include the cost of signature confirmation for all packages in the order.'),
      '#disabled' => TRUE,
    );

    // Container for markup forms
    $form['markups'] = array(
      '#type'          => 'details',
      '#title'         => $this->t('Markups'),
      '#description'   => $this->t('Modifiers to the shipping weight and quoted rate.'),
      '#group'         => 'usps-settings',
    );

    $form['markups']['rate_markup_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Rate markup type'),
      '#default_value' => $usps_config->get('rate_markup_type'),
      '#options' => array(
        'percentage' => $this->t('Percentage (%)'),
        'multiplier' => $this->t('Multiplier (×)'),
        'currency' => $this->t('Addition (@currency)', ['@currency' => \Drupal::config('uc_store.settings')->get('currency.symbol')]),
      ),
    );
    $form['markups']['rate_markup'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Shipping rate markup'),
      '#default_value' => $usps_config->get('rate_markup'),
      '#description' => $this->t('Markup shipping rate quote by dollar amount, percentage, or multiplier.'),
    );

    // Form to select type of weight markup
    $form['markups']['weight_markup_type'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Weight markup type'),
      '#default_value' => $usps_config->get('weight_markup_type'),
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
      //'#default_value' => $usps_config->get('weight_markup'),
      '#default_value' => 0,
      '#description'   => $this->t('Markup shipping weight on a per-package basis before quote, by weight amount, percentage, or multiplier.'),
      '#disabled' => TRUE,
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
    $usps_config = $this->config('uc_usps.settings');

    $values = $form_state->getValues();
    $usps_config
      ->set('user_id', $values['user_id'])
      ->set('online_rates', $values['online_rates'])
      ->set('env_services', $values['env_services'])
      ->set('services', $values['services'])
      ->set('intl_env_services', $values['intl_env_services'])
      ->set('intl_services', $values['intl_services'])
      ->set('rate_markup_type', $values['rate_markup_type'])
      ->set('rate_markup', $values['rate_markup'])
      ->set('weight_markup_type', $values['weight_markup_type'])
      ->set('weight_markup', $values['weight_markup'])
      ->set('all_in_one', $values['all_in_one'])
      ->set('insurance', $values['insurance'])
      ->set('delivery_confirmation', $values['delivery_confirmation'])
      ->set('signature_confirmation', $values['signature_confirmation'])
      ->save();

    drupal_set_message($this->t('The configuration options have been saved.'));

    // @todo: Still need these two lines?
    //cache_clear_all();
    //drupal_theme_rebuild();

    parent::submitForm($form, $form_state);
  }

}
