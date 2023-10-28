<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines the attribute option delete form.
 */
class OptionDeleteForm extends ConfirmFormBase {

  /**
   * The option to be deleted.
   */
  protected $option;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the option %name?', ['%name' => $this->option->name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('uc_attribute.options', ['aid' => $this->option->aid]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_attribute_option_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $aid = NULL, $oid = NULL) {
    $this->option = uc_attribute_option_load($oid);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $match = 'i:' . $this->option->aid . ';s:' . strlen($this->option->oid) . ':"' . $this->option->oid . '";';

    db_delete('uc_product_adjustments')
      ->condition('combination', '%' . db_like($match) . '%', 'LIKE')
      ->execute();

    $select = db_select('uc_attribute_options', 'ao')
      ->where('{uc_class_attribute_options}.oid = ao.oid')
      ->condition('ao.oid', $this->option->oid);
    $select->addExpression('1');
    db_delete('uc_class_attribute_options')
      ->condition('', $select, 'EXISTS')
      ->execute();

    $select = db_select('uc_attribute_options', 'ao')
      ->where('{uc_product_options}.oid = ao.oid')
      ->condition('ao.oid', $this->option->oid);
    $select->addExpression('1');
    db_delete('uc_product_options')
      ->condition('', $select, 'EXISTS')
      ->execute();

    db_delete('uc_attribute_options')
      ->condition('oid', $this->option->oid)
      ->execute();

    $form_state->setRedirect('uc_attribute.options', ['aid' => $this->option->aid]);
  }

}
