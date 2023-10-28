<?php

namespace Drupal\uc_product\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the Ubercart weight field type.
 *
 * @FieldType(
 *   id = "uc_weight",
 *   label = @Translation("Weight"),
 *   description = @Translation("This field stores a weight in the database."),
 *   default_widget = "uc_weight",
 *   default_formatter = "uc_weight"
 * )
 */
class UcWeightItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('float')
      ->setLabel(t('Weight'));
    $properties['units'] = DataDefinition::create('string')
      ->setLabel(t('Units'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'float',
          'not null' => FALSE,
        ),
        'units' => array(
          'type' => 'char',
          'length' => 2,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = mt_rand(1, 999);
    $values['units'] = array_rand(array_flip(['lb', 'kg', 'oz', 'g']));
    return $values;
  }

}
