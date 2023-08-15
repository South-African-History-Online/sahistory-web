<?php

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\Entity\OrderStatus;

/**
 * Displays the order workflow form for order state and status customization.
 */
class OrderWorkflowForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_workflow_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_order.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $states = uc_order_state_options_list();
    $statuses = OrderStatus::loadMultiple();

    $form['order_states'] = array(
      '#type' => 'details',
      '#title' => $this->t('Order states'),
    );
    $form['order_states']['order_states'] = array(
      '#type' => 'table',
      '#header' => array($this->t('State'), $this->t('Default order status')),
    );

    foreach ($states as $state_id => $title) {
      $form['order_states']['order_states'][$state_id]['title'] = array(
        '#markup' => $title,
      );

      // Create the select box for specifying a default status per order state.
      $options = array();
      foreach ($statuses as $status) {
        if ($state_id == $status->getState()) {
          $options[$status->id()] = $status->getName();
        }
      }
      if (empty($options)) {
        $form['order_states']['order_states'][$state_id]['default'] = array(
          '#markup' => $this->t('- N/A -'),
        );
      }
      else {
        $form['order_states']['order_states'][$state_id]['default'] = array(
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => uc_order_state_default($state_id),
        );
      }
    }

    $form['order_statuses'] = array(
      '#type' => 'details',
      '#title' => $this->t('Order statuses'),
      '#open' => TRUE,
    );
    $form['order_statuses']['order_statuses'] = array(
      '#type' => 'table',
      '#header' => array($this->t('ID'), $this->t('Title'), $this->t('List position'), $this->t('State'), $this->t('Remove')),
    );

    foreach ($statuses as $status) {
      $form['order_statuses']['order_statuses'][$status->id()]['id'] = array(
        '#markup' => $status->id(),
      );
      $form['order_statuses']['order_statuses'][$status->id()]['name'] = array(
        '#type' => 'textfield',
        '#default_value' => $status->getName(),
        '#size' => 32,
        '#required' => TRUE,
      );
      $form['order_statuses']['order_statuses'][$status->id()]['weight'] = array(
        '#type' => 'weight',
        '#delta' => 20,
        '#default_value' => $status->getWeight(),
      );
      if ($status->isLocked()) {
        $form['order_statuses']['order_statuses'][$status->id()]['state'] = array(
          '#markup' => $states[$status->getState()],
        );
      }
      else {
        $form['order_statuses']['order_statuses'][$status->id()]['state'] = array(
          '#type' => 'select',
          '#options' => $states,
          '#default_value' => $status->getState(),
        );
        $form['order_statuses']['order_statuses'][$status->id()]['remove'] = array(
          '#type' => 'checkbox',
        );
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('uc_order.settings');
    foreach ($form_state->getValue('order_states') as $key => $value) {
      $config->set("default_state.$key", $value['default']);
    }
    $config->save();

    foreach ($form_state->getValue('order_statuses') as $id => $value) {
      $status = OrderStatus::load($id);
      if (!empty($value['remove'])) {
        $status->delete();
        drupal_set_message($this->t('Order status %status removed.', ['%status' => $status->getName()]));
      }
      else {
        $status->setName($value['name']);
        $status->setWeight((int) $value['weight']);

        // The state cannot be changed if the status is locked.
        if (!$status->isLocked()) {
          $status->setState($value['state']);
        }

        $status->save();
      }
    }

    parent::submitForm($form, $form_state);
  }

}
