<?php

namespace Drupal\uc_paypal\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\ExpressPaymentMethodPluginInterface;
use Drupal\uc_payment\OffsitePaymentMethodPluginInterface;
use GuzzleHttp\Exception\TransferException;

/**
 * Defines the PayPal Express Checkout payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "paypal_ec",
 *   name = @Translation("PayPal Express Checkout")
 * )
 */
class PayPalExpressCheckout extends PayPalPaymentMethodPluginBase implements ExpressPaymentMethodPluginInterface, OffsitePaymentMethodPluginInterface {

  /**
   * The payment method entity ID that is using this plugin.
   *
   * @var string
   */
  protected $methodId;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'ec_landingpage_style' => 'Billing',
      'ec_rqconfirmed_addr' => FALSE,
      'ec_review_shipping' => TRUE,
      'ec_review_company' => TRUE,
      'ec_review_phone' => TRUE,
      'ec_review_comment' => TRUE,
      'wpp_cc_txn_type' => 'Sale',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Generic PayPal settings from base class.
    $form = parent::buildConfigurationForm($form, $form_state);

    // Express Checkout specific settings.
    $form['ec_landingpage_style'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Default PayPal landing page'),
      '#options' => array(
        'Billing' => $this->t('Credit card submission form.'),
        'Login' => $this->t('Account login form.'),
      ),
      '#default_value' => $this->configuration['ec_landingpage_style'],
    );
    $form['ec_rqconfirmed_addr'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Require Express Checkout users to use a PayPal confirmed shipping address.'),
      '#default_value' => $this->configuration['ec_rqconfirmed_addr'],
    );
    $form['ec_review_shipping'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the shipping select form on the Review payment page.'),
      '#default_value' => $this->configuration['ec_review_shipping'],
    );
    $form['ec_review_company'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the company name box on the Review payment page.'),
      '#default_value' => $this->configuration['ec_review_company'],
    );
    $form['ec_review_phone'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the contact phone number box on the Review payment page.'),
      '#default_value' => $this->configuration['ec_review_phone'],
    );
    $form['ec_review_comment'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the comment text box on the Review payment page.'),
      '#default_value' => $this->configuration['ec_review_comment'],
    );
    $form['wpp_cc_txn_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Payment action'),
      '#description' => $this->t('"Complete sale" will authorize and capture the funds at the time the payment is processed.<br>"Authorization" will only reserve funds on the card to be captured later through your PayPal account.'),
      '#options' => array(
        'Sale' => $this->t('Complete sale'),
        'Authorization' => $this->t('Authorization'),
      ),
      '#default_value' => $this->configuration['wpp_cc_txn_type'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['ec_landingpage_style'] = $form_state->getValue('ec_landingpage_style');
    $this->configuration['ec_rqconfirmed_addr'] = $form_state->getValue('ec_rqconfirmed_addr');
    $this->configuration['ec_review_shipping'] = $form_state->getValue('ec_review_shipping');
    $this->configuration['ec_review_company'] = $form_state->getValue('ec_review_company');
    $this->configuration['ec_review_phone'] = $form_state->getValue('ec_review_phone');
    $this->configuration['ec_review_comment'] = $form_state->getValue('ec_review_comment');
    $this->configuration['wpp_cc_txn_type'] = $form_state->getValue('wpp_cc_txn_type');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $txn_id = db_query("SELECT txn_id FROM {uc_payment_paypal_ipn} WHERE order_id = :id ORDER BY received ASC", [':id' => $order->id()])->fetchField();
    if (empty($txn_id)) {
      $txn_id = $this->t('Unknown');
    }

    $build['#markup'] = $this->t('Transaction ID:<br />@txn_id', ['@txn_id' => $txn_id]);
    return $build;
  }

