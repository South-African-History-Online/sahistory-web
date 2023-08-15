<?php

namespace Drupal\uc_tax\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the new tax rate form.
 */
class TaxRateAddForm extends TaxRateFormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create tax rate');
    return $actions;
  }

}
