<?php

namespace Drupal\entity_import;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Define the entity properties service.
 */
class EntityImportEntityProperties implements EntityImportEntityPropertiesInterface{

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity properties constructor.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed configuration manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(
    TypedConfigManagerInterface $typed_config_manager,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    $this->typedConfigManager = $typed_config_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function getEntityPropertiesOptions(
    EntityTypeInterface $entity_type,
    $bundle
  ) {
    $options = [];
    $entity_class = $entity_type->getOriginalClass();

    if (is_subclass_of($entity_class, ConfigEntityInterface::class)) {
      $options = $this->getEntityTypedDataMappingOptions(
        $entity_type
      );
    }

    if (is_subclass_of($entity_class, FieldableEntityInterface::class)) {
      $options = $this->getEntityFieldPropertyOptions(
        $entity_type, $bundle
      );
    }

    return $options;
  }

  /**
   * Get the entity typed data mapping options.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type
   *   The entity type instance.
   *
   * @return array
   *   An array of allowed typed data mapping options.
   */
  protected function getEntityTypedDataMappingOptions(
    ConfigEntityTypeInterface $entity_type
  ) {
    $options = [];
    $config_prefix = $entity_type->getConfigPrefix();

    if ($typed_data_key = $this->findTypedDataKeyByPrefix($config_prefix)) {
      $definition = $this->typedConfigManager->getDefinition($typed_data_key);

      if (isset($definition['mapping'])) {
        $excluded_types = [
          'sequence',
          '_core_config_info',
          'config_dependencies'
        ];
        foreach ($definition['mapping'] as $name => $info) {
          if (in_array($info['type'], $excluded_types)) {
            continue;
          }
          $options[$name] = $info['label'] ?? $name;
        }
      }
    }

    return $options;
  }

  /**
   * Get the entity field property options.
   *
   * @param $entity_type
   *   The entity type identifier.
   * @param $bundle
   *   The entity bundle type.
   *
   * @return array
   *   An array of entity property options.
   */
  protected function getEntityFieldPropertyOptions(
    ContentEntityTypeInterface $entity_type,
    $bundle
  ) {
    $options = [];

    $fields = $this->loadEntityFieldDefinitions($entity_type->id(), $bundle);

    foreach ($fields as $field_name => $field) {
      if ($field->isComputed()) {
        continue;
      }
      /** @var \Drupal\Core\Field\TypedData\FieldItemDataDefinition $item_definition */
      $item_definition = $field->getItemDefinition();

      /** @var \Drupal\Core\TypedData\DataDefinition $data_definition */
      foreach ($item_definition->getPropertyDefinitions() as $property_name => $data_definition) {
        if ($data_definition->isComputed() || $data_definition->isReadOnly()) {
          continue;
        }
        if ($item_definition->getMainPropertyName() === $property_name) {
          $options["{$field_name}"] = $this->t('@field_name', [
            '@field_name' => $field_name,
          ]);
        }
        else {
          $options["{$field_name}/{$property_name}"] = $this->t('@field_name/@property_name', [
            '@field_name' => $field_name,
            '@property_name' => $property_name,
          ]);
        }
      }
    }

    return $options;
  }

  /**
   * Find typed data key by entity configuration prefix.
   *
   * @param $config_prefix
   *   The entity type configuration prefix.
   *
   * @return string|null
   *   Return the type data key based on the configuration prefix.
   */
  protected function findTypedDataKeyByPrefix($config_prefix) {
    $typed_data_key = preg_grep(
      "/$config_prefix\.+/",
      array_keys($this->typedConfigManager->getDefinitions())
    );

    return reset($typed_data_key) ?? NULL;
  }

  /**
   * Load the entity field definitions.
   *
   * @param $entity_type
   *   The entity type identifier.
   * @param $bundle
   *   The entity bundle type.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of entity field definitions.
   */
  protected function loadEntityFieldDefinitions($entity_type, $bundle) {
    return $this
      ->entityFieldManager
      ->getFieldDefinitions($entity_type, $bundle);
  }
}