  /**
   * Redirect to PayPal Express Checkout Mark Flow.
   *
   * This is used when the user does not use the cart button, but follows the
   * normal checkout process and selects Express Checkout as a payment method.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being processed.
   *
   * @return array
   *   The form structure.
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $session = \Drupal::service('session');
    if ($session->has('TOKEN') && $session->has('PAYERID')) {
      // If the session variables are set, then the user already gave their
      // details via Shortcut Flow, so we do not need to redirect them here.
      return [];
    }

    $address = $order->getAddress('delivery');
    $request = array(
      'METHOD' => 'SetExpressCheckout',
      'RETURNURL' => Url::fromRoute('uc_paypal.ec_complete', [], ['absolute' => TRUE])->toString(),
      'CANCELURL' => Url::fromRoute('uc_cart.checkout_review', [], ['absolute' => TRUE])->toString(),
      'AMT' => uc_currency_format($order->getTotal(), FALSE, FALSE, '.'),
      'CURRENCYCODE' => $order->getCurrency(),
      'PAYMENTACTION' => $this->configuration['wpp_cc_txn_type'],
      'DESC' => $this->t('Order @order_id at @store', ['@order_id' => $order->id(), '@store' => uc_store_name()]),
      'INVNUM' => $order->id() . '-' . REQUEST_TIME,
      'REQCONFIRMSHIPPING' => $this->configuration['ec_rqconfirmed_addr'],
      'ADDROVERRIDE' => 1,
      'BUTTONSOURCE' => 'Ubercart_ShoppingCart_EC_US',
      'NOTIFYURL' => Url::fromRoute('uc_paypal.ipn', [], ['absolute' => TRUE])->toString(),
      'SHIPTONAME' => substr($address->first_name . ' ' . $address->last_name, 0, 32),
      'SHIPTOSTREET' => substr($address->street1, 0, 100),
      'SHIPTOSTREET2' => substr($address->street2, 0, 100),
      'SHIPTOCITY' => substr($address->city, 0, 40),
      'SHIPTOSTATE' => $address->zone,
      'SHIPTOCOUNTRYCODE' => $address->country,
      'SHIPTOZIP' => substr($address->postal_code, 0, 20),
      'PHONENUM' => substr($address->phone, 0, 20),
      'LANDINGPAGE' => $this->configuration['ec_landingpage_style'],
    );

    if (!$order->isShippable()) {
      $request['NOSHIPPING'] = 1;
      unset($request['ADDROVERRIDE']);
    }

    $response = $this->sendNvpRequest($request);

    if ($response['ACK'] != 'Success') {
      \Drupal::logger('uc_paypal')->error('NVP API request failed with @code: @message', ['@code' => $response['L_ERRORCODE0'], '@message' => $response['L_LONGMESSAGE0']]);
      return $this->t('PayPal reported an error: @code: @message', ['@code' => $response['L_ERRORCODE0'], '@message' => $response['L_LONGMESSAGE0']]);
    }

    $session->set('TOKEN', $response['TOKEN']);

    $sandbox = strpos($this->configuration['wpp_server'], 'sandbox') > 0 ? 'sandbox.' : '';
    $url = 'https://www.' . $sandbox . 'paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token=' . $response['TOKEN'];
    $form['#action'] = $url;

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit order'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(OrderInterface $order) {
    $session = \Drupal::service('session');

    $shipping = 0;
    if (is_array($order->line_items)) {
      foreach ($order->line_items as $item) {
        if ($item['type'] == 'shipping') {
          $shipping += $item['amount'];
        }
      }
    }

    $tax = 0;
    if (\Drupal::moduleHandler()->moduleExists('uc_tax')) {
      foreach (uc_tax_calculate($order) as $tax_item) {
        $tax += $tax_item->amount;
      }
    }

    $subtotal = $order->getTotal() - $tax - $shipping;

    $response = $this->sendNvpRequest([
      'METHOD' => 'DoExpressCheckoutPayment',
      'TOKEN' => $session->get('TOKEN'),
      'PAYMENTACTION' => $this->configuration['wpp_cc_txn_type'],
      'PAYERID' => $session->get('PAYERID'),
      'AMT' => uc_currency_format($order->getTotal(), FALSE, FALSE, '.'),
      'DESC' => $this->t('Order @order_id at @store', ['@order_id' => $order->id(), '@store' => uc_store_name()]),
      'INVNUM' => $order->id() . '-' . REQUEST_TIME,
      'BUTTONSOURCE' => 'Ubercart_ShoppingCart_EC_US',
      'NOTIFYURL' => Url::fromRoute('uc_paypal.ipn', [], ['absolute' => TRUE])->toString(),
      'ITEMAMT' => uc_currency_format($subtotal, FALSE, FALSE, '.'),
      'SHIPPINGAMT' => uc_currency_format($shipping, FALSE, FALSE, '.'),
      'TAXAMT' => uc_currency_format($tax, FALSE, FALSE, '.'),
      'CURRENCYCODE' => $order->getCurrency(),
    ]);

    if ($response['ACK'] != 'Success') {
      \Drupal::logger('uc_paypal')->error('NVP API request failed with @code: @message', ['@code' => $response['L_ERRORCODE0'], '@message' => $response['L_LONGMESSAGE0']]);
      return $this->t('PayPal reported an error: @code: @message', ['@code' => $response['L_ERRORCODE0'], '@message' => $response['L_LONGMESSAGE0']]);
    }

    $session->remove('TOKEN');
    $session->remove('PAYERID');
  }

  /**
   * {@inheritdoc}
   */
  public function getExpressButton($method_id) {
    $this->methodId = $method_id;
    return [
      '#type' => 'image_button',
      '#name' => 'paypal_ec',
      '#src' => 'https://www.paypal.com/en_US/i/btn/btn_xpressCheckoutsm.gif',
      '#title' => $this->t('Checkout with PayPal.'),
      '#submit' => ['::submitForm', [$this, 'submitExpressForm']],
    ];
  }

