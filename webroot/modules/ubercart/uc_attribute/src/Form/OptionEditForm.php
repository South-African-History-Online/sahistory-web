<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the attribute option edit form.
 */
class OptionEditForm extends OptionFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $aid = NULL, $oid = NULL) {
    $option = uc_attribute_option_load($oid);

    $form = parent::buildForm($form, $form_state, $aid);

    $form['#title'] = $this->t('Edit option: %name', ['%name' => $option->name]);

    $form['oid'] = array('#type' => 'value', '#value' => $option->oid);
    $form['name']['#default_value'] = $option->name;
    $form['ordering']['#default_value'] = $option->ordering;
    $form['cost']['#default_value'] = $option->cost;
    $form['price']['#default_value'] = $option->price;
    $form['weight']['#default_value'] = $option->weight;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove Form API elements from $form_state
    $form_state->cleanValues();
    db_merge('uc_attribute_options')
      ->key(array('aid' => $form_state->getValue('aid'), 'oid' => $form_state->getValue('oid')))
      ->fields($form_state->getValues())
      ->execute();
    drupal_set_message($this->t('Updated option %option.', ['%option' => $form_state->getValue('name')]));
    $this->logger('uc_attribute')->notice('Updated option %option.', ['%option' => $form_state->getValue('name'), 'link' => 'admin/store/products/attributes/' . $form_state->getValue('aid') . '/options/' . $form_state->getValue('oid')]);
    $form_state->setRedirect('uc_attribute.options', ['aid' => $form_state->getValue('aid')]);
  }

}
