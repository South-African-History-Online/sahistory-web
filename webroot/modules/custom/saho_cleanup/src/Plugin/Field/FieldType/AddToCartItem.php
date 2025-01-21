<?php

namespace Drupal\saho_cleanup\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Stub plugin for the "addtocart" field type.
 *
 * @FieldType(
 *   id = "addtocart",
 *   label = @Translation("Add to Cart (Stub)"),
 *   description = @Translation("A stub field plugin to facilitate removing leftover Add to Cart fields."),
 *   default_widget = "string_textfield",
 *   default_formatter = "string"
 * )
 */
class AddToCartItem extends FieldItemBase
{

    /**
     * Defines the schema for the field's database columns.
     *
     * @see \Drupal\Core\Field\FieldStorageDefinitionInterface
     */
    public static function schema(FieldStorageDefinitionInterface $field_storage_definition)
    {
        return [
        'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => false,
        ],
        ],
        ];
    }

    /**
     * Returns an array of property definitions for each data element in the field item.
     *
     * @see \Drupal\Core\Field\FieldItemInterface::propertyDefinitions()
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties = [];
        // Minimal single property called "value".
        $properties['value'] = DataDefinition::create('string')
            ->setLabel(t('Value'))
            ->setRequired(false);

        return $properties;
    }

    /**
     * The main property name of the field (e.g., "value", "target_id", etc.).
     */
    public static function mainPropertyName()
    {
        return 'value';
    }

    /**
     * Determines if this field item is empty.
     */
    public function isEmpty()
    {
        $value = $this->get('value')->getValue();
        return $value === null || $value === '';
    }

}
