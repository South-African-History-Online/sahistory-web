<?php

namespace Drupal\entity_reference_quantity\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;

/**
 * @FieldWidget(
 *   id = "entity_reference_quantity_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field with associated data."),
 *   field_types = {
 *     "entity_reference_quantity"
 *   }
 * )
 */
class EntityReferenceQuantityAutocomplete extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = [
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
      '#theme_wrappers' => ['container'],
    ];
    $widget['target_id'] = parent::formElement($items, $delta, $element, $form, $form_state);
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
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    foreach ($values as $delta => $data) {
      if (empty($data['quantity'])) {
        unset($values[$delta]['quantity']);
      }
    }
    return $values;
  }

}
