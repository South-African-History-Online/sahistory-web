<?php

namespace Drupal\entity_reference_quantity\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;

/**
 * @FieldWidget(
 *   id = "entity_reference_quantity_select",
 *   label = @Translation("Select"),
 *   description = @Translation("A select field with associated data."),
 *   field_types = {
 *     "entity_reference_quantity"
 *   }
 * )
 */
class EntityReferenceQuantitySelect extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element += [
      '#type' => 'select',
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => isset($items[$delta]->target_id) ? $items[$delta]->target_id : '',
    ];

    $widget = [
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
      '#theme_wrappers' => ['container'],
    ];

    $widget['target_id'] = $element;
    $widget['quantity'] = [
      '#type' => 'number',
      '#size' => '4',
      '#default_value' => isset($items[$delta]) ? $items[$delta]->quantity : 1,
      '#weight' => 10,
    ];

    if ($this->fieldDefinition->getFieldStorageDefinition()->isMultiple()) {
      $widget['quantity']['#placeholder'] = $this->fieldDefinition->getSetting('qty_label');
    }
    else {
      $widget['quantity']['#title'] = $this->fieldDefinition->getSetting('qty_label');
    }

    return $widget;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    // Single select: add a 'none' option for non-required fields,
    // and a 'select a value' option for required fields that do not come
    // with a value selected.
    if (!$this->required) {
      return t('- None -');
    }
    if (!$this->has_value) {
      return t('- Select a value -');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#value'] == '_none') {
      if ($element['#required'] && $element['#value'] == '_none') {
        $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
      }
      else {
        $form_state->setValueForElement($element, NULL);
      }
    }
    else {
      $form_state->setValueForElement($element, $element['#value']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $data) {
      if (isset($data['element'])) {
        $values[$delta] = $data['element'];
      }
      if (empty($data['quantity'])) {
        unset($values[$delta]['quantity']);
      }
    }
    return $values;
  }

}
