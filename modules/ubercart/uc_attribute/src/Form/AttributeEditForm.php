<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the attribute edit form.
 */
class AttributeEditForm extends AttributeFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $aid = NULL) {
    $attribute = uc_attribute_load($aid);

    $form = parent::buildForm($form, $form_state);

    $form['#title'] = $this->t('Edit attribute: %name', ['%name' => $attribute->name]);

    $form['aid'] = array('#type' => 'value', '#value' => $attribute->aid);
    $form['name']['#default_value'] = $attribute->name;
    $form['label']['#default_value'] = $attribute->label ?: $attribute->name;
    $form['description']['#default_value'] = $attribute->description;
    $form['required']['#default_value'] = $attribute->required;
    $form['display']['#default_value'] = $attribute->display;
    $form['ordering']['#default_value'] = $attribute->ordering;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove Form API elements from $form_state
    $form_state->cleanValues();
    db_merge('uc_attributes')
      ->key(array('aid' => $form_state->getValue('aid')))
      ->fields($form_state->getValues())
      ->execute();
    $form_state->setRedirect('uc_attribute.overview');
  }

}
