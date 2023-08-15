<?php

namespace Drupal\uc_payment\Plugin\Ubercart\OrderPane;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\EditableOrderPanePluginBase;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\Entity\PaymentMethod;

/**
 * Specify and collect payment for an order.
 *
 * @UbercartOrderPane(
 *   id = "payment",
 *   title = @Translation("Payment"),
 *   weight = 4,
 * )
 */
class Payment extends EditableOrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return array('pos-left');
  }

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    if ($view_mode != 'customer') {
      $build['balance'] = array('#markup' => $this->t('Balance: @balance', ['@balance' => uc_currency_format(uc_payment_balance($order))]));

      $account = \Drupal::currentUser();
      if ($account->hasPermission('view payments')) {
        $build['view_payments'] = array(
          '#type' => 'link',
          '#prefix' => ' (',
          '#title' => $this->t('View'),
          '#url' => Url::fromRoute('uc_payments.order_payments', ['uc_order' => $order->id()]),
          '#suffix' => ')',
        );
      }

      $method = \Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($order);
      $build['method'] = array(
        '#markup' => $this->t('Method: @payment_method', ['@payment_method' => $method->cartReviewTitle()]),
        '#prefix' => '<br />',
      );

      $method_output = $method->orderView($order);
      if (!empty($method_output)) {
        $build['output'] = $method_output + array(
          '#prefix' => '<br />',
        );
      }
    }
    else {
      $method = \Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($order);
      $build['method'] = array('#markup' => $this->t('Method: @payment_method', ['@payment_method' => $method->cartReviewTitle()]));

      $method_output = $method->customerView($order);
      if (!empty($method_output)) {
        $build['output'] = $method_output + array(
          '#prefix' => '<br />',
        );
      }

    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $options = array();
    $methods = PaymentMethod::loadMultiple();
    uasort($methods, 'Drupal\uc_payment\Entity\PaymentMethod::sort');
    foreach ($methods as $method) {
      $options[$method->id()] = $method->label();
    }

    $form['payment_method'] = array(
      '#type' => 'select',
      '#title' => $this->t('Payment method'),
      '#default_value' => $order->getPaymentMethodId(),
      '#options' => $options,
      '#ajax' => array(
        'callback' => array($this, 'ajaxCallback'),
        'progress' => array('type' => 'throbber'),
        'wrapper' => 'payment-details',
      ),
    );

    // An empty <div> for Ajax.
    $form['payment_details'] = array(
      '#type' => 'container',
      '#attributes' => array('id' => 'payment-details'),
      '#tree' => TRUE,
    );

    $method = $form_state->getValue('payment_method') ?: $order->getPaymentMethodId();
    if ($method && $details = PaymentMethod::load($method)->getPlugin()->orderEditDetails($order)) {
      if (is_array($details)) {
        $form['payment_details'] += $details;
      }
      else {
        $form['payment_details']['#markup'] = $details;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(OrderInterface $order, array &$form, FormStateInterface $form_state) {
    $changes['payment_method'] = $form_state->getValue('payment_method');
    $changes['payment_details'] = $form_state->getValue('payment_details') ?: array();

    $order->setPaymentMethodId($changes['payment_method']);
    $method = \Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($order);
    $return = $method->orderEditProcess($order, $form, $form_state);
    if (is_array($return)) {
      $changes['payment_details'] = array_merge($changes['payment_details'], $return);
    }
    $order->payment_details = $changes['payment_details'];
  }

  /**
   * AJAX callback to render the payment method pane.
   */
  public function ajaxCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#payment-details', trim(drupal_render($form['payment']['payment_details']))));
    $status_messages = array('#type' => 'status_messages');
    $response->addCommand(new PrependCommand('#payment-details', drupal_render($status_messages)));

    return $response;
  }

}
