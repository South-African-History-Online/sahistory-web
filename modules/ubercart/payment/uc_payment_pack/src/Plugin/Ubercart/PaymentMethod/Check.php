<?php

namespace Drupal\uc_payment_pack\Plugin\Ubercart\PaymentMethod;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_store\Address;

/**
 * Defines the check payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "check",
 *   name = @Translation("Check", context = "cheque"),
 * )
 */
class Check extends PaymentMethodPluginBase {

  public function defaultConfiguration() {
    $config = \Drupal::config('uc_store.settings');
    return [
      'policy' => $this->t('Personal and business checks will be held for up to 10 business days to ensure payment clears before an order is shipped.'),
      'name' => '',
      'address' => $config->get('address') + ['company' => $config->get('name')],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['policy'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Check payment policy', [], ['context' => 'cheque']),
      '#description' => $this->t('Instructions for customers on the checkout page.'),
      '#default_value' => $this->configuration['policy'],
      '#rows' => 3,
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Contact'),
      '#description' => $this->t('Direct checks to a person or department.'),
      '#default_value' => $this->configuration['name'],
    );
    $form['address'] = array(
      '#type' => 'uc_address',
      '#tree' => TRUE,
      '#default_value' => $this->configuration['address'],
      '#required' => FALSE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['policy'] = $form_state->getValue('policy');
    $this->configuration['name'] = $form_state->getValue('name');
    $this->configuration['address'] = $form_state->getValue('address');
  }

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $build['instructions'] = array(
      '#markup' => $this->t('Checks should be made out to:')
    );

    $address = Address::create($this->configuration['address']);
    $address->setFirstName($this->configuration['name']);
    $build['address'] = array(
      '#prefix' => '<p>',
      '#markup' => (string) $address,
      '#suffix' => '</p>',
    );

    $build['policy'] = array(
      '#prefix' => '<p>',
      '#markup' => Html::escape($this->configuration['policy']),
      '#suffix' => '</p>',
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReview(OrderInterface $order) {
    $address = Address::create($this->configuration['address']);
    $address->setFirstName($this->configuration['name']);
    $review[] = array(
      'title' => $this->t('Mail to'),
      'data' => array('#markup' => (string) $address),
    );

    return $review;
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $build = array('#suffix' => '<br />');

    $result = db_query('SELECT clear_date FROM {uc_payment_check} WHERE order_id = :id ', [':id' => $order->id()]);
    if ($clear_date = $result->fetchField()) {
      $build['#markup'] = $this->t('Clear Date:') . ' ' . \Drupal::service('date.formatter')->format($clear_date, 'uc_store');
    }
    else {
      $build['#type'] = 'link';
      $build['#title'] = $this->t('Receive Check');
      $build['#url'] = Url::fromRoute('uc_payment_pack.check.receive', ['uc_order' => $order->id()]);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function customerView(OrderInterface $order) {
    $build = array();

    $result = db_query('SELECT clear_date FROM {uc_payment_check} WHERE order_id = :id ', [':id' => $order->id()]);
    if ($clear_date = $result->fetchField()) {
      $build['#markup'] = $this->t('Check received') . '<br />' .
        $this->t('Expected clear date:') . '<br />' . \Drupal::service('date.formatter')->format($clear_date, 'uc_store');
    }

    return $build;
  }

}
