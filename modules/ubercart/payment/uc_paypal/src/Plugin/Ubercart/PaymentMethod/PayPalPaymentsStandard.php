<?php

namespace Drupal\uc_paypal\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\OffsitePaymentMethodPluginInterface;

/**
 * Defines the PayPal Payments Standard payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "paypal_wps",
 *   name = @Translation("PayPal Payments Standard")
 * )
 */
class PayPalPaymentsStandard extends PayPalPaymentMethodPluginBase implements OffsitePaymentMethodPluginInterface {

  /**
   * Returns the set of card types which are used by this payment method.
   *
   * @return array
   *   An array with keys as needed by the chargeCard() method and values
   *   that can be displayed to the customer.
   */
  protected function getEnabledTypes() {
    return [
      'visa' => $this->t('Visa'),
      'mastercard' => $this->t('MasterCard'),
      'discover' => $this->t('Discover'),
      'amex' => $this->t('American Express'),
      'echeck' => $this->t('eCheck'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label) {
    $build['paypal-mark'] = array(
      '#theme' => 'image',
      '#uri' => 'https://www.paypal.com/en_US/i/logo/PayPal_mark_37x23.gif',
      '#alt' => $this->t('PayPal'),
      '#attributes' => array('class' => array('uc-credit-cctype', 'uc-credit-cctype-paypal')),
    );
    $build['label'] = array(
      '#prefix' => ' ',
      '#plain_text' => $this->t('PayPal - pay without sharing your financial information.'),
      '#suffix' => '<br /> ',
    );
    $build['includes'] = array(
      '#plain_text' => $this->t('Includes:') . ' ',
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
    $build['image']['paypal'] = $build['paypal-mark'];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'wps_email' => '',
      'wps_language' => 'US',
      'wps_server' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
      'wps_payment_action' => 'Sale',
      'wps_submit_method' => 'single',
      'wps_no_shipping' => '1',
      'wps_address_override' => TRUE,
      'wps_address_selection' => 'billing',
      'wps_debug_ipn' => FALSE,
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
    $languages = array('AU', 'DE', 'FR', 'IT', 'GB', 'ES', 'US');
    $form['wps_language'] = array(
      '#type' => 'select',
      '#title' => $this->t('PayPal login page language'),
      '#options' => array_combine($languages, $languages),
      '#default_value' => $this->configuration['wps_language'],
    );
    $form['wps_server'] = array(
      '#type' => 'select',
      '#title' => $this->t('PayPal server'),
      '#description' => $this->t('Sign up for and use a Sandbox account for testing.'),
      '#options' => array(
        'https://www.sandbox.paypal.com/cgi-bin/webscr' => ('Sandbox'),
        'https://www.paypal.com/cgi-bin/webscr' => ('Live'),
      ),
      '#default_value' => $this->configuration['wps_server'],
    );
    $form['wps_payment_action'] = array(
      '#type' => 'select',
      '#title' => $this->t('Payment action'),
      '#description' => $this->t('"Complete sale" will authorize and capture the funds at the time the payment is processed.<br />"Authorization" will only reserve funds on the card to be captured later through your PayPal account.'),
      '#options' => array(
        'Sale' => $this->t('Complete sale'),
        'Authorization' => $this->t('Authorization'),
      ),
      '#default_value' => $this->configuration['wps_payment_action'],
    );
    $form['wps_submit_method'] = array(
      '#type' => 'radios',
      '#title' => $this->t('PayPal cart submission method'),
      '#options' => array(
        'single' => $this->t('Submit the whole order as a single line item.'),
        'itemized' => $this->t('Submit an itemized order showing each product and description.'),
      ),
      '#default_value' => $this->configuration['wps_submit_method'],
    );
    $form['wps_no_shipping'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Shipping address prompt in PayPal'),
      '#options' => array(
        '1' => $this->t('Do not show shipping address prompt at PayPal.'),
        '0' => $this->t('Prompt customer to include a shipping address.'),
        '2' => $this->t('Require customer to provide a shipping address.'),
      ),
      '#default_value' => $this->configuration['wps_no_shipping'],
    );
    $form['wps_address_override'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Submit address information to PayPal to override PayPal stored addresses.'),
      '#description' => $this->t('Works best with the first option above.'),
      '#default_value' => $this->configuration['wps_address_override'],
    );
    $form['wps_address_selection'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Sent address selection'),
      '#options' => array(
        'billing' => $this->t('Send billing address to PayPal.'),
        'delivery' => $this->t('Send shipping address to PayPal.'),
      ),
      '#default_value' => $this->configuration['wps_address_selection'],
    );
    $form['wps_debug_ipn'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show debug info in the logs for Instant Payment Notifications.'),
      '#default_value' => $this->configuration['wps_debug_ipn'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['wps_email'] = trim($form_state->getValue('wps_email'));
    $this->configuration['wps_language'] = $form_state->getValue('wps_language');
    $this->configuration['wps_server'] = $form_state->getValue('wps_server');
    $this->configuration['wps_submit_method'] = $form_state->getValue('wps_submit_method');
    $this->configuration['wps_no_shipping'] = $form_state->getValue('wps_no_shipping');
    $this->configuration['wps_address_override'] = $form_state->getValue('wps_address_override');
    $this->configuration['wps_address_selection'] = $form_state->getValue('wps_address_selection');
    $this->configuration['wps_debug_ipn'] = $form_state->getValue('wps_debug_ipn');
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
   * {@inheritdoc}
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $shipping = 0;
    foreach ($order->line_items as $item) {
      if ($item['type'] == 'shipping') {
        $shipping += $item['amount'];
      }
    }

    $tax = 0;
    if (\Drupal::moduleHandler()->moduleExists('uc_tax')) {
      foreach (uc_tax_calculate($order) as $tax_item) {
        $tax += $tax_item->amount;
      }
    }

    $address = $order->getAddress($this->configuration['wps_address_selection']);

    $country = $address->country;
    $phone = '';
    for ($i = 0; $i < strlen($address->phone); $i++) {
      if (is_numeric($address->phone[$i])) {
        $phone .= $address->phone[$i];
      }
    }

    /**
     * night_phone_a: The area code for U.S. phone numbers, or the country code
     *                for phone numbers outside the U.S.
     * night_phone_b: The three-digit prefix for U.S. phone numbers, or the
     *                entire phone number for phone numbers outside the U.S.,
     *                excluding country code.
     * night_phone_c: The four-digit phone number for U.S. phone numbers.
     *                (Not Used for UK numbers)
     */
    if ($country == 'US' || $country == 'CA') {
      $phone = substr($phone, -10);
      $phone_a = substr($phone, 0, 3);
      $phone_b = substr($phone, 3, 3);
      $phone_c = substr($phone, 6, 4);
    }
    else {
      $phone_a = $phone_b = $phone_c = '';
    }

    $data = array(
      // PayPal command variable.
      'cmd' => '_cart',

      // Set the correct codepage.
      'charset' => 'utf-8',

      // IPN control notify URL.
      'notify_url' => Url::fromRoute('uc_paypal.ipn', [], ['absolute' => TRUE])->toString(),

      // Display information.
      'cancel_return' => Url::fromRoute('uc_cart.checkout_review', [], ['absolute' => TRUE])->toString(),
      'no_note' => 1,
      'no_shipping' => $this->configuration['wps_no_shipping'],
      'return' => Url::fromRoute('uc_paypal.wps_complete', ['uc_order' => $order->id()], ['absolute' => TRUE])->toString(),
      'rm' => 1,

      // Transaction information.
      'currency_code' => $order->getCurrency(),
      'handling_cart' => uc_currency_format($shipping, FALSE, FALSE, '.'),
      'invoice' => $order->id() . '-' . \Drupal::service('uc_cart.manager')->get()->getId(),
      'tax_cart' => uc_currency_format($tax, FALSE, FALSE, '.'),

      // Shopping cart specific variables.
      'business' => $this->configuration['wps_email'],
      'upload' => 1,

      'lc' => $this->configuration['wps_language'],

      // Prepopulating forms/address overriding.
      'address1' => substr($address->street1, 0, 100),
      'address2' => substr($address->street2, 0, 100),
      'city' => substr($address->city, 0, 40),
      'country' => $country,
      'email' => $order->getEmail(),
      'first_name' => substr($address->first_name, 0, 32),
      'last_name' => substr($address->last_name, 0, 64),
      'state' => $address->zone,
      'zip' => $address->postal_code,
      'night_phone_a' => $phone_a,
      'night_phone_b' => $phone_b,
      'night_phone_c' => $phone_c,
    );

    if ($this->configuration['wps_address_override']) {
      $data['address_override'] = 1;
    }

    // Account for stores that just want to authorize funds instead of capture.
    if ($this->configuration['wps_payment_action'] == 'Authorization') {
      $data['paymentaction'] = 'authorization';
    }

    if ($this->configuration['wps_submit_method'] == 'itemized') {
      // List individual items.
      $i = 0;
      foreach ($order->products as $item) {
        $i++;
        $data['amount_' . $i] = uc_currency_format($item->price->value, FALSE, FALSE, '.');
        $data['item_name_' . $i] = $item->title->value;
        $data['item_number_' . $i] = $item->model->value;
        $data['quantity_' . $i] = $item->qty->value;

        // PayPal will only display the first two...
        if (!empty($item->data->attributes)) {
          $o = 0;
          foreach ($item->data->attributes as $name => $setting) {
            $data['on' . $o . '_' . $i] = $name;
            $data['os' . $o . '_' . $i] = implode(', ', (array)$setting);
            $o++;
          }
        }
      }

      // Apply discounts (negative amount line items). For example, this handles
      // line items created by uc_coupon.
      $discount = 0;

      foreach ($order->line_items as $item) {
        if ($item['amount'] < 0) {
          // The minus sign is not an error! The discount amount must be positive.
          $discount -= $item['amount'];
        }
      }

      if ($discount != 0) {
        $data['discount_amount_cart'] = $discount;
      }
    }
    else {
      // List the whole cart as a single item to account for fees/discounts.
      $data['amount_1'] = uc_currency_format($order->getTotal() - $shipping - $tax, FALSE, FALSE, '.');
      $data['item_name_1'] = $this->t('Order @order_id at @store', ['@order_id' => $order->id(), '@store' => uc_store_name()]);
    }

    $form['#action'] = $this->configuration['wps_server'];

    foreach ($data as $name => $value) {
      if (!empty($value)) {
        $form[$name] = array('#type' => 'hidden', '#value' => $value);
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit order'),
    );

    return $form;
  }

}
