<?php

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\Entity\OrderStatus;

/**
 * Presents the form to create a custom order status.
 */
class OrderStatusAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_status_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Order status ID'),
      '#description' => $this->t('Must be a unique ID with no spaces.'),
      '#size' => 32,
      '#maxlength' => 32,
      '#required' => TRUE,
    );

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The order status title displayed to users.'),
      '#size' => 32,
      '#maxlength' => 48,
      '#required' => TRUE,
    );

    $form['state'] = array(
      '#type' => 'select',
      '#title' => $this->t('Order state'),
      '#description' => $this->t('Set which order state this status is for.'),
      '#options' => uc_order_state_options_list(),
      '#default_value' => 'post_checkout',
    );

    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => $this->t('List position'),
      '#delta' => 20,
      '#default_value' => 0,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['create'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Create'),
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('uc_order.workflow'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $id = strtolower(trim($form_state->getValue('id')));
    if (strpos($id, ' ') !== FALSE || $id == 'all') {
      $form_state->setErrorByName('id', $this->t('You have entered an invalid status ID.'));
    }

    if (OrderStatus::load($id)) {
      $form_state->setErrorByName('id', $this->t('This ID is already in use.  Please specify a unique ID.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    OrderStatus::create(array(
      'id' => strtolower(trim($form_state->getValue('id'))),
      'name' => $form_state->getValue('name'),
      'state' => $form_state->getValue('state'),
      'weight' => (int) $form_state->getValue('weight'),
    ))->save();

    drupal_set_message($this->t('Custom order status created.'));

    $form_state->setRedirect('uc_order.workflow');
  }

}
