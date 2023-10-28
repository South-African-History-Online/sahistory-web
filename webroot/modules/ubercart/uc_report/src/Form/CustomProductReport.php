<?php

namespace Drupal\uc_report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\Entity\OrderStatus;

class CustomProductReport extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $values) {
    $form['search'] = array(
      '#type' => 'details',
      '#title' => $this->t('Customize product report parameters'),
      '#description' => $this->t('Adjust these values and update the report to build your custom product report. Once submitted, the report may be bookmarked for easy reference in the future.'),
    );

    $form['search']['start_date'] = array(
      '#type' => 'date',
      '#title' => $this->t('Start date'),
      '#default_value' => array(
        'month' => \Drupal::service('date.formatter')->format($values['start_date'], 'custom', 'n'),
        'day' => \Drupal::service('date.formatter')->format($values['start_date'], 'custom', 'j'),
        'year' => \Drupal::service('date.formatter')->format($values['start_date'], 'custom', 'Y'),
      ),
    );
    $form['search']['end_date'] = array(
      '#type' => 'date',
      '#title' => $this->t('End date'),
      '#default_value' => array(
        'month' => \Drupal::service('date.formatter')->format($values['end_date'], 'custom', 'n'),
        'day' => \Drupal::service('date.formatter')->format($values['end_date'], 'custom', 'j'),
        'year' => \Drupal::service('date.formatter')->format($values['end_date'], 'custom', 'Y'),
      ),
    );

    $form['search']['status'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Order statuses'),
      '#description' => $this->t('Only orders with selected statuses will be included in the report.'),
      '#options' => OrderStatus::getOptionsList(),
      '#default_value' => $values['status'],
    );

    $form['search']['actions'] = array('#type' => 'actions');
    $form['search']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Update report'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('status')) {
      $form_state->setErrorByName('status', $this->t('You must select at least one order status.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $start_date = mktime(0, 0, 0, $form_state->getValue(['start_date', 'month']), $form_state->getValue(['start_date', 'day']), $form_state->getValue(['start_date', 'year']));
    $end_date = mktime(23, 59, 59, $form_state->getValue(['end_date', 'month']), $form_state->getValue(['end_date', 'day']), $form_state->getValue(['end_date', 'year']));

    $args = array(
      'start_date' => $start_date,
      'end_date' => $end_date,
      'status' => implode(',', array_keys(array_filter($form_state->getValue('status')))),
    );

    $form_state->setRedirect('uc_report.custom.products', $args);
  }
}
