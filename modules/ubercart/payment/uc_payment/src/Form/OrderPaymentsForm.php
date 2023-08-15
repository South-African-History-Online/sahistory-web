<?php

namespace Drupal\uc_payment\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\Entity\PaymentMethod;
use Drupal\uc_payment\Plugin\PaymentMethodManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays a list of payments attached to an order.
 */
class OrderPaymentsForm extends FormBase {

  /**
   * The payment method manager.
   *
   * @var \Drupal\uc_payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * Constructs an OrderPaymentsForm object.
   *
   * @param \Drupal\uc_payment\Plugin\PaymentMethodManager $payment_method_manager
   *   The payment method plugin manager.
   */
  public function __construct(PaymentMethodManager $payment_method_manager) {
    $this->paymentMethodManager = $payment_method_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_payment.method')
    );
  }

  /**
   * The order that is being viewed.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_payment_by_order_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL) {
    $this->order = $uc_order;

    $form['#attached']['library'][] = 'uc_payment/uc_payment.styles';

    $total = $this->order->getTotal();
    $payments = uc_payment_load_payments($this->order->id());

    $form['order_total'] = array(
      '#type' => 'item',
      '#title' => $this->t('Order total'),
      '#theme' => 'uc_price',
      '#price' => $total,
    );
    $form['payments'] = array(
      '#type' => 'table',
      '#header' => array($this->t('Received'), $this->t('User'), $this->t('Method'), $this->t('Amount'), $this->t('Balance'), $this->t('Comment'), $this->t('Action')),
      '#weight' => 10,
    );

    $account = $this->currentUser();
    foreach ($payments as $id => $payment) {
      $form['payments'][$id]['received'] = array(
        '#markup' => \Drupal::service('date.formatter')->format($payment->getReceived(), 'short'),
      );
      $form['payments'][$id]['user'] = array(
        '#theme' => 'username',
        '#account' => $payment->getUser(),
      );
      $form['payments'][$id]['method'] = array(
        '#markup' => $payment->getMethod()->label(),
      );
      $form['payments'][$id]['amount'] = array(
        '#theme' => 'uc_price',
        '#price' => $payment->getAmount(),
      );
      $total -= $payment->getAmount();
      $form['payments'][$id]['balance'] = array(
        '#theme' => 'uc_price',
        '#price' => $total,
      );
      $form['payments'][$id]['comment'] = array(
        '#markup' => $payment->getComment() ?: '-',
      );
      $form['payments'][$id]['action'] = array(
        '#type' => 'operations',
        '#links' => array(
          'delete' => array(
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('uc_payments.delete', ['uc_order' => $this->order->id(), 'uc_payment_receipt' => $id]),
          ),
        ),
        '#access' => $account->hasPermission('delete payments'),
      );
    }

    $form['balance'] = array(
      '#type' => 'item',
      '#title' => $this->t('Current balance'),
      '#theme' => 'uc_price',
      '#price' => $total,
    );

    if ($account->hasPermission('manual payments')) {
      $form['new'] = array(
        '#type' => 'details',
        '#title' => $this->t('Add payment'),
        '#open' => TRUE,
        '#weight' => 20,
      );
      $form['new']['amount'] = array(
        '#type' => 'uc_price',
        '#title' => $this->t('Amount'),
        '#required' => TRUE,
        '#size' => 6,
      );
      $options = array();
      foreach (PaymentMethod::loadMultiple() as $method) {
        $options[$method->id()] = $method->label();
      }
      $form['new']['method'] = array(
        '#type' => 'select',
        '#title' => $this->t('Payment method'),
        '#options' => $options,
        '#default_value' => $this->order->getPaymentMethodId(),
      );
      $form['new']['comment'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Comment'),
      );
      $form['new']['received'] = array(
        '#type' => 'datetime',
        '#title' => $this->t('Date'),
        '#date_date_element' => 'date',
        '#date_time_element' => 'time',
        '#default_value' => DrupalDateTime::createFromTimestamp(REQUEST_TIME),
      );
      $form['new']['action'] = array('#type' => 'actions');
      $form['new']['action']['action'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Record payment'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $payment = $form_state->getValues();
    uc_payment_enter($this->order->id(), $payment['method'], $payment['amount'], \Drupal::currentUser()->id(), '', $payment['comment'], $payment['received']->getTimestamp());
    drupal_set_message($this->t('Payment entered.'));
  }

}
