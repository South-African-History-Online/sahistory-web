<?php

namespace Drupal\uc_paypal\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines the PayPal Express Checkout payment method.
 */
abstract class PayPalPaymentMethodPluginBase extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'wps_email' => '',
      'wpp_server' => 'https://api-3t.sandbox.paypal.com/nvp',
      'api' => [
        'api_username' => '',
        'api_password' => '',
        'api_signature' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['wps_email'] = array(
      '#type' => 'email',
      '#title' => $this->t('PayPal e-mail address'),
      '#description' => $this->t('The e-mail address you use for the PayPal account you want to receive payments.'),
      '#default_value' => $this->configuration['wps_email'],
    );
    $form['wpp_server'] = array(
      '#type' => 'select',
      '#title' => $this->t('API server'),
      '#description' => $this->t('Sign up for and use a Sandbox account for testing.'),
      '#options' => array(
        'https://api-3t.sandbox.paypal.com/nvp' => $this->t('Sandbox'),
        'https://api-3t.paypal.com/nvp' => $this->t('Live'),
      ),
      '#default_value' => $this->configuration['wpp_server'],
    );
    $form['api'] = array(
      '#type' => 'details',
      '#title' => $this->t('API credentials'),
      '#description' => $this->t('@link for information on obtaining credentials. You need to acquire an API Signature. If you have already requested API credentials, you can review your settings under the API Access section of your PayPal profile.', ['@link' => Link::fromTextAndUrl($this->t('Click here'), Url::fromUri('https://developer.paypal.com/docs/classic/api/apiCredentials/'))->toString()]),
      '#open' => TRUE,
    );
    $form['api']['api_username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API username'),
      '#default_value' => $this->configuration['api']['api_username'],
    );
    $form['api']['api_password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API password'),
      '#default_value' => $this->configuration['api']['api_password'],
    );
    $form['api']['api_signature'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Signature'),
      '#default_value' => $this->configuration['api']['api_signature'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['wps_email'] = trim($form_state->getValue('wps_email'));
    $this->configuration['wpp_server'] = $form_state->getValue('wpp_server');
    $this->configuration['api']['api_username'] = $form_state->getValue(['settings', 'api', 'api_username']);
    $this->configuration['api']['api_password'] = $form_state->getValue(['settings', 'api', 'api_password']);
    $this->configuration['api']['api_signature'] = $form_state->getValue(['settings', 'api', 'api_signature']);
  }

}
