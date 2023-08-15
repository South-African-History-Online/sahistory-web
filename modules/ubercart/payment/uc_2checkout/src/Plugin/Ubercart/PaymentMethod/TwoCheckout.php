<?php

namespace Drupal\uc_2checkout\Plugin\Ubercart\PaymentMethod;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\OffsitePaymentMethodPluginInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines the 2Checkout payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "2checkout",
 *   name = @Translation("2Checkout"),
 *   redirect = "\Drupal\uc_2checkout\Form\TwoCheckoutForm",
 * )
 */
class TwoCheckout extends PaymentMethodPluginBase implements OffsitePaymentMethodPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label) {
    $build['#attached']['library'][] = 'uc_2checkout/2checkout.styles';
    $build['label'] = array(
      '#plain_text' => $label,
      '#suffix' => '<br />',
    );
    $build['image'] = array(
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'uc_2checkout') . '/images/2co_logo.jpg',
      '#alt' => $this->t('2Checkout'),
      '#attributes' => array('class' => array('uc-2checkout-logo')),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'check' => FALSE,
      'checkout_type' => 'dynamic',
      'demo' => TRUE,
      'language' => 'en',
      'notification_url' => '',
      'secret_word' => 'tango',
      'sid' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['sid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Vendor account number'),
      '#description' => $this->t('Your 2Checkout vendor account number.'),
      '#default_value' => $this->configuration['sid'],
      '#size' => 16,
    );
    $form['secret_word'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Secret word for order verification'),
      '#description' => $this->t('The secret word entered in your 2Checkout account Look and Feel settings.'),
      '#default_value' => $this->configuration['secret_word'],
      '#size' => 16,
    );
    $form['demo'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable demo mode, allowing you to process fake orders for testing purposes.'),
      '#default_value' => $this->configuration['demo'],
    );
    $form['language'] = array(
      '#type' => 'select',
      '#title' => $this->t('Language preference'),
      '#description' => $this->t('Adjust language on 2Checkout pages.'),
      '#options' => array(
        'en' => $this->t('English'),
        'sp' => $this->t('Spanish'),
      ),
      '#default_value' => $this->configuration['language'],
    );
    $form['check'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow customers to choose to pay by credit card or online check.'),
      '#default_value' => $this->configuration['check'],
    );
    $form['checkout_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Checkout type'),
      '#options' => array(
        'dynamic' => $this->t('Dynamic checkout (user is redirected to 2CO)'),
        'direct' => $this->t('Direct checkout (payment page opens in iframe popup)'),
      ),
      '#default_value' => $this->configuration['checkout_type'],
    );
    $form['notification_url'] = array(
      '#type' => 'url',
      '#title' => $this->t('Instant notification settings URL'),
      '#description' => $this->t('Pass this URL to the <a href=":help_url">instant notification settings</a> parameter in your 2Checkout account. This way, any refunds or failed fraud reviews will automatically cancel the Ubercart order.', [':help_url' => Url::fromUri('https://www.2checkout.com/static/va/documentation/INS/index.html')->toString()]),
      '#default_value' => Url::fromRoute('uc_2checkout.notification', [], ['absolute' => TRUE])->toString(),
      '#attributes' => array('readonly' => 'readonly'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['check'] = $form_state->getValue('check');
    $this->configuration['checkout_type'] = $form_state->getValue('checkout_type');
    $this->configuration['demo'] = $form_state->getValue('demo');
    $this->configuration['language'] = $form_state->getValue('language');
    $this->configuration['notification_url'] = $form_state->getValue('notification_url');
    $this->configuration['secret_word'] = $form_state->getValue('secret_word');
    $this->configuration['sid'] = $form_state->getValue('sid');
  }

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $build = array();
    $session = \Drupal::service('session');
    if ($this->configuration['check']) {
      $build['pay_method'] = array(
        '#type' => 'select',
        '#title' => $this->t('Select your payment type:'),
        '#default_value' => $session->get('pay_method') == 'CK' ? 'CK' : 'CC',
        '#options' => array(
          'CC' => $this->t('Credit card'),
          'CK' => $this->t('Online check'),
        ),
      );
      $session->remove('pay_method');
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $session = \Drupal::service('session');
    if (NULL != $form_state->getValue(['panes', 'payment', 'details', 'pay_method'])) {
      $session->set('pay_method', $form_state->getValue(['panes', 'payment', 'details', 'pay_method']));
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReviewTitle() {
    if ($this->configuration['check']) {
      return $this->t('Credit card/eCheck');
    }
    else {
      return $this->t('Credit card');
    }
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

    $data = array(
      'sid' => $this->configuration['sid'],
      'mode' => '2CO',
      'card_holder_name' => Unicode::substr($address->first_name . ' ' . $address->last_name, 0, 128),
      'street_address' => Unicode::substr($address->street1, 0, 64),
      'street_address2' => Unicode::substr($address->street2, 0, 64),
      'city' => Unicode::substr($address->city, 0, 64),
      'state' => $address->zone,
      'zip' => Unicode::substr($address->postal_code, 0, 16),
      'country' => $country,
      'email' => Unicode::substr($order->getEmail(), 0, 64),
      'phone' => Unicode::substr($address->phone, 0, 16),
      'purchase_step' => 'payment-method',

      'demo' => $this->configuration['demo'] ? 'Y' : 'N',
      'lang' => $this->configuration['language'],
      'merchant_order_id' => $order->id(),
      'pay_method' => 'CC',
      'x_receipt_link_url' => Url::fromRoute('uc_2checkout.complete', ['cart_id' => \Drupal::service('uc_cart.manager')->get()->getId()], ['absolute' => TRUE])->toString(),

      'total' => uc_currency_format($order->getTotal(), FALSE, FALSE, '.'),
      'currency_code' => $order->getCurrency(),
      'cart_order_id' => $order->id(),
    );

    $i = 0;
    foreach ($order->products as $product) {
      $i++;
      $data['li_' . $i . '_type'] = 'product';
      $data['li_' . $i . '_name'] = $product->title->value; // @todo: HTML escape and limit to 128 chars
      $data['li_' . $i . '_quantity'] = $product->qty->value;
      $data['li_' . $i . '_product_id'] = $product->model->value;
      $data['li_' . $i . '_price'] = uc_currency_format($product->price->value, FALSE, FALSE, '.');
    }

    if ('direct' == $this->configuration['checkout_type']) {
      $form['#attached']['library'][] = 'uc_2checkout/2checkout.direct';
    }

    $host = $this->configuration['demo'] ? 'sandbox' : 'www';
    $form['#action'] = "https://$host.2checkout.com/checkout/purchase";

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
