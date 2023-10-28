<?php

namespace Drupal\uc_payment_pack\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Form for recording a received check and expected clearance date.
 */
class ReceiveCheckForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_payment_pack_receive_check_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL) {
    $balance = uc_payment_balance($uc_order);
    $form['balance'] = array(
      '#prefix' => '<strong>' . $this->t('Order balance:') . '</strong> ',
      '#markup' => uc_currency_format($balance),
    );
    $form['order_id'] = array(
      '#type' => 'hidden',
      '#value' => $uc_order->id(),
    );
    $form['amount'] = array(
      '#type' => 'uc_price',
      '#title' => $this->t('Check amount'),
      '#default_value' => $balance,
    );
    $form['comment'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Comment'),
      '#description' => $this->t('Any notes about the check, like type or check number.'),
      '#size' => 64,
      '#maxlength' => 256,
    );
    $form['clear_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('Expected clear date'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#default_value' => DrupalDateTime::createFromTimestamp(REQUEST_TIME),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Receive check'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    uc_payment_enter($form_state->getValue('order_id'), 'check', $form_state->getValue('amount'), $this->currentUser()->id(), '', $form_state->getValue('comment'));

    $clear_date = $form_state->getValue('clear_date')->getTimestamp();
    db_insert('uc_payment_check')
      ->fields(array(
        'order_id' => $form_state->getValue('order_id'),
        'clear_date' => $clear_date,
      ))
      ->execute();
    drupal_set_message($this->t('Check received, expected clear date of @date.', ['@date' => \Drupal::service('date.formatter')->format($clear_date, 'uc_store')]));


    $form_state->setRedirect('entity.uc_order.canonical', ['uc_order' => $form_state->getValue('order_id')]);
  }
}
