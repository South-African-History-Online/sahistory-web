<?php

namespace Drupal\uc_order\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\uc_order\Entity\OrderStatus;

/**
 * Sets the status of an order.
 *
 * @Action(
 *   id = "uc_order_set_order_status_action",
 *   label = @Translation("Set order status"),
 *   type = "uc_order"
 * )
 */
class SetOrderStatusAction extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\uc_order\OrderInterface $object */
    return $object->access('update', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'status' => '',
      'notify' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['status'] = array(
      '#type' => 'select',
      '#title' => $this->t('Order status'),
      '#default_value' => $this->configuration['status'],
      '#options' => OrderStatus::getOptionsList(),
    );
    $form['notify'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Send e-mail notification on update.'),
      '#default_value' => $this->configuration['notify'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['status'] = $form_state->getValue('status');
    $this->configuration['notify'] = $form_state->getValue('notify');
  }

  /**
   * {@inheritdoc}
   */
  public function execute($order = NULL) {
    $order->setStatusId($this->configuration['status'])->save();
    if ($this->configuration['notify']) {
      // rules_invoke_event('uc_order_status_email_update', $order);
    }
  }

}
