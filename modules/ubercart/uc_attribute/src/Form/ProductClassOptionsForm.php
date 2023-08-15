<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Defines the product class options overview form.
 */
class ProductClassOptionsForm extends ObjectOptionsFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeTypeInterface $node_type = NULL) {
    $this->attributeTable = 'uc_class_attributes';
    $this->optionTable = 'uc_class_attribute_options';
    $this->idField = 'pcid';
    $this->idValue = $node_type->id();

    $attributes = uc_class_get_attributes($node_type->id());
    return parent::buildForm($form, $form_state, $attributes);
  }

}
