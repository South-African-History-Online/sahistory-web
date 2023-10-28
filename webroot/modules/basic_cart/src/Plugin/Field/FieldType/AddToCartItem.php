<?php

namespace Drupal\basic_cart\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\basic_cart\Utility;

/**
 * Plugin implementation of the 'addtocart' field type.
 *
 * @FieldType(
 *   id = "addtocart",
 *   label = @Translation("Add to cart"),
 *   module = "basic_cart",
 *   description = @Translation("Demonstrates a field."),
 *   default_widget = "addtocart",
 *   default_formatter = "addtocart",
 *   no_ui = TRUE
 * )
 */
class AddToCartItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $config = Utility::cartSettings();
    $properties['value'] = DataDefinition::create('boolean')
      ->setLabel(t($config->get('add_to_cart_button')));
    // $properties['no_ui'] = TRUE;.
    return $properties;
  }

}
