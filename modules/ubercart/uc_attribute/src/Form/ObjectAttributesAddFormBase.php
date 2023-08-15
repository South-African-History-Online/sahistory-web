<?php

namespace Drupal\uc_attribute\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the class/product attributes add form.
 */
abstract class ObjectAttributesAddFormBase extends FormBase {

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
    return 'uc_object_attributes_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $attributes = NULL) {
    $used_aids = array();
    foreach ($attributes as $attribute) {
      $used_aids[] = $attribute->aid;
    }

    $unused_attributes = [];
    $result = db_query("SELECT a.aid, a.name, a.label FROM {uc_attributes} a LEFT JOIN {uc_attribute_options} ao ON a.aid = ao.aid GROUP BY a.aid, a.name, a.label ORDER BY a.name");
    foreach ($result as $attribute) {
      if (!in_array($attribute->aid, $used_aids)) {
        $unused_attributes[$attribute->aid] = $attribute->name;
      }
    }

    $form['add_attributes'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Attributes'),
      '#options' => $unused_attributes ?: array($this->t('No attributes left to add.')),
      '#disabled' => empty($unused_attributes),
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['add'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add attributes'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    foreach (array_filter($form_state->getValue('add_attributes')) as $aid) {
      // Enable all options for added attributes.
      $attribute = uc_attribute_load($aid);
      $oid = 0;
      if (isset($attribute->options)) {
        foreach ($attribute->options as $option) {
          $option->{$this->idField} = $this->idValue;
          unset($option->name);
          unset($option->aid);
          db_insert($this->optionTable)
            ->fields((array)$option)
            ->execute();
        }
        // Make the first option (if any) the default.
        if ($option = reset($attribute->options)) {
          $oid = $option->oid;
        }
      }

      $select = db_select('uc_attributes', 'a')
        ->condition('aid', $aid);
      $select->addExpression(':id', $this->idField, array(':id' => $this->idValue));
      $select->addField('a', 'aid');
      $select->addField('a', 'label');
      $select->addField('a', 'ordering');
      $select->addExpression(':oid', 'default_option', array(':oid' => $oid));
      $select->addField('a', 'required');
      $select->addField('a', 'display');

      db_insert($this->attributeTable)
        ->from($select)
        ->execute();
    }

    $num = count(array_filter($form_state->getValue('add_attributes')));
    if ($num > 0) {
      $this->attributesAdded();

      drupal_set_message($this->formatPlural($num, '1 attribute has been added.', '@count attributes have been added.'));
    }
  }

  /**
   * Called when submission of this form caused attributes to be added.
   */
  protected function attributesAdded() {
  }

}
