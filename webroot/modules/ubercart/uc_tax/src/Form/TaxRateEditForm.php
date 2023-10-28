<?php

namespace Drupal\uc_tax\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the tax rate edit form.
 */
class TaxRateEditForm extends TaxRateFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $rate = $this->entity;
    // Set title to show what rate we're editing.
    $form['#title'] = $this->t('Edit %rate', ['%rate' => $rate->label()]);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Update tax rate');
    return $actions;
  }

}
