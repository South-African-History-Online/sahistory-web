<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the attribute add form.
 */
class AttributeAddForm extends AttributeFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Remove Form API elements from $form_state
    $form_state->cleanValues();
    $aid = db_insert('uc_attributes')->fields($form_state->getValues())->execute();

    if ($form_state->getValue('display') == 0) {
      // No options needed/allowed for Textfield display type.
      $form_state->setRedirect('uc_attribute.overview', ['aid' => $aid]);
    }
    else {
      // All other display types we redirect to add options.
      $form_state->setRedirect('uc_attribute.options', ['aid' => $aid]);
    }
  }

}
