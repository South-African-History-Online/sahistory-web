<?php

namespace Drupal\uc_payfast\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\Component\Utility\Unicode;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\OffsitePaymentMethodPluginInterface;

/**
 * Defines the PayFast payment method.
 *
 * * @UbercartPaymentMethod(
 *   id = "payfast",
 *   name = @Translation("PayFast"),
 * )
 */
class PayFastPaymentMethodPluginBase extends PaymentMethodPluginBase implements OffsitePaymentMethodPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant_id' => '',
      'merchant_key' => '',
      'passphrase' => '',
      'server' => 'https://sandbox.payfast.co.za/eng/process',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['merchant_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('PayFast Merchant ID'),
      '#description' => $this->t('Your PayFast merchant ID, which can be found on your PayFast account settings page.'),
      '#default_value' => $this->configuration['merchant_id'],
    );
    $form['merchant_key'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('PayFast Merchant Key'),
        '#description' => $this->t('Your PayFast merchant Key, which can be found on your PayFast account settings page.'),
        '#default_value' => $this->configuration['merchant_key'],
    );
    $form['passphrase'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('PayFast Passphrase'),
        '#description' => $this->t('Set this to be identical to the passphrase set on your PayFast account settings page.'),
        '#default_value' => $this->configuration['passphrase'],
    );
    $form['server'] = array(
      '#type' => 'select',
      '#title' => $this->t('Server'),
      '#description' => $this->t('Use the Sandbox for testing.'),
      '#options' => array(
        'https://sandbox.payfast.co.za' => $this->t('Sandbox'),
        'https://www.payfast.co.za' => $this->t('Live'),
      ),
      '#default_value' => $this->configuration['server'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['merchant_id'] = trim($form_state->getValue('merchant_id'));
    $this->configuration['merchant_key'] = trim($form_state->getValue('merchant_key'));
    $this->configuration['passphrase'] = trim($form_state->getValue('passphrase'));
    $this->configuration['server'] = $form_state->getValue('server');
  }

  /**
   * {@inheritdoc}
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $address = $order->getAddress('billing');
    if ($address->country) {
      $country = \Drupal::service('country_manager')->getCountry($address->country)->getAlpha3();
    }
    else {
      $country = '';
    }

    if ( $this->configuration['server'] == 'https://sandbox.payfast.co.za' ) {
      $merchant_id = '10000100';
      $merchant_key = '46f0cd694581a';
    }
    else {
      $merchant_id = $this->configuration['merchant_id'];
      $merchant_key = $this->configuration['merchant_key'];
    }

    $data = array(
        'merchant_id' => $merchant_id,
        'merchant_key' => $merchant_key,
        'return_url' => Url::fromRoute('uc_payfast.return_url', ['uc_order' => $order->id()], ['absolute' => TRUE])->toString(),
        'cancel_url' => Url::fromRoute('uc_cart.checkout_review', [], ['absolute' => TRUE])->toString(),
        'notify_url' => Url::fromRoute('uc_payfast.itn', [], ['absolute' => TRUE])->toString(),
        'email_address' => Unicode::substr($order->getEmail(), 0, 64),
        'm_payment_id' => $order->id(),
        'amount' => uc_currency_format($order->getTotal(), FALSE, FALSE, '.'),
        'item_name' => 'Order number: ' . $order->id(),
    );

    $pfOutput = '';
    // Create output string
    foreach( $data as $key => $value )
      $pfOutput .= $key .'='. urlencode( trim( $value ) ) .'&';

    $passPhrase = $this->configuration['passphrase'];

    if ( empty( $passPhrase ) || $this->configuration['passphrase'] == 'Sandbox' ) {
      $pfOutput = substr( $pfOutput, 0, -1 );
    }
    else
    {
      $pfOutput = $pfOutput."passphrase=".urlencode( $passPhrase );
    }

    $data['signature'] = md5( $pfOutput );
    $data['user_agent'] = 'UberCart 3.x';
    
    $host = $this->configuration['server'] . '/eng/process';
    $form['#action'] = $host;

    foreach ($data as $name => $value) {
      $form[$name] = array('#type' => 'hidden', '#value' => $value);
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Submit order'),
    );

    return $form;
  }


}