  /**
   * Submit callback for the express checkout button.
   */
  public function submitExpressForm(array &$form, FormStateInterface $form_state) {
    $items = \Drupal::service('uc_cart.manager')->get()->getContents();

    if (empty($items)) {
      drupal_set_message($this->t('You do not have any items in your shopping cart.'));
      return;
    }

    $order = Order::create([
      'uid' => \Drupal::currentUser()->id(),
      'payment_method' => $this->methodId,
    ]);
    $order->products = array();
    foreach ($items as $item) {
      $order->products[] = $item->toOrderProduct();
    }
    $order->save();

    $response = $this->sendNvpRequest([
      'METHOD' => 'SetExpressCheckout',
      'RETURNURL' => Url::fromRoute('uc_paypal.ec_review', [], ['absolute' => TRUE])->toString(),
      'CANCELURL' => Url::fromRoute('uc_cart.cart', [], ['absolute' => TRUE])->toString(),
      'AMT' => uc_currency_format($order->getSubtotal(), FALSE, FALSE, '.'),
      'CURRENCYCODE' => $order->getCurrency(),
      'PAYMENTACTION' => $this->configuration['wpp_cc_txn_type'],
      'DESC' => $this->t('Order @order_id at @store', ['@order_id' => $order->id(), '@store' => uc_store_name()]),
      'INVNUM' => $order->id() . '-' . REQUEST_TIME,
      'REQCONFIRMSHIPPING' => $this->configuration['ec_rqconfirmed_addr'],
      'BUTTONSOURCE' => 'Ubercart_ShoppingCart_EC_US',
      'NOTIFYURL' => Url::fromRoute('uc_paypal.ipn', [], ['absolute' => TRUE])->toString(),
      'LANDINGPAGE' => $this->configuration['ec_landingpage_style'],
    ]);

    if ($response['ACK'] != 'Success') {
      \Drupal::logger('uc_paypal')->error('NVP API request failed with @code: @message', ['@code' => $response['L_ERRORCODE0'], '@message' => $response['L_LONGMESSAGE0']]);
      drupal_set_message($this->t('PayPal reported an error: @code: @message', ['@code' => $response['L_ERRORCODE0'], '@message' => $response['L_LONGMESSAGE0']]), 'error');
      return;
    }

    $session = \Drupal::service('session');
    $session->set('cart_order', $order->id());
    $session->set('TOKEN', $response['TOKEN']);

    $sandbox = strpos($this->configuration['wpp_server'], 'sandbox') > 0 ? 'sandbox.' : '';
    $url = 'https://www.' . $sandbox . 'paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=' . $response['TOKEN'];
    $form_state->setResponse(new TrustedRedirectResponse($url));
  }

