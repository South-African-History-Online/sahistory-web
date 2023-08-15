<?php

namespace Drupal\uc_authorizenet\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_credit\CreditCardPaymentMethodBase;
use Drupal\uc_order\OrderInterface;

/**
 * Defines the Authorize.net payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "authorizenet",
 *   name = @Translation("Authorize.net"),
 * )
 */
class AuthorizeNet extends CreditCardPaymentMethodBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'duplicate_window' => 120,
      'api' => [
        'login_id' => '',
        'transaction_key' => '',
        'test_gateway_url' => UC_AUTHORIZENET_TEST_GATEWAY_URL,
        'live_gateway_url' => UC_AUTHORIZENET_LIVE_GATEWAY_URL,
      ],
      'aim' => [
        'txn_mode' => 'live_test',
        'email_customer' => FALSE,
        'response_debug' => FALSE,
      ],
      'arb' => [
        'arb_mode' => 'disabled',
        'md5_hash' => '',
        'report_arb_post' => FALSE,
      ],
      'cim' => [
        'cim_profile' => FALSE,
        'cim_mode' => 'disabled',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // If CC encryption has been configured properly.
    if ($key = uc_credit_encryption_key()) {
      // Setup our encryption object.
      $crypt = \Drupal::service('uc_store.encryption');

      // Decrypt the MD5 Hash.
      $md5_hash = $this->configuration['arb']['md5_hash'];
      if (!empty($md5_hash)) {
        $md5_hash = $crypt->decrypt($key, $md5_hash);
      }

      // Store any errors.
      uc_store_encryption_errors($crypt, 'uc_authorizenet');
    }
    else {
      // @todo: Need to set a form error - can't configure Authorize.net without CC encryption set up.
    }

    // Allow admin to set duplicate window.
    $intervals = array(0, 15, 30, 45, 60, 75, 90, 105, 120);
    $form['duplicate_window'] = array(
      '#type' => 'select',
      '#title' => $this->t('Duplicate window'),
      '#description' => $this->t('Blocks submission of duplicate transactions within the specified window. Defaults to 120 seconds.'),
      '#default_value' => $this->configuration['duplicate_window'],
      '#options' => array_combine($intervals, $intervals),
    );

    $form['api'] = array(
      '#type' => 'details',
      '#title' => $this->t('API Login ID and Transaction Key'),
      '#description' => $this->t('This information is required for Ubercart to interact with your payment gateway account. It is different from your login ID and password and may be found through your account settings page. Do not change the gateway URLs unless you are using this module with an Authorize.net-compatible gateway that requires different URLs.'),
      '#open' => TRUE,
    );
    $form['api']['login_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Login ID'),
      '#default_value' => $this->configuration['api']['login_id'],
    );
    $form['api']['transaction_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Transaction Key'),
      '#default_value' => $this->configuration['api']['transaction_key'],
    );
    $form['api']['test_gateway_url'] = array(
      '#type' => 'url',
      '#title' => $this->t('Authorize.net Test Gateway URL'),
      '#default_value' => $this->configuration['api']['test_gateway_url'],
    );
    $form['api']['live_gateway_url'] = array(
      '#type' => 'url',
      '#title' => $this->t('Authorize.net Live Gateway URL'),
      '#default_value' => $this->configuration['api']['live_gateway_url'],
    );

    $form['aim'] = array(
      '#type' => 'details',
      '#title' => $this->t('AIM settings'),
      '#description' => $this->t('These settings pertain to the Authorize.Net AIM payment method for card not present transactions.'),
      '#open' => TRUE,
    );
    $form['aim']['txn_mode'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Transaction mode'),
      '#description' => $this->t('Only specify a developer test account if you login to your account through https://test.authorize.net.<br />Adjust to live transactions when you are ready to start processing real payments.'),
      '#options' => array(
        'live' => $this->t('Live transactions in a live account'),
        'live_test' => $this->t('Test transactions in a live account'),
        'developer_test' => $this->t('Developer test account transactions'),
      ),
      '#default_value' => $this->configuration['aim']['txn_mode'],
    );

    $form['aim']['email_customer'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Tell Authorize.net to e-mail the customer a receipt based on your account settings.'),
      '#default_value' => $this->configuration['aim']['email_customer'],
    );
    $form['aim']['response_debug'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Log full API response messages from Authorize.net for debugging.'),
      '#default_value' => $this->configuration['aim']['response_debug'],
    );


    $form['arb'] = array(
      '#type' => 'details',
      '#title' => $this->t('ARB settings'),
      '#description' => $this->t('These settings pertain to the Authorize.Net Automated Recurring Billing service.'),
      '#open' => TRUE,
    );
    $form['arb']['arb_mode'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Transaction mode'),
      '#description' => $this->t('Only specify developer mode if you login to your account through https://test.authorize.net.<br />Adjust to production mode when you are ready to start processing real recurring fees.'),
      '#options' => array(
        'production' => $this->t('Production'),
        'developer' => $this->t('Developer test'),
        'disabled' => $this->t('Disabled'),
      ),
      '#default_value' => $this->configuration['arb']['arb_mode'],
    );
    $form['arb']['md5_hash'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('MD5 Hash'),
      '#description' => $this->t('<b>Note:</b> You must first configure credit card encryption before setting this.<br />Enter the value here you entered in your Auth.Net account settings.'),
      '#default_value' => $md5_hash,
      '#access' => \Drupal::currentUser()->hasPermission('administer credit cards'),
    );
    $form['arb']['report_arb_post'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Log reported ARB payments in watchdog.'),
      '#description' => $this->t('Make sure you have set your Silent POST URL in Authorize.Net to :url.', [':url' => Url::fromUri('base:authnet/silent-post', ['absolute' => TRUE])->toString()]),
      '#default_value' => $this->configuration['arb']['report_arb_post'],
    );

    $form['cim'] = array(
      '#type' => 'details',
      '#title' => $this->t('CIM settings'),
      '#description' => $this->t('These settings pertain to the Authorize.Net Customer Information Management service.'),
      '#open' => TRUE,
    );
    $form['cim']['cim_profile'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Always create a CIM profile for securely storing CC info for later use.'),
      '#default_value' => $this->configuration['cim']['cim_profile'],
    );
    $form['cim']['cim_mode'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Transaction mode'),
      '#description' => $this->t('Only specify a developer test account if you login to your account through https://test.authorize.net.<br />Adjust to live transactions when you are ready to start processing real payments.'),
      '#options' => array(
        'production' => $this->t('Production'),
        'developer' => $this->t('Developer test'),
        'disabled' => $this->t('Disabled'),
      ),
      '#default_value' => $this->configuration['cim']['cim_mode'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['duplicate_window'] = $form_state->getValue('duplicate_window');
    $this->configuration['api']['login_id'] = trim($form_state->getValue(['settings', 'api', 'login_id']));
    $this->configuration['api']['transaction_key'] = trim($form_state->getValue(['settings', 'api', 'transaction_key']));
    $this->configuration['api']['test_gateway_url'] = $form_state->getValue(['settings', 'api', 'test_gateway_url']);
    $this->configuration['api']['live_gateway_url'] = $form_state->getValue(['settings', 'api', 'live_gateway_url']);
    $this->configuration['aim']['txn_mode'] = $form_state->getValue(['settings', 'aim', 'txn_mode']);
    $this->configuration['aim']['email_customer'] = $form_state->getValue(['settings', 'aim', 'email_customer']);
    $this->configuration['aim']['response_debug'] = $form_state->getValue(['settings', 'aim', 'response_debug']);
    $this->configuration['arb']['arb_mode'] = $form_state->getValue(['settings', 'arb', 'arb_mode']);
    $this->configuration['arb']['report_arb_post'] = $form_state->getValue(['settings', 'arb', 'report_arb_post']);
    $this->configuration['cim']['cim_profile'] = $form_state->getValue(['settings', 'cim', 'cim_profile']);
    $this->configuration['cim']['cim_mode'] = $form_state->getValue(['settings', 'cim', 'cim_mode']);
    // If CC encryption has been configured properly.
    if ($key = uc_credit_encryption_key()) {
      // Setup our encryption object.
      $crypt = \Drupal::service('uc_store.encryption');

      // Encrypt the Login ID, Transaction key, and MD5 Hash.
      $md5_hash = $form_state->getValue(['settings', 'arb', 'md5_hash']);
      if (!empty($md5_hash)) {
        $this->configuration['arb']['md5_hash'] = $crypt->encrypt($key, $md5_hash);
      }

      // Store any errors.
      uc_store_encryption_errors($crypt, 'uc_authorizenet');
    }
  }

  public function getTransactionTypes() {
    return [
      UC_CREDIT_AUTH_ONLY, UC_CREDIT_PRIOR_AUTH_CAPTURE, UC_CREDIT_AUTH_CAPTURE,
      UC_CREDIT_REFERENCE_SET, UC_CREDIT_REFERENCE_TXN
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function chargeCard(OrderInterface $order, $amount, $txn_type, $reference = NULL) {
  //function uc_authorizenet_charge($order_id, $amount, $data) {

    // Perform the appropriate action based on the transaction type.
    switch ($txn_type) {
      // Reference transactions are handled through Authorize.Net's CIM.
      case UC_CREDIT_REFERENCE_TXN:
        return _uc_authorizenet_cim_profile_charge($order, $amount, $data);

      // Set a reference only.
      case UC_CREDIT_REFERENCE_SET:
        // Return the error message if this failed.
        if ($message = _uc_authorizenet_cim_profile_create($order)) {
          return array('success' => FALSE, 'message' => $message);
        }
        else {
          return array('success' => TRUE, 'log_payment' => FALSE, 'message' => $this->t('New customer profile created successfully at Authorize.Net.'));
        }

      // Accommodate all other transaction types.
      default:
        return _uc_authorizenet_charge($order, $amount, $txn_type, $reference);
    }

  }

  /**
   * Handles authorizations and captures through AIM at Authorize.Net
   */
  protected function _uc_authorizenet_charge(OrderInterface $order, $amount, $txn_type, $reference) {
    global $user;

    // Build a description of the order for logging in Auth.Net.
    $description = array();
    foreach ((array) $order->products as $product) {
      $description[] = $product->qty . 'x ' . $product->model;
    }

    $billing_address = $order->getAddress('billing');
    $billing_street = $billing_address->getStreet1();
    if ($billing_address->getStreet2()) {
      $billing_street .= ', ' . $billing_address->getStreet2();
    }
    $delivery_address = $order->getAddress('delivery');
    $delivery_street = $delivery_address->getStreet1();
    if ($delivery_address->getStreet2()) {
      $delivery_street .= ', ' . $delivery_address->getStreet2();
    }

    // Build the POST data for the transaction.
    $submit_data = array(
      // Merchant information.
      'x_login' => $this->configuration['login_id'],
      'x_tran_key' => $this->configuration['transaction_key'],

      // Transaction information.
      'x_version' => '3.1',
      'x_type' => $this->transactionTypeMap($txn_type),
      // 'x_method' => $order->getPaymentMethodId() == 'credit' ? 'CC' : 'ECHECK',
      'x_method' => 'CC',
      // 'x_recurring_billing' => 'FALSE',
      'x_amount' => uc_currency_format($amount, FALSE, FALSE, '.'),
      'x_card_num' => $order->payment_details['cc_number'],
      'x_exp_date' => $order->payment_details['cc_exp_month'] . '/' . $order->payment_details['cc_exp_year'],
      'x_card_code' => $order->payment_details['cc_cvv'],
      // 'x_trans_id' => '',
      // 'x_auth_code' => '',
      'x_test_request' => $this->configuration['aim']['txn_mode'] == 'live_test' ? 'TRUE' : 'FALSE',
      'x_duplicate_window' => $this->configuration['duplicate_window'],

      // Order information.
      'x_invoice_num' => $order->id(),
      'x_description' => substr(implode(', ', $description), 0, 255),

      // Customer information.
      'x_first_name' => substr($billing_address->getFirstName(), 0, 50),
      'x_last_name' => substr($billing_address->getLastName(), 0, 50),
      'x_company' => substr($billing_address->getCompany(), 0, 50),
      'x_address' => substr($billing_street, 0, 60),
      'x_city' => substr($billing_address->getCity(), 0, 40),
      'x_state' => substr($billing_address->getZone(), 0, 40),
      'x_zip' => substr($billing_address->getPostalCode(), 0, 20),
      'x_country' => $billing_address->getCountry(),
      'x_phone' => substr($billing_address->getPhone(), 0, 25),
      // 'x_fax' => substr('', 0, 25),
      'x_email' => substr($order->getEmail(), 0, 255),
      'x_cust_id' => $order->getOwnerId(),
      'x_customer_ip' => substr(Drupal::request()->getClientIp(), 0, 15),

      // Shipping information.
      'x_ship_to_first_name' => substr($delivery_address->getFirstName(), 0, 50),
      'x_ship_to_last_name' => substr($delivery_address->getLastName(), 0, 50),
      'x_ship_to_company' => substr($delivery_address->getCompany(), 0, 50),
      'x_ship_to_address' => substr($delivery_street, 0, 60),
      'x_ship_to_city' => substr($delivery_address->getCity(), 0, 40),
      'x_ship_to_state' => substr($delivery_address->getZone(), 0, 40),
      'x_ship_to_zip' => substr($delivery_address->getPostalCode(), 0, 20),
      'x_ship_to_country' => $delivery_address->getCountry(),

      // Extra information.
      'x_delim_data' => 'TRUE',
      'x_delim_char' => '|',
      'x_encap_char' => '"',
      'x_relay_response' => 'FALSE',
      'x_email_customer' => $this->configuration['aim']['email_customer'] ? 'TRUE' : 'FALSE',
    );

    if ($txn_type == UC_CREDIT_PRIOR_AUTH_CAPTURE) {
      $submit_data['x_trans_id'] = $data['auth_id'];
    }

    // Allow other modules to alter the transaction.
    \Drupal::moduleHandler()->alter('uc_authorizenet_transaction', $submit_data);

    // Determine the correct URL based on the transaction mode.
    if ($this->configuration['aim']['txn_mode'] == 'developer_test') {
      $post_url = $this->configuration['test_gateway_url'];
    }
    else {
      $post_url = $this->configuration['live_gateway_url'];
    }

    $result = \Drupal::httpClient()
      ->setSslVerification(TRUE, TRUE, 2)
      ->setConfig(array('curl.options' => array(CURLOPT_FOLLOWLOCATION => FALSE)))
      ->post($post_url, NULL, $submit_data)
      ->send();

    // Log any errors to the watchdog.
    if ($result->isError()) {
      \Drupal::logger('uc_authorizenet')->error('@error', ['@error' => $result->getReasonPhrase()]);
      return array('success' => FALSE);
    }

    $response = explode('|', $result->getBody(TRUE));

    if ($this->configuration['aim']['response_debug']) {
      \Drupal::logger('uc_authorizenet')->notice('Debug response: @data', ['@data' => '<pre>' . print_r($response, TRUE) . '</pre>']);
    }

    // Trim off the encapsulating character from the results.
    for ($i = 0; $i < count($response); $i++) {
      $response[$i] = substr($response[$i], 1, strlen($response[$i]) - 2);
    }

    /**
     * Response key index:
     * 0 = Response Code
     * 2 = Response Reason Code
     * 3 = Response Reason Text
     * 4 = Authorization Code
     * 5 = Address Verification Service (AVS) Response
     * 6 = Transaction ID; needed for CREDIT, PRIOR_AUTH_CAPTURE, and VOID transactions.
     * 9 = Amount
     * 11 = Transaction Type
     * 32 = Tax Amount Charged
     * 37 = Transaction Response MD5 Hash
     * 38 = Card Code (CVV) Response
     */

    // If we didn't get an approval response code...
    if ($response[0] != '1') {
      // Fail the charge with the reason text in the decline message.
      $result = array(
        'success' => FALSE,
        'message' => $this->t('Credit card payment declined: @message', ['@message' => $response[3]]),
        'uid' => $user->id(),
      );
    }
    else {
      // Build a message for display and comments in the payments table.
      $message = $this->t('Type: @type<br />ID: @id', ['@type' => $this->transactionType($response[11]), '@id' => $response[6]]);
      $result = array(
        'success' => TRUE,
        'comment' => $message,
        'message' => $message,
        'data' => array('module' => 'uc_authorizenet', 'txn_type' => $response[11], 'txn_id' => $response[6], 'txn_authcode' => $response[4]),
        'uid' => $user->id(),
      );

      // If this was an authorization only transaction...
      if ($txn_type == UC_CREDIT_AUTH_ONLY) {
        // Log the authorization to the order.
        uc_credit_log_authorization($order->id(), $response[6], $amount);
      }
      elseif ($txn_type == UC_CREDIT_PRIOR_AUTH_CAPTURE) {
        uc_credit_log_prior_auth_capture($order->id(), $data['auth_id']);
      }

      // Create a transaction reference if specified in the payment gateway
      // settings and this is an appropriate transaction type.
      if ($this->configuration['cim']['cim_profile'] && in_array($txn_type, array(UC_CREDIT_AUTH_ONLY, UC_CREDIT_AUTH_CAPTURE))) {
        // Ignore the returned message for now; that will appear in the comments.
        _uc_authorizenet_cim_profile_create($order);
      }
    }

    // Don't log this as a payment money wasn't actually captured.
    if (in_array($txn_type, array(UC_CREDIT_AUTH_ONLY))) {
      $result['log_payment'] = FALSE;
    }

    // Build an admin order comment.
    $comment = $this->t('<b>@type</b><br /><b>@status:</b> @message<br />Amount: @amount<br />AVS response: @avs', [
      '@type' => $this->transactionType($response[11]),
      '@status' => $result['success'] ? $this->t('ACCEPTED') : $this->t('REJECTED'),
      '@message' => $response[3],
      '@amount' => uc_currency_format($response[9]),
      '@avs' => $this->avsCodeMessage($response[5]),
    ]);

    // Add the CVV response if enabled.
    if ($this->configuration['uc_credit_cvv_enabled']) {
      $comment .= '<br />' . $this->t('CVV match: @cvv', ['@cvv' => $this->cvvmatchMessage($response[38])]);
    }

    // Save the comment to the order.
    uc_order_comment_save($order->id(), $user->id(), $comment, 'admin');

    return $result;
}

  /**
   * Sends an XML API Request to Authorize.Net.
   *
   * @param string $server
   *   The name of the server to send a request to - 'production' or 'developer'.
   * @param string $xml
   *   The XML to send to Authorize.Net.
   * @param string $callback
   *   The name of the function that should process the response.
   *
   * @return bool
   *   TRUE or FALSE indicating the success of the API request.
   */
  protected function uc_authorizenet_xml_api($server, $xml) {
    if ($server == 'production') {
      $post_url = 'https://api.authorize.net/xml/v1/request.api';
    }
    elseif ($server == 'developer') {
      $post_url = 'https://apitest.authorize.net/xml/v1/request.api';
    }
    else {
      return FALSE;
    }

    $response = \Drupal::httpClient()
      ->setSslVerification(TRUE, TRUE, 2)
      ->setConfig(array('curl.options' => array(CURLOPT_FOLLOWLOCATION => FALSE)))
      ->post($post_url, array("Content-Type: text/xml"), $xml)
      ->send();

    // Log any errors to the watchdog.
    if ($response->isError()) {
      \Drupal::logger('uc_authorizenet')->error('@error', ['@error' => $response->getReasonPhrase()]);
      return FALSE;
    }

    return $response->getBody(TRUE);
  }

  /**
   * Returns the message text for an AVS response code.
   */
  protected function avsCodeMessage($code) {
    $text = $code . ' - ';

    switch ($code) {
      case 'A':
        $text .= $this->t('Address (Street) matches, ZIP does not');
        break;
      case 'B':
        $text .= $this->t('Address information not provided for AVS check');
        break;
      case 'E':
        $text .= $this->t('AVS error');
        break;
      case 'G':
        $text .= $this->t('Non-U.S. Card Issuing Bank');
        break;
      case 'N':
        $text .= $this->t('No Match on Address (Street) or ZIP');
        break;
      case 'P':
        $text .= $this->t('AVS not applicable for this transaction');
        break;
      case 'R':
        $text .= $this->t('Retry – System unavailable or timed out');
        break;
      case 'S':
        $text .= $this->t('Service not supported by issuer');
        break;
      case 'U':
        $text .= $this->t('Address information is unavailable');
        break;
      case 'W':
        $text .= $this->t('Nine digit ZIP matches, Address (Street) does not');
        break;
      case 'X':
        $text .= $this->t('Address (Street) and nine digit ZIP match');
        break;
      case 'Y':
        $text .= $this->t('Address (Street) and five digit ZIP match');
        break;
      case 'Z':
        $text .= $this->t('Five digit ZIP matches, Address (Street) does not');
        break;
    }

    return $text;
  }

  /**
   * Returns the message text for a CVV match.
   */
  protected function cvvmatchMessage($code) {
    $text = $code . ' - ';

    switch ($code) {
      case 'M':
        $text .= $this->t('Match');
        break;
      case 'N':
        $text .= $this->t('No Match');
        break;
      case 'P':
        $text .= $this->t('Not Processed');
        break;
      case 'S':
        $text .= $this->t('Should have been present');
        break;
      case 'U':
        $text .= $this->t('Issuer unable to process request');
        break;
    }

    return $text;
  }

  /**
   * Returns the title of the transaction type.
   */
  protected function transactionType($type) {
    switch (strtoupper($type)) {
      case 'AUTH_CAPTURE':
        return $this->t('Authorization and capture');
      case 'AUTH_ONLY':
        return $this->t('Authorization only');
      case 'PRIOR_AUTH_CAPTURE':
        return $this->t('Prior authorization capture');
      case 'CAPTURE_ONLY':
        return $this->t('Capture only');
      case 'CREDIT':
        return $this->t('Credit');
      case 'VOID':
        return $this->t('Void');
    }
  }

  /**
   * Returns the Auth.Net transaction type corresponding to a UC type.
   */
  protected function transactionTypeMap($type) {
    switch ($type) {
      case UC_CREDIT_AUTH_ONLY:
        return 'AUTH_ONLY';
      case UC_CREDIT_PRIOR_AUTH_CAPTURE:
        return 'PRIOR_AUTH_CAPTURE';
      case UC_CREDIT_AUTH_CAPTURE:
        return 'AUTH_CAPTURE';
      case UC_CREDIT_CREDIT:
        return 'CREDIT';
      case UC_CREDIT_VOID:
        return 'VOID';
    }
  }

}
