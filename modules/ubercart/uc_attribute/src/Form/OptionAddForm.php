<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the attribute option add form.
 */
class OptionAddForm extends OptionFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $aid = NULL) {
    $attribute = uc_attribute_load($aid);

    $form = parent::buildForm($form, $form_state, $aid);

    $form['#title'] = $this->t('Options for %name', ['%name' => $attribute->name]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove Form API elements from $form_state
    $form_state->cleanValues();
    $oid = db_insert('uc_attribute_options')->fields($form_state->getValues())->execute();
    drupal_set_message($this->t('Created new option %option.', ['%option' => $form_state->getValue('name')]));
    $this->logger('uc_attribute')->notice('Created new option %option.', ['%option' => $form_state->getValue('name'), 'link' => 'admin/store/products/attributes/' . $form_state->getValue('aid') . '/options/add']);
    $form_state->setRedirect('uc_attribute.option_add', ['aid' => $form_state->getValue('aid')]);
  }

}
