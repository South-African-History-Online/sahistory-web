<?php

namespace Drupal\uc_paypal\Plugin\Ubercart\PaymentMethod;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\uc_credit\CreditCardPaymentMethodBase;
use Drupal\uc_order\OrderInterface;
use GuzzleHttp\Exception\TransferException;

/**
 * Defines the PayPal Website Payments Pro payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "paypal_wpp",
 *   name = @Translation("PayPal Website Payments Pro"),
 * )
 */
class PayPalWebsitePaymentsPro extends CreditCardPaymentMethodBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
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
    $form = parent::buildConfigurationForm($form, $form_state);

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
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['wps_email'] = trim($form_state->getValue('wps_email'));
    $this->configuration['wpp_server'] = $form_state->getValue('wpp_server');
    $this->configuration['api']['api_username'] = $form_state->getValue(['settings', 'api', 'api_username']);
    $this->configuration['api']['api_password'] = $form_state->getValue(['settings', 'api', 'api_password']);
    $this->configuration['api']['api_signature'] = $form_state->getValue(['settings', 'api', 'api_signature']);
  }

  /**
   * {@inheritdoc}
   */
  protected function chargeCard(OrderInterface $order, $amount, $txn_type, $reference = NULL) {
    if ($txn_type == UC_CREDIT_PRIOR_AUTH_CAPTURE) {
      $nvp_request = array(
        'METHOD' => 'DoCapture',
        'AUTHORIZATIONID' => $reference,
        'AMT' => uc_currency_format($amount, FALSE, FALSE, '.'),
        'CURRENCYCODE' => $order->getCurrency(),
        'COMPLETETYPE' => 'Complete',
      );
    }
    else {
      if (intval($order->payment_details['cc_exp_month']) < 10) {
        $expdate = '0' . $order->payment_details['cc_exp_month'] . $order->payment_details['cc_exp_year'];
      }
      else {
        $expdate = $order->payment_details['cc_exp_month'] . $order->payment_details['cc_exp_year'];
      }

      $cc_type = NULL;
      if (isset($order->payment_details['cc_type'])) {
        switch (strtolower($order->payment_details['cc_type'])) {
          case 'amex':
          case 'american express':
            $cc_type = 'Amex';
            break;
          case 'visa':
            $cc_type = 'Visa';
            break;
          case 'mastercard':
          case 'master card':
            $cc_type = 'MasterCard';
            break;
          case 'discover':
            $cc_type = 'Discover';
            break;
        }
      }
      if (is_null($cc_type)) {
        $cc_type = $this->cardType($order->payment_details['cc_number']);
        if ($cc_type === FALSE) {
          drupal_set_message($this->t('The credit card type did not pass validation.'), 'error');
          \Drupal::logger('uc_paypal')->error('Could not figure out cc type: @number / @type', ['@number' => $order->payment_details['cc_number'], '@type' => $order->payment_details['cc_type']]);
          return array('success' => FALSE);
        }
      }

      // PayPal doesn't accept IPv6 addresses.
      $ip_address = ltrim(\Drupal::request()->getClientIp(), '::ffff:');

      $address = $order->getAddress('billing');
      $nvp_request = array(
        'METHOD' => 'DoDirectPayment',
        'PAYMENTACTION' => $txn_type == UC_CREDIT_AUTH_ONLY ? 'Authorization' : 'Sale',
        'IPADDRESS' => $ip_address,
        'AMT' => uc_currency_format($amount, FALSE, FALSE, '.'),
        'CREDITCARDTYPE' => $cc_type,
        'ACCT' => $order->payment_details['cc_number'],
        'EXPDATE' => $expdate,
        'CVV2' => $order->payment_details['cc_cvv'],
        'FIRSTNAME' => substr($address->first_name, 0, 25),
        'LASTNAME' => substr($address->last_name, 0, 25),
        'STREET' => substr($address->street1, 0, 100),
        'STREET2' => substr($address->street2, 0, 100),
        'CITY' => substr($address->city, 0, 40),
        'STATE' => $address->zone,
        'ZIP' => $address->postal_code,
        'COUNTRYCODE' => $address->country,
        'CURRENCYCODE' => $order->getCurrency(),
        'DESC' => $this->t('Order @order_id at @store', ['@order_id' => $order->id(), '@store' => uc_store_name()]),
        'INVNUM' => $order->id() . '-' . REQUEST_TIME,
        'BUTTONSOURCE' => 'Ubercart_ShoppingCart_DP_US',
        'NOTIFYURL' => Url::fromRoute('uc_paypal.ipn', [], ['absolute' => TRUE])->toString(),
        'EMAIL' => substr($order->getEmail(), 0, 127),
        'PHONENUM' => substr($address->phone, 0, 20),
      );

      if ($order->isShippable()) {
        $address = $order->getAddress('delivery');
        $nvp_request += array(
          'SHIPTONAME' => substr($address->first_name . ' ' . $address->last_name, 0, 25),
          'SHIPTOSTREET' => substr($address->street1, 0, 100),
          'SHIPTOSTREET2' => substr($address->street2, 0, 100),
          'SHIPTOCITY' => substr($address->city, 0, 40),
          'SHIPTOSTATE' => $address->zone,
          'SHIPTOZIP' => $address->postal_code,
          'SHIPTOCOUNTRYCODE' => $address->country,
        );
      }
    }

    $nvp_response = $this->sendNvpRequest($nvp_request);
    $types = uc_credit_transaction_types();

    switch ($nvp_response['ACK']) {
      case 'SuccessWithWarning':
        \Drupal::logger('uc_paypal')->warning('<b>@type succeeded with a warning.</b>@paypal_message',
          array(
            '@paypal_message' => $this->buildErrorMessages($nvp_response),
            '@type' => $types[$txn_type],
            'link' => $order->toLink($this->t('view order'))->toString(),
          )
        );
      // Fall through.
      case 'Success':
        $message = $this->t('<b>@type</b><br /><b>Success: </b>@amount @currency', ['@type' => $types[$txn_type], '@amount' => uc_currency_format($nvp_response['AMT'], FALSE), '@currency' => $nvp_response['CURRENCYCODE']]);
        if ($txn_type != UC_CREDIT_PRIOR_AUTH_CAPTURE) {
          $message .= '<br />' . $this->t('<b>Address:</b> @avscode', ['@avscode' => $this->avscodeMessage($nvp_response['AVSCODE'])]);
          $message .= '<br />' . $this->t('<b>CVV2:</b> @cvvmatch', ['@cvvmatch' => $this->cvvmatchMessage($nvp_response['CVV2MATCH'])]);
        }
        $result = array(
          'success' => TRUE,
          'comment' => $this->t('PayPal transaction ID: @transactionid', ['@transactionid' => $nvp_response['TRANSACTIONID']]),
          'message' => $message,
          'data' => SafeMarkup::checkPlain($nvp_response['TRANSACTIONID']),
          'uid' => \Drupal::currentUser()->id(),
        );

        // If this was an authorization only transaction...
        if ($txn_type == UC_CREDIT_AUTH_ONLY) {
          // Log the authorization to the order.
          uc_credit_log_authorization($order->id(), $nvp_response['TRANSACTIONID'], $nvp_response['AMT']);
        }
        elseif ($txn_type == UC_CREDIT_PRIOR_AUTH_CAPTURE) {
          uc_credit_log_prior_auth_capture($order->id(), $reference);
        }

        // Log the IPN to the database.
        db_insert('uc_payment_paypal_ipn')
          ->fields(array(
            'order_id' => $order->id(),
            'txn_id' => $nvp_response['TRANSACTIONID'],
            'txn_type' => 'web_accept',
            'mc_gross' => $amount,
            'status' => 'Completed',
            'payer_email' => $order->getEmail(),
            'received' => REQUEST_TIME,
          ))
          ->execute();

        break;
      case 'FailureWithWarning':
        // Fall through.
      case 'Failure':
        $message = $this->t('<b>@type failed.</b>', ['@type' => $types[$txn_type]]) . $this->buildErrorMessages($nvp_response);
        $result = array(
          'success' => FALSE,
          'message' => $message,
          'uid' => \Drupal::currentUser()->id(),
        );
        break;
      default:
        $message = $this->t('Unexpected acknowledgement status: @status', ['@status' => $nvp_response['ACK']]);
        $result = array(
          'success' => NULL,
          'message' => $message,
          'uid' => \Drupal::currentUser()->id(),
        );
        break;
    }

    uc_order_comment_save($order->id(), \Drupal::currentUser()->id(), $message, 'admin');

    // Don't log this as a payment money wasn't actually captured.
    if (in_array($txn_type, array(UC_CREDIT_AUTH_ONLY))) {
      $result['log_payment'] = FALSE;
    }

    return $result;
  }

  /**
   * Sends a request to the PayPal NVP API.
   */
  public function sendNvpRequest($params) {
    $host = $this->configuration['wpp_server'];
    $params += [
      'USER' => $this->configuration['api']['api_username'],
      'PWD' => $this->configuration['api']['api_password'],
      'SIGNATURE' => $this->configuration['api']['api_signature'],
      'VERSION' => '3.0',
    ];

    try {
      $response = \Drupal::httpClient()->request('POST', $host, [
        'form_params' => $params,
      ]);
      parse_str($response->getBody(), $output);
      return $output;
    }
    catch (TransferException $e) {
      \Drupal::logger('uc_paypal')->error('NVP API request failed with HTTP error %error.', ['%error' => $e->getMessage()]);
    }
  }

  /**
   * Builds error message(s) from PayPal failure responses.
   */
  protected function buildErrorMessages($nvp_response) {
    $code = 0;
    $message = '';
    while (array_key_exists('L_SEVERITYCODE' . $code, $nvp_response)) {
      $message .= '<br /><b>' . SafeMarkup::checkPlain($nvp_response['L_SEVERITYCODE' . $code]) . ':</b> ' . SafeMarkup::checkPlain($nvp_response['L_ERRORCODE' . $code]) . ': ' . SafeMarkup::checkPlain($nvp_response['L_LONGMESSAGE' . $code]);
      $code++;
    }
    return $message;
  }

  /**
   * Returns the PayPal approved credit card type for a card number.
   */
  protected function cardType($cc_number) {
    switch (substr(strval($cc_number), 0, 1)) {
      case '3':
        return 'Amex';
      case '4':
        return 'Visa';
      case '5':
        return 'MasterCard';
      case '6':
        return 'Discover';
    }

    return FALSE;
  }

  /**
   * Returns a human readable message for the AVS code.
   */
  protected function avscodeMessage($code) {
    if (is_numeric($code)) {
      switch ($code) {
        case '0':
          return $this->t('All the address information matched.');
        case '1':
          return $this->t('None of the address information matched; transaction declined.');
        case '2':
          return $this->t('Part of the address information matched.');
        case '3':
          return $this->t('The merchant did not provide AVS information. Not processed.');
        case '4':
          return $this->t('Address not checked, or acquirer had no response. Service not available.');
        default:
          return $this->t('No AVS response was obtained.');
      }
    }

    switch ($code) {
      case 'A':
      case 'B':
        return $this->t('Address matched; postal code did not');
      case 'C':
      case 'N':
        return $this->t('Nothing matched; transaction declined');
      case 'D':
      case 'F':
      case 'X':
      case 'Y':
        return $this->t('Address and postal code matched');
      case 'E':
        return $this->t('Not allowed for MOTO transactions; transaction declined');
      case 'G':
        return $this->t('Global unavailable');
      case 'I':
        return $this->t('International unavailable');
      case 'P':
      case 'W':
      case 'Z':
        return $this->t('Postal code matched; address did not');
      case 'R':
        return $this->t('Retry for validation');
      case 'S':
        return $this->t('Service not supported');
      case 'U':
        return $this->t('Unavailable');
      case 'Null':
        return $this->t('No AVS response was obtained.');
      default:
        return $this->t('An unknown error occurred.');
    }
  }

  /**
   * Returns a human readable message for the CVV2 match code.
   */
  protected function cvvmatchMessage($code) {
    if (is_numeric($code)) {
      switch ($code) {
        case '0':
          return $this->t('Matched');
        case '1':
          return $this->t('No match');
        case '2':
          return $this->t('The merchant has not implemented CVV2 code handling.');
        case '3':
          return $this->t('Merchant has indicated that CVV2 is not present on card.');
        case '4':
          return $this->t('Service not available');
        default:
          return $this->t('Unkown error');
      }
    }

    switch ($code) {
      case 'M':
        return $this->t('Match');
      case 'N':
        return $this->t('No match');
      case 'P':
        return $this->t('Not processed');
      case 'S':
        return $this->t('Service not supported');
      case 'U':
        return $this->t('Service not available');
      case 'X':
        return $this->t('No response');
      default:
        return $this->t('Not checked');
    }
  }

}
