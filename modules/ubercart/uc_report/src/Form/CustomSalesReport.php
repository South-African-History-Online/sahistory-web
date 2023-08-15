<?php

namespace Drupal\uc_report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\Entity\OrderStatus;

class CustomSalesReport extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $values, $statuses) {
    $form['search'] = array(
      '#type' => 'details',
      '#title' => $this->t('Customize sales report parameters'),
      '#description' => $this->t('Adjust these values and update the report to build your custom sales summary. Once submitted, the report may be bookmarked for easy reference in the future.'),
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

    $form['search']['length'] = array(
      '#type' => 'select',
      '#title' => $this->t('Results breakdown'),
      '#description' => $this->t('Large daily reports may take a long time to display.'),
      '#options' => array(
        'day' => $this->t('daily'),
        'week' => $this->t('weekly'),
        'month' => $this->t('monthly'),
        'year' => $this->t('yearly'),
      ),
      '#default_value' => $values['length'],
    );

    if ($statuses === FALSE) {
      $statuses = uc_report_order_statuses();
    }

    $form['search']['status'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Order statuses'),
      '#description' => $this->t('Only orders with selected statuses will be included in the report.'),
      '#options' => OrderStatus::getOptionsList(),
      '#default_value' => $statuses,
    );

    $form['search']['detail'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show a detailed list of products ordered.'),
      '#default_value' => $values['detail'],
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
    // Build the start and end dates from the form.
    $start_date = mktime(0, 0, 0, $form_state->getValue(['start_date', 'month']), $form_state->getValue(['start_date', 'day']), $form_state->getValue(['start_date', 'year']));
    $end_date = mktime(23, 59, 59, $form_state->getValue(['end_date', 'month']), $form_state->getValue(['end_date', 'day']), $form_state->getValue(['end_date', 'year']));

    $args = array(
      'start_date' => $start_date,
      'end_date' => $end_date,
      'length' => $form_state->getValue('length'),
      'status' => implode(',', array_keys(array_filter($form_state->getValue('status')))),
      'detail' => $form_state->getValue('detail'),
    );

    $form_state->setRedirect('uc_report.custom.sales', $args);
  }
}
