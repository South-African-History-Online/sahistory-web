<?php

namespace Drupal\uc_credit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines a base credit card payment method plugin implementation.
 */
abstract class CreditCardPaymentMethodBase extends PaymentMethodPluginBase {

  /**
   * Returns the set of fields which are used by this payment method.
   *
   * @return array
   *   An array with keys 'cvv', 'owner', 'start', 'issue', 'bank' and 'type'.
   */
  public function getEnabledFields() {
    return [
      'cvv' => TRUE,
      'owner' => FALSE,
      'start' => FALSE,
      'issue' => FALSE,
      'bank' => FALSE,
      'type' => FALSE,
    ];
  }

  /**
   * Returns the set of card types which are used by this payment method.
   *
   * @return array
   *   An array with keys as needed by the chargeCard() method and values
   *   that can be displayed to the customer.
   */
  public function getEnabledTypes() {
    return [
      'visa' => $this->t('Visa'),
      'mastercard' => $this->t('MasterCard'),
      'discover' => $this->t('Discover'),
      'amex' => $this->t('American Express'),
    ];
  }

  /**
   * Returns the set of transaction types allowed by this payment method.
   *
   * @return array
   *   An array with values UC_CREDIT_AUTH_ONLY, UC_CREDIT_PRIOR_AUTH_CAPTURE,
   *   UC_CREDIT_AUTH_CAPTURE, UC_CREDIT_REFERENCE_SET, UC_CREDIT_REFERENCE_TXN,
   *   UC_CREDIT_REFERENCE_REMOVE, UC_CREDIT_REFERENCE_CREDIT, UC_CREDIT_CREDIT
   *   and UC_CREDIT_VOID.
   */
  public function getTransactionTypes() {
    return [
      UC_CREDIT_AUTH_CAPTURE,
      UC_CREDIT_AUTH_ONLY,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label) {
    $build['#attached']['library'][] = 'uc_credit/uc_credit.styles';
    $build['label'] = array(
      '#plain_text' => $label,
    );
    $cc_types = $this->getEnabledTypes();
    foreach ($cc_types as $type => $description) {
      $build['image'][$type] = array(
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'uc_credit') . '/images/' . $type . '.gif',
        '#alt' => $description,
        '#attributes' => array('class' => array('uc-credit-cctype', 'uc-credit-cctype-' . $type)),
      );
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'txn_type' => UC_CREDIT_AUTH_CAPTURE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['txn_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Transaction type'),
      '#default_value' => $this->configuration['txn_type'],
      '#options' => [
        UC_CREDIT_AUTH_CAPTURE => $this->t('Authorize and capture immediately'),
        UC_CREDIT_AUTH_ONLY => $this->t('Authorization only'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['txn_type'] = $form_state->getValue('txn_type');
  }

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $build = array(
      '#type' => 'container',
      '#attributes' => array('class' => 'uc-credit-form')
    );
    $build['#attached']['library'][] = 'uc_credit/uc_credit.styles';
    $build['cc_policy'] = array(
      '#prefix' => '<p>',
      '#markup' => $this->t('Your billing information must match the billing address for the credit card entered below or we will be unable to process your payment.'),
      '#suffix' => '</p>',
    );

    $order->payment_details = array();

    // Encrypted data in the session is from the user returning from the review page.
    $session = \Drupal::service('session');
    if ($session->has('sescrd')) {
      $order->payment_details = uc_credit_cache($session->get('sescrd'));
      $build['payment_details_data'] = array(
        '#type' => 'hidden',
        '#value' => base64_encode($session->get('sescrd')),
      );
      $session->remove('sescrd');
    }
    elseif (isset($_POST['panes']['payment']['details']['payment_details_data'])) {
      // Copy any encrypted data that was POSTed in.
      $build['payment_details_data'] = array(
        '#type' => 'hidden',
        '#value' => $_POST['panes']['payment']['details']['payment_details_data'],
      );
    }

    $fields = $this->getEnabledFields();
    if (!empty($fields['type'])) {
      $build['cc_type'] = array(
        '#type' => 'select',
        '#title' => $this->t('Card type'),
        '#options' => $this->getEnabledTypes(),
        '#default_value' => isset($order->payment_details['cc_type']) ? $order->payment_details['cc_type'] : NULL,
      );
    }

    if (!empty($fields['owner'])) {
      $build['cc_owner'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Card owner'),
        '#default_value' => isset($order->payment_details['cc_owner']) ? $order->payment_details['cc_owner'] : '',
        '#attributes' => array('autocomplete' => 'off'),
        '#size' => 32,
        '#maxlength' => 64,
      );
    }

    // Set up the default CC number on the credit card form.
    if (!isset($order->payment_details['cc_number'])) {
      $default_num = NULL;
    }
    elseif (!$this->validateCardNumber($order->payment_details['cc_number'])) {
      // Display the number as is if it does not validate so it can be corrected.
      $default_num = $order->payment_details['cc_number'];
    }
    else {
      // Otherwise default to the last 4 digits.
      $default_num = $this->t('(Last 4) ') . substr($order->payment_details['cc_number'], -4);
    }

    $build['cc_number'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Card number'),
      '#default_value' => $default_num,
      '#attributes' => array('autocomplete' => 'off'),
      '#size' => 20,
      '#maxlength' => 19,
    );

    if (!empty($fields['start'])) {
      $month = isset($order->payment_details['cc_start_month']) ? $order->payment_details['cc_start_month'] : NULL;
      $year = isset($order->payment_details['cc_start_year']) ? $order->payment_details['cc_start_year'] : NULL;
      $year_range = range(date('Y') - 10, date('Y'));
      $build['cc_start_month'] = array(
        '#type' => 'number',
        '#title' => $this->t('Start date'),
        '#options' => array(
          1 => $this->t('01 - January'), 2 => $this->t('02 - February'),
          3 => $this->t('03 - March'), 4 => $this->t('04 - April'),
          5 => $this->t('05 - May'), 6 => $this->t('06 - June'),
          7 => $this->t('07 - July'), 8 => $this->t('08 - August'),
          9 => $this->t('09 - September'), 10 => $this->t('10 - October'),
          11 => $this->t('11 - November'), 12 => $this->t('12 - December'),
        ),
        '#default_value' => $month,
        '#required' => TRUE,
      );
      $build['cc_start_year'] = array(
        '#type' => 'select',
        '#title' => $this->t('Start year'),
        '#title_display' => 'invisible',
        '#options' => array_combine($year_range, $year_range),
        '#default_value' => $year,
        '#field_suffix' => $this->t('(if present)'),
        '#required' => TRUE,
      );
    }

    $month = isset($order->payment_details['cc_exp_month']) ? $order->payment_details['cc_exp_month'] : 1;
    $year = isset($order->payment_details['cc_exp_year']) ? $order->payment_details['cc_exp_year'] : date('Y');
    $year_range = range(date('Y'), date('Y') + 20);
    $build['cc_exp_month'] = array(
      '#type' => 'select',
      '#title' => $this->t('Expiration date'),
      '#options' => array(
        1 => $this->t('01 - January'), 2 => $this->t('02 - February'),
        3 => $this->t('03 - March'), 4 => $this->t('04 - April'),
        5 => $this->t('05 - May'), 6 => $this->t('06 - June'),
        7 => $this->t('07 - July'), 8 => $this->t('08 - August'),
        9 => $this->t('09 - September'), 10 => $this->t('10 - October'),
        11 => $this->t('11 - November'), 12 => $this->t('12 - December'),
      ),
      '#default_value' => $month,
      '#required' => TRUE,
    );
    $build['cc_exp_year'] = array(
      '#type' => 'select',
      '#title' => $this->t('Expiration year'),
      '#title_display' => 'invisible',
      '#options' => array_combine($year_range, $year_range),
      '#default_value' => $year,
      '#field_suffix' => $this->t('(if present)'),
      '#required' => TRUE,
    );

    if (!empty($fields['issue'])) {
      // Set up the default Issue Number on the credit card form.
      if (empty($order->payment_details['cc_issue'])) {
        $default_card_issue = NULL;
      }
      elseif (!$this->validateIssueNumber($order->payment_details['cc_issue'])) {
        // Display the Issue Number as is if it does not validate so it can be
        // corrected.
        $default_card_issue = $order->payment_details['cc_issue'];
      }
      else {
        // Otherwise mask it with dashes.
        $default_card_issue = str_repeat('-', strlen($order->payment_details['cc_issue']));
      }

      $build['cc_issue'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Issue number'),
        '#default_value' => $default_card_issue,
        '#attributes' => array('autocomplete' => 'off'),
        '#size' => 2,
        '#maxlength' => 2,
        '#field_suffix' => $this->t('(if present)'),
      );
    }

    if (!empty($fields['cvv'])) {
      // Set up the default CVV on the credit card form.
      if (empty($order->payment_details['cc_cvv'])) {
        $default_cvv = NULL;
      }
      elseif (!$this->validateCvv($order->payment_details['cc_cvv'])) {
        // Display the CVV as is if it does not validate so it can be corrected.
        $default_cvv = $order->payment_details['cc_cvv'];
      }
      else {
        // Otherwise mask it with dashes.
        $default_cvv = str_repeat('-', strlen($order->payment_details['cc_cvv']));
      }

      $build['cc_cvv'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('CVV'),
        '#default_value' => $default_cvv,
        '#attributes' => array('autocomplete' => 'off'),
        '#size' => 4,
        '#maxlength' => 4,
        '#field_suffix' => array(
          '#theme' => 'uc_credit_cvv_help',
          '#method' => $order->getPaymentMethodId(),
        ),
      );
    }

    if (!empty($fields['bank'])) {
      $build['cc_bank'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Issuing bank'),
        '#default_value' => isset($order->payment_details['cc_bank']) ? $order->payment_details['cc_bank'] : '',
        '#attributes' => array('autocomplete' => 'off'),
        '#size' => 32,
        '#maxlength' => 64,
      );
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReviewTitle() {
    return $this->t('Credit card');
  }

  /**
   * {@inheritdoc}
   */
  public function cartReview(OrderInterface $order) {
    $fields = $this->getEnabledFields();

    if (!empty($fields['type'])) {
      $review[] = array('title' => $this->t('Card type'), 'data' => $order->payment_details['cc_type']);
    }
    if (!empty($fields['owner'])) {
      $review[] = array('title' => $this->t('Card owner'), 'data' => $order->payment_details['cc_owner']);
    }
    $review[] = array('title' => $this->t('Card number'), 'data' => $this->displayCardNumber($order->payment_details['cc_number']));
    if (!empty($fields['start'])) {
      $start = $order->payment_details['cc_start_month'] . '/' . $order->payment_details['cc_start_year'];
      $review[] = array('title' => $this->t('Start date'), 'data' => strlen($start) > 1 ? $start : '');
    }
    $review[] = array('title' => $this->t('Expiration'), 'data' => $order->payment_details['cc_exp_month'] . '/' . $order->payment_details['cc_exp_year']);
    if (!empty($fields['issue'])) {
      $review[] = array('title' => $this->t('Issue number'), 'data' => $order->payment_details['cc_issue']);
    }
    if (!empty($fields['bank'])) {
      $review[] = array('title' => $this->t('Issuing bank'), 'data' => $order->payment_details['cc_bank']);
    }

    return $review;
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $build = array();

    // Add the hidden span for the CC details if possible.
    $account = \Drupal::currentUser();
    if ($account->hasPermission('view cc details')) {
      $rows = array();

      if (!empty($order->payment_details['cc_type'])) {
        $rows[] = $this->t('Card type') . ': ' . $order->payment_details['cc_type'];
      }

      if (!empty($order->payment_details['cc_owner'])) {
        $rows[] = $this->t('Card owner') . ': ' . $order->payment_details['cc_owner'];
      }

      if (!empty($order->payment_details['cc_number'])) {
        $rows[] = $this->t('Card number') . ': ' . $this->displayCardNumber($order->payment_details['cc_number']);
      }

      if (!empty($order->payment_details['cc_start_month']) && !empty($order->payment_details['cc_start_year'])) {
        $rows[] = $this->t('Start date') . ': ' . $order->payment_details['cc_start_month'] . '/' . $order->payment_details['cc_start_year'];
      }

      if (!empty($order->payment_details['cc_exp_month']) && !empty($order->payment_details['cc_exp_year'])) {
        $rows[] = $this->t('Expiration') . ': ' . $order->payment_details['cc_exp_month'] . '/' . $order->payment_details['cc_exp_year'];
      }

      if (!empty($order->payment_details['cc_issue'])) {
        $rows[] = $this->t('Issue number') . ': ' . $order->payment_details['cc_issue'];
      }

      if (!empty($order->payment_details['cc_bank'])) {
        $rows[] = $this->t('Issuing bank') . ': ' . $order->payment_details['cc_bank'];
      }

      $build['cc_info'] = array(
        '#markup' => implode('<br />', $rows) . '<br />',
      );
    }

    // Add the form to process the card if applicable.
    if ($account->hasPermission('process credit cards')) {
      $build['terminal'] = [
        '#type' => 'link',
        '#title' => $this->t('Process card'),
        '#url' => Url::fromRoute('uc_credit.terminal', [
          'uc_order' => $order->id(),
          'uc_payment_method' => $order->getPaymentMethodId(),
        ]),
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function customerView(OrderInterface $order) {
    $build = array();

    if (!empty($order->payment_details['cc_number'])) {
      $build['#markup'] = $this->t('Card number') . ':<br />' . $this->displayCardNumber($order->payment_details['cc_number']);
    }

    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function orderEditDetails(OrderInterface $order) {
    return $this->t('Use the terminal available through the<br />%button button on the View tab to<br />process credit card payments.', ['%button' => $this->t('Process card')]);
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    if (!$form_state->hasValue(['panes', 'payment', 'details', 'cc_number'])) {
      return;
    }

    $fields = $this->getEnabledFields();

    // Fetch the CC details from the $_POST directly.
    $cc_data = $form_state->getValue(['panes', 'payment', 'details']);
    $cc_data['cc_number'] = str_replace(' ', '', $cc_data['cc_number']);

    // Recover cached CC data in form state, if it exists.
    if (isset($cc_data['payment_details_data'])) {
      $cache = uc_credit_cache(base64_decode($cc_data['payment_details_data']));
      unset($cc_data['payment_details_data']);
    }

    // Account for partial CC numbers when masked by the system.
    if (substr($cc_data['cc_number'], 0, strlen(t('(Last4)'))) == $this->t('(Last4)')) {
      // Recover the number from the encrypted data in the form if truncated.
      if (isset($cache['cc_number'])) {
        $cc_data['cc_number'] = $cache['cc_number'];
      }
      else {
        $cc_data['cc_number'] = '';
      }
    }

    // Account for masked CVV numbers.
    if (!empty($cc_data['cc_cvv']) && $cc_data['cc_cvv'] == str_repeat('-', strlen($cc_data['cc_cvv']))) {
      // Recover the number from the encrypted data in $_POST if truncated.
      if (isset($cache['cc_cvv'])) {
        $cc_data['cc_cvv'] = $cache['cc_cvv'];
      }
      else {
        $cc_data['cc_cvv'] = '';
      }
    }

    // Go ahead and put the CC data in the payment details array.
    $order->payment_details = $cc_data;

    // Default our value for validation.
    $return = TRUE;

    // Make sure an owner value was entered.
    if (!empty($fields['owner']) && empty($cc_data['cc_owner'])) {
      $form_state->setErrorByName('panes][payment][details][cc_owner', $this->t('Enter the owner name as it appears on the card.'));
      $return = FALSE;
    }

    // Validate the credit card number.
    if (!$this->validateCardNumber($cc_data['cc_number'])) {
      $form_state->setErrorByName('panes][payment][details][cc_number', $this->t('You have entered an invalid credit card number.'));
      $return = FALSE;
    }

    // Validate the start date (if entered).
    if (!empty($fields['start']) && !$this->validateStartDate($cc_data['cc_start_month'], $cc_data['cc_start_year'])) {
      $form_state->setErrorByName('panes][payment][details][cc_start_month', $this->t('The start date you entered is invalid.'));
      $form_state->setErrorByName('panes][payment][details][cc_start_year');
      $return = FALSE;
    }

    // Validate the card expiration date.
    if (!$this->validateExpirationDate($cc_data['cc_exp_month'], $cc_data['cc_exp_year'])) {
      $form_state->setErrorByName('panes][payment][details][cc_exp_month', $this->t('The credit card you entered has expired.'));
      $form_state->setErrorByName('panes][payment][details][cc_exp_year');
      $return = FALSE;
    }

    // Validate the issue number (if entered). With issue numbers, '01' is
    // different from '1', but is_numeric() is still appropriate.
    if (!empty($fields['issue']) && !$this->validateIssueNumber($cc_data['cc_issue'])) {
      $form_state->setErrorByName('panes][payment][details][cc_issue', $this->t('The issue number you entered is invalid.'));
      $return = FALSE;
    }

    // Validate the CVV number if enabled.
    if (!empty($fields['cvv']) && !$this->validateCvv($cc_data['cc_cvv'])) {
      $form_state->setErrorByName('panes][payment][details][cc_cvv', $this->t('You have entered an invalid CVV number.'));
      $return = FALSE;
    }

    // Validate the bank name if enabled.
    if (!empty($fields['bank']) && empty($cc_data['cc_bank'])) {
      $form_state->setErrorByName('panes][payment][details][cc_bank', $this->t('You must enter the issuing bank for that card.'));
      $return = FALSE;
    }

    // Initialize the encryption key and class.
    $key = uc_credit_encryption_key();
    $crypt = \Drupal::service('uc_store.encryption');

    // Store the encrypted details in the session for the next pageload.
    // We are using base64_encode() because the encrypt function works with a
    // limited set of characters, not supporting the full Unicode character
    // set or even extended ASCII characters that may be present.
    // base64_encode() converts everything to a subset of ASCII, ensuring that
    // the encryption algorithm does not mangle names.
    $session = \Drupal::service('session');
    $session->set('sescrd', $crypt->encrypt($key, base64_encode(serialize($order->payment_details))));

    // Log any errors to the watchdog.
    uc_store_encryption_errors($crypt, 'uc_credit');

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function orderLoad(OrderInterface $order) {
    // Load the CC details from the credit cache if available.
    $order->payment_details = uc_credit_cache();

    // Otherwise load any details that might be stored in the data array.
    if (empty($order->payment_details) && isset($order->data->cc_data)) {
      $order->payment_details = uc_credit_cache($order->data->cc_data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderSave(OrderInterface $order) {
    // Save only some limited, PCI compliant data.
    $cc_data = $order->payment_details;
    $cc_data['cc_number'] = substr($cc_data['cc_number'], -4);
    unset($cc_data['cc_cvv']);

    // Stuff the serialized and encrypted CC details into the array.
    $crypt = \Drupal::service('uc_store.encryption');
    $order->data->cc_data = $crypt->encrypt(uc_credit_encryption_key(), base64_encode(serialize($cc_data)));
    uc_store_encryption_errors($crypt, 'uc_credit');
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(OrderInterface $order) {
    // Attempt to process the credit card payment.
    if (!$this->processPayment($order, $order->getTotal(), $this->configuration['txn_type'])) {
      return $this->t('We were unable to process your credit card payment. Please verify your details and try again.');
    }
  }

  /**
   * Process a payment through the credit card gateway.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being processed.
   * @param float $amount
   *   The amount of the payment we're attempting to collect.
   * @param string $txn_type
   *   The transaction type, one of the UC_CREDIT_* constants.
   * @param string $reference
   *   (optional) The payment reference, where needed for specific transaction
   *   types.
   *
   * @return bool
   *   TRUE or FALSE indicating whether or not the payment was processed.
   */
  public function processPayment(OrderInterface $order, $amount, $txn_type, $reference = NULL) {
    // Ensure the cached details are loaded.
    // @todo Figure out which parts of this call are strictly necessary.
    $this->orderLoad($order);

    $result = $this->chargeCard($order, $amount, $txn_type, $reference);

    // If the payment processed successfully...
    if ($result['success'] === TRUE) {
      // Log the payment to the order if not disabled.
      if (!isset($result['log_payment']) || $result['log_payment'] !== FALSE) {
        uc_payment_enter($order->id(), $this->getPluginDefinition()['id'], $amount, empty($result['uid']) ? 0 : $result['uid'], empty($result['data']) ? '' : $result['data'], empty($result['comment']) ? '' : $result['comment']);
      }
    }
    else {
      // Otherwise display the failure message in the logs.
      \Drupal::logger('uc_payment')->warning('Payment failed for order @order_id: @message', ['@order_id' => $order->id(), '@message' => $result['message'], 'link' => $order->toLink($this->t('view order'))->toString()]);
    }

    return $result['success'];
  }

  /**
   * Called when a credit card should be processed.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being processed. Credit card details supplied by the
   *   user are available in $order->payment_details[].
   * @param float $amount
   *   The amount that should be charged.
   * @param string $txn_type
   *   The transaction type, one of the UC_CREDIT_* constants.
   * @param string $reference
   *   (optional) The payment reference, where needed for specific transaction
   *   types.
   *
   * @return array
   *   Returns an associative array with the following members:
   *   - "success": TRUE if the transaction succeeded, FALSE otherwise.
   *   - "message": a human-readable message describing the result of the
   *     transaction.
   *   - "log_payment": TRUE if the transaction should be regarded as a
   *     successful payment.
   *   - "uid": The user ID of the person logging the payment, or 0 if the
   *     payment was processed automatically.
   *   - "comment": The comment string, markup allowed, to enter in the
   *     payment log.
   *   - "data": Any data that should be serialized and stored with the payment.
   *
   * @todo: Replace the return array with a typed object.
   */
  abstract protected function chargeCard(OrderInterface $order, $amount, $txn_type, $reference = NULL);

  /**
   * Returns a credit card number with appropriate masking.
   *
   * @param string $number
   *   Credit card number as a string.
   *
   * @return string
   *   Masked credit card number - just the last four digits.
   */
  protected function displayCardNumber($number) {
    if (strlen($number) == 4) {
      return t('(Last 4) ') . $number;
    }

    return str_repeat('-', 12) . substr($number, -4);
  }

  /**
   * Validates a credit card number during checkout.
   *
   * @param string $number
   *   Credit card number as a string.
   *
   * @return bool
   *   TRUE if card number is valid according to the Luhn algorithm.
   *
   * @see https://en.wikipedia.org/wiki/Luhn_algorithm
   */
  protected function validateCardNumber($number) {
    $id = substr($number, 0, 1);
    $types = $this->getEnabledTypes();
    if (($id == 3 && empty($types['amex'])) ||
      ($id == 4 && empty($types['visa'])) ||
      ($id == 5 && empty($types['mastercard'])) ||
      ($id == 6 && empty($types['discover'])) ||
      !ctype_digit($number)) {
      return FALSE;
    }

    $total = 0;
    for ($i = 0; $i < strlen($number); $i++) {
      $digit = substr($number, $i, 1);
      if ((strlen($number) - $i - 1) % 2) {
        $digit *= 2;
        if ($digit > 9) {
          $digit -= 9;
        }
      }
      $total += $digit;
    }

    if ($total % 10 != 0) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validates a CVV number during checkout.
   *
   * @param string $cvv
   *   CVV number as a string.
   *
   * @return bool
   *   TRUE if CVV has the correct number of digits.
   */
  protected function validateCvv($cvv) {
    $digits = array();

    $types = $this->getEnabledTypes();
    if (!empty($types['visa']) ||
      !empty($types['mastercard']) ||
      !empty($types['discover'])) {
      $digits[] = 3;
    }
    if (!empty($types['amex'])) {
      $digits[] = 4;
    }

    // Fail validation if it's non-numeric or an incorrect length.
    if (!is_numeric($cvv) || (count($digits) > 0 && !in_array(strlen($cvv), $digits))) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validates a start date on a card.
   *
   * @param int $month
   *   The 1 or 2-digit numeric representation of the month, i.e. 1, 6, 12.
   * @param int $year
   *   The 4-digit numeric representation of the year, i.e. 2008.
   *
   * @return bool
   *   TRUE for cards whose start date is blank (both month and year) or in the
   *   past, FALSE otherwise.
   */
  protected function validateStartDate($month, $year) {
    if (empty($month) && empty($year)) {
      return TRUE;
    }

    if (empty($month) || empty($year)) {
      return FALSE;
    }

    if ($year > date('Y')) {
      return FALSE;
    }
    elseif ($year == date('Y')) {
      if ($month > date('n')) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Validates an expiration date on a card.
   *
   * @param int $month
   *   The 1 or 2-digit numeric representation of the month, i.e. 1, 6, 12.
   * @param int $year
   *   The 4-digit numeric representation of the year, i.e. 2008.
   *
   * @return bool
   *   TRUE if expiration date is in the future, FALSE otherwise.
   */
  protected function validateExpirationDate($month, $year) {
    if ($year < date('Y')) {
      return FALSE;
    }
    elseif ($year == date('Y')) {
      if ($month < date('n')) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Validates an issue number on a card.
   *
   * @param string $issue
   *   The issue number.
   *
   * @return bool
   *   TRUE if the issue number if valid, FALSE otherwise.
   */
  protected function validateIssueNumber($issue) {
    if (empty($issue) || (is_numeric($issue) && $issue > 0)) {
      return TRUE;
    }

    return FALSE;
  }

}
