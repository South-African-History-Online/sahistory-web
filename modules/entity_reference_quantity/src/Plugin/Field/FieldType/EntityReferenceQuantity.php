<?php

namespace Drupal\entity_reference_quantity\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_quantity' field type.
 *
 * @FieldType(
 *   id = "entity_reference_quantity",
 *   label = @Translation("Entity reference w/quantity"),
 *   description = @Translation("Entity reference with associated quantity"),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_quantity_autocomplete",
 *   default_formatter = "entity_reference_quantity_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList" * )
 */
class EntityReferenceQuantity extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $quantity_definition = DataDefinition::create('integer')
      ->setLabel($field_definition->getSetting('qty_label'));
    $properties['quantity'] = $quantity_definition;
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['quantity'] = [
      'type' => 'int',
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'qty_label' => t('Quantity'),
      'qty_min' => 0,
      'qty_max' => 999,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::fieldSettingsForm($form, $form_state);

    $elements['qty_min'] = [
      '#type' => 'number',
      '#title' => t('Minimum'),
      '#default_value' => $this->getSetting('qty_min'),
    ];
    $elements['qty_max'] = [
      '#type' => 'number',
      '#title' => t('Maximum'),
      '#default_value' => $this->getSetting('qty_max'),
    ];
    $elements['qty_label'] = [
      '#type' => 'textfield',
      '#title' => t('Quantity Label'),
      '#default_value' => $this->getSetting('qty_label'),
      '#description' => $this->t('Also used as a placeholder in multi-value instances.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    // In the base EntityReference class, this is used to populate the
    // list of field-types with options for each destination entity type.
    // Too much work, we'll just make people fill that out later.
    // Also, keeps the field type dropdown from getting too cluttered.
    return [];
  }

}