  /**
   * Form constructor for the express checkout review form.
   */
  public function getExpressReviewForm(array $form, FormStateInterface $form_state, OrderInterface $order) {
    // Required by QuotePane::prepare().
    $form['#tree'] = TRUE;

    // @todo: Replace with PayPal shipping callback?
    // @todo: Make a simpler way of getting and applying shipping quotes.
    if ($this->configuration['ec_review_shipping'] && \Drupal::moduleHandler()->moduleExists('uc_quote') && $order->isShippable()) {
      /** @var \Drupal\uc_cart\CheckoutPanePluginInterface $pane */
      $pane = \Drupal::service('plugin.manager.uc_cart.checkout_pane')->createInstance('quotes');
      $pane->prepare($order, $form, $form_state);

      $form['panes']['quotes'] = array(
        '#type' => 'details',
        '#title' => $this->t('Shipping cost'),
        '#open' => TRUE,
      );
      $form['panes']['quotes'] += $pane->view($order, $form, $form_state);
      $form['panes']['quotes']['quotes']['quote_option']['#required'] = TRUE;
      unset($form['panes']['quotes']['#description']);
      unset($form['panes']['quotes']['quote_button']);
    }

    $address = $order->getAddress('delivery');

    // @todo: Replace with "BUSINESS" from PayPal
    if ($this->configuration['ec_review_company']) {
      $form['delivery_company'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Company'),
        '#description' => $order->isShippable() ? $this->t('Leave blank if shipping to a residence.') : '',
        '#default_value' => $address->company,
      );
    }

    // @todo: Replace with "SHIPTOPHONENUM" from PayPal
    if ($this->configuration['ec_review_phone']) {
      $form['delivery_phone'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Contact phone number'),
        '#default_value' => $address->phone,
        '#size' => 24,
      );
    }

    // @todo: Replace with "NOTE" from PayPal
    if ($this->configuration['ec_review_comment']) {
      $form['order_comments'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Order comments'),
        '#description' => $this->t('Special instructions or notes regarding your order.'),
      );
    }

    return $form;
  }

  /**
   * Form constructor for the express checkout review form.
   */
  public function submitExpressReviewForm(array $form, FormStateInterface $form_state, OrderInterface $order) {
    if (!empty($form['panes']['quotes']['quotes'])) {
      \Drupal::service('plugin.manager.uc_cart.checkout_pane')
        ->createInstance('quotes')
        ->prepare($order, $form, $form_state);
    }

    $address = $order->getAddress('delivery');
    if ($this->configuration['ec_review_company']) {
      $address->company = $form_state->getValue('delivery_company');
    }
    if ($this->configuration['ec_review_phone']) {
      $address->phone = $form_state->getValue('delivery_phone');
    }
    $order->setAddress('delivery', $address);

    if ($this->configuration['ec_review_comment'] && $form_state->getValue('order_comments')) {
      db_delete('uc_order_comments')
        ->condition('order_id', $order->id())
        ->execute();
      uc_order_comment_save($order->id(), 0, $form_state->getValue('order_comments'), 'order');
    }

    $order->save();
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

}
