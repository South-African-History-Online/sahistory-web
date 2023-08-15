<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the class/product attributes overview form.
 */
abstract class ObjectAttributesFormBase extends FormBase {

  /**
   * The attribute table that this form will write to.
   */
  protected $attributeTable;

  /**
   * The option table that this form will write to.
   */
  protected $optionTable;

  /**
   * The identifier field that this form will use.
   */
  protected $idField;

  /**
   * The identifier value that this form will use.
   */
  protected $idValue;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_object_attributes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $attributes = NULL) {
    $form['attributes'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Remove'),
        $this->t('Name'),
        $this->t('Label'),
        $this->t('Default'),
        $this->t('Required'),
        $this->t('List position'),
        $this->t('Display'),
      ),
      '#empty' => $this->t('No attributes available.'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'uc-attribute-table-ordering',
        ),
      ),
    );

    foreach ($attributes as $aid => $attribute) {
      $option = isset($attribute->options[$attribute->default_option]) ? $attribute->options[$attribute->default_option] : NULL;
      $form['attributes'][$aid]['#attributes']['class'][] = 'draggable';
      $form['attributes'][$aid]['remove'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Remove'),
        '#title_display' => 'invisible',
      );
      $form['attributes'][$aid]['name'] = array(
        '#markup' => $attribute->name,
      );
      $form['attributes'][$aid]['label'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#title_display' => 'invisible',
        '#default_value' => empty($attribute->label) ? $attribute->name : $attribute->label,
        '#size' => 20,
        '#maxlength' => 255,
      );
      $form['attributes'][$aid]['option'] = array(
        '#markup' => $option ? ($option->name . ' (' . uc_currency_format($option->price) . ')' ) : $this->t('n/a'),
      );
      $form['attributes'][$aid]['required'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Required'),
        '#title_display' => 'invisible',
        '#default_value' => $attribute->required,
      );
      $form['attributes'][$aid]['ordering'] = array(
        '#type' => 'weight',
        '#title' => $this->t('List position'),
        '#title_display' => 'invisible',
        '#default_value' => $attribute->ordering,
        '#attributes' => array('class' => array('uc-attribute-table-ordering')),
      );
      $form['attributes'][$aid]['display'] = array(
        '#type' => 'select',
        '#title' => $this->t('Display'),
        '#title_display' => 'invisible',
        '#default_value' => $attribute->display,
        '#options' => _uc_attribute_display_types(),
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $changed = FALSE;

    foreach ($form_state->getValue('attributes') as $aid => $attribute) {
      if ($attribute['remove']) {
        $remove_aids[] = $aid;
      }
      else {
        unset($attribute['remove']);
        db_merge($this->attributeTable)
          ->key('aid', $aid)
          ->fields($attribute)
          ->execute();
        $changed = TRUE;
      }
    }

    if (isset($remove_aids)) {
      $select = db_select('uc_attribute_options', 'ao')
        ->fields('ao', array('oid'))
        ->condition('ao.aid', $remove_aids, 'IN');
      db_delete($this->optionTable)
        ->condition('oid', $select, 'IN')
        ->condition($this->idField, $this->idValue)
        ->execute();

      db_delete($this->attributeTable)
        ->condition($this->idField, $this->idValue)
        ->condition('aid', $remove_aids, 'IN')
        ->execute();

      $this->attributesRemoved();

      drupal_set_message($this->formatPlural(count($remove_aids), '1 attribute has been removed.', '@count attributes have been removed.'));
    }

    if ($changed) {
      drupal_set_message($this->t('The changes have been saved.'));
    }
  }

  /**
   * Called when submission of this form caused attributes to be removed.
   */
  protected function attributesRemoved() {
  }

}
