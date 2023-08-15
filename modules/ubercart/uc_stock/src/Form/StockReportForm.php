<?php

namespace Drupal\uc_stock\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Displays a stock report for products with stock tracking enabled.
 */
class StockReportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_stock_report_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $form['threshold'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Only show SKUs that are below their threshold.'),
      '#default_value' => /* @todo remove arg(): arg(4) == 'threshold' ? TRUE :*/ FALSE,
      '#attributes' => array('onchange' => 'this.form.submit();'),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#attributes' => array('style' => "display:none;"),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('threshold')) {
      $form_state->setRedirect('uc_stock.threshold');
    }
    else {
      $form_state->setRedirect('uc_stock.settings');
    }
  }
}
