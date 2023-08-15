<?php

namespace Drupal\uc_tax_report\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\Entity\OrderStatus;

/**
 * Form to customize parameters on the tax report.
 */
class ParametersForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_tax_report_params_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $args = NULL) {
    if (!isset($args['start_date'])) {
      $args['start_date'] = REQUEST_TIME;
    }
    if (!isset($args['end_date'])) {
      $args['end_date'] = REQUEST_TIME;
    }
    if (!isset($args['statuses'])) {
      $args['statuses'] = uc_report_order_statuses();
    }

    $form['params'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Customize tax report parameters'),
      '#description' => $this->t('Adjust these values and update the report to build your sales tax report. Once submitted, the report may be bookmarked for easy reference in the future.'),
    );

    $form['params']['start_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('Start date'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#default_value' => DrupalDateTime::createFromTimestamp($args['start_date']),
    );

    $form['params']['end_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('End date'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#default_value' => DrupalDateTime::createFromTimestamp($args['end_date']),
    );

    $form['params']['statuses'] = array(
      '#type' => 'select',
      '#title' => $this->t('Order statuses'),
      '#description' => $this->t('Only orders with selected statuses will be included in the report.') . '<br />' . $this->t('Hold Ctrl + click to select multiple statuses.'),
      '#options' => OrderStatus::getOptionsList(),
      '#default_value' => $args['statuses'],
      '#multiple' => TRUE,
      '#size' => 5,
    );

    $form['params']['actions'] = array('#type' => 'actions');
    $form['params']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Update report'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('statuses')) {
      $form_state->setErrorByName('statuses', $this->t('You must select at least one order status.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the start and end dates from the form.
    $start_date = $form_state->getValue('start_date')->setTime(0, 0, 0)->getTimestamp();
    $end_date = $form_state->getValue('end_date')->setTime(23, 59, 59)->getTimestamp();

    $args = array(
      'start_date' => $start_date,
      'end_date' => $end_date,
      'statuses' => implode(',', array_keys($form_state->getValue('statuses'))),
    );

    $form_state->setRedirect('uc_tax_report.reports', $args);
  }
}
