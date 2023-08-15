<?php

namespace Drupal\uc_product\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the Ubercart dimensions field type.
 *
 * @FieldType(
 *   id = "uc_dimensions",
 *   label = @Translation("Dimensions"),
 *   description = @Translation("This field stores a set of dimensions in the database."),
 *   default_widget = "uc_dimensions",
 *   default_formatter = "uc_dimensions"
 * )
 */
class UcDimensionsItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['length'] = DataDefinition::create('float')
      ->setLabel(t('Length'));
    $properties['width'] = DataDefinition::create('float')
      ->setLabel(t('Width'));
    $properties['height'] = DataDefinition::create('float')
      ->setLabel(t('Height'));
    $properties['units'] = DataDefinition::create('string')
      ->setLabel(t('Units'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'length' => array(
          'type' => 'float',
          'not null' => FALSE,
        ),
        'width' => array(
          'type' => 'float',
          'not null' => FALSE,
        ),
        'height' => array(
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
    $values['length'] = mt_rand(1, 999);
    $values['width'] = mt_rand(1, 999);
    $values['height'] = mt_rand(1, 999);
    $values['units'] = array_rand(array_flip(['in', 'ft', 'cm', 'mm']));
    return $values;
  }

}
