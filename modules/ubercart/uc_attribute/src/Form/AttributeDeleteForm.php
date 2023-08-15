<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines the attribute delete form.
 */
class AttributeDeleteForm extends ConfirmFormBase {

  /**
   * The attribute to be deleted.
   */
  protected $attribute;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the attribute %name?', ['%name' => $this->attribute->name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $count = db_query("SELECT COUNT(*) FROM {uc_product_attributes} WHERE aid = :aid", [':aid' => $this->attribute->aid])->fetchField();
    return $this->formatPlural($count, 'There is 1 product with this attribute.', 'There are @count products with this attribute.');
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
    return Url::fromRoute('uc_attribute.overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_attribute_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $aid = NULL) {
    $this->attribute = uc_attribute_load($aid);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $options = array_keys($this->attribute->options);

    if ($options) {
      db_delete('uc_class_attribute_options')
        ->condition('oid', $options, 'IN')
        ->execute();

      db_delete('uc_product_options')
        ->condition('oid', $options, 'IN')
        ->execute();
    }

    if ($nodes = db_query("SELECT nid FROM {uc_product_attributes} WHERE aid = :aid", [':aid' => $this->attribute->aid])->fetchCol()) {
      db_delete('uc_product_adjustments')
        ->condition('nid', $nodes, 'IN')
        ->execute();
    }

    db_delete('uc_class_attributes')
      ->condition('aid', $this->attribute->aid)
      ->execute();

    db_delete('uc_product_attributes')
      ->condition('aid', $this->attribute->aid)
      ->execute();

    db_delete('uc_attribute_options')
      ->condition('aid', $this->attribute->aid)
      ->execute();

    db_delete('uc_attributes')
      ->condition('aid', $this->attribute->aid)
      ->execute();

    drupal_set_message($this->t('Product attribute deleted.'));

    $form_state->setRedirect('uc_attribute.overview');
  }

}
