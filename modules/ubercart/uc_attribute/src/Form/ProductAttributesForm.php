<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Defines the product attribute overview form.
 */
class ProductAttributesForm extends ObjectAttributesFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $this->attributeTable = 'uc_product_attributes';
    $this->optionTable = 'uc_product_options';
    $this->idField = 'nid';
    $this->idValue = $node->id();

    $attributes = uc_product_get_attributes($node->id());
    return parent::buildForm($form, $form_state, $attributes);
  }

  /**
   * {@inheritdoc}
   */
  protected function attributesRemoved() {
    db_delete('uc_product_adjustments')
      ->condition('nid', $this->idValue)
      ->execute();
  }

}
