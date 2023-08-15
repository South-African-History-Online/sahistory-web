<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Defines the product options overview form.
 */
class ProductOptionsForm extends ObjectOptionsFormBase {

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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Clear the page and block caches.
    Cache::invalidateTags(['content']);
  }

  /**
   * {@inheritdoc}
   */
  protected function optionRemoved($aid, $oid) {
    $match = 'i:' . $aid . ';s:' . strlen($oid) . ':"' . $oid . '";';
    db_delete('uc_product_adjustments')
      ->condition('nid', $this->idValue)
      ->condition('combination', '%' . db_like($match) . '%', 'LIKE')
      ->execute();
  }

}
