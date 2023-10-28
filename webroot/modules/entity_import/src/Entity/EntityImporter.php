<?php

namespace Drupal\entity_import\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ConfigEntityType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Define entity configuration importer.
 *
 * @ConfigEntityType(
 *   id = "entity_importer",
 *   label = @Translation("Entity Importer"),
 *   config_prefix = "type",
 *   admin_permission = "administer entity import",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "expose_importer",
 *     "migration_source",
 *     "migration_entity",
 *     "migration_dependencies"
 *   },
 *   handlers = {
 *     "form" = {
 *       "add" = "\Drupal\entity_import\Form\EntityImporterForm",
 *       "edit" = "\Drupal\entity_import\Form\EntityImporterForm",
 *       "delete" = "\Drupal\entity_import\Form\EntityImporterDeleteForm",
 *     },
 *     "list_builder" = "\Drupal\entity_import\Controller\EntityImporterList",
 *     "route_provider" = {
 *       "html" = "\Drupal\entity_import\Entity\Routing\EntityImporterRouteDefault"
 *     }
 *   },
 *   links = {
 *     "collection" = "/admin/config/system/entity-importer",
 *     "add-form" = "/admin/config/system/entity-importer/add",
 *     "edit-form" = "/admin/config/system/entity-importer/{entity_importer}/edit",
 *     "delete-form" = "/admin/config/system/entity-importer/{entity_importer}/delete"
 *   }
 * )
 */
class EntityImporter extends EntityImporterConfigEntityBase implements EntityImporterInterface {

  /**
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $label;

  /**
   * @var string
   */
  public $description;

  /**
   * @var bool
   */
  public $expose_importer;

  /**
   * @var array
   */
  public $migration_source = [];

  /**
   * @var array
   */
  public $migration_entity = [];

  /**
   * @var array
   */
  public $migration_dependencies = [];

  /**
   * @var bool
   */
  protected $pageDisplayChanged;

  /**
   * {@inheritdoc}
   */
  public function exposeImporter() {
    // Support backwards compatibility as $this->display_page is no longer the
    // structure based on recent changes. After entity save, it'll be removed
    // as it's not defined in the config_export directive upon save.
    if (property_exists($this, 'display_page')) {
      return $this->display_page;
    }
    return $this->expose_importer;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getMigrationSource() {
    // Support backwards compatibility as $this->source is no longer the
    // structure based on recent changes. After entity save, it'll be removed
    // as it's not defined in the config_export directive upon save.
    if (property_exists($this, 'source')) {
      return $this->source;
    }

    return $this->migration_source['source'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMigrationEntity() {
    // Support backwards compatibility as $this->entity is no longer the
    // structure based on recent changes. After entity save, it'll be removed
    // as it's not defined in the config_export directive upon save.
    if (property_exists($this, 'entity')) {
      return $this->entity;
    }

    return $this->migration_entity['entity'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMigrationPluginId($bundle) {
    return "entity_import:{$this->id()}:{$bundle}";
  }

  /**
   * {@inheritdoc}
   */
  public function getImporterSourcePluginId() {
    return $this->getSourceInfoValue('plugin_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getImporterSourceConfiguration() {
    return $this->getSourceInfoValue('configuration', []);
  }

  /**
   * {@inheritdoc}
   */
  public function getImporterBundles() {
    return $this->getEntityInfoValue('bundles', []);
  }

  /**
   * {@inheritdoc}
   */
  public function getImporterEntityType() {
    return $this->getEntityInfoValue('type', NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getMigrationDependencies() {
    return $this->migration_dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPageDisplayChanged() {
    return $this->pageDisplayChanged;
  }

  /**
   * {@inheritDoc}
   */
  public function createUrl($route_name) {
    return Url::fromRoute($route_name, [
      'entity_importer' => $this->id()
    ]);
  }

  /**
   * {@inheritDoc}
   */
  public function createLink($text, $route_name) {
    return Link::createFromRoute($text, $route_name, [
      'entity_importer' => $this->id()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $this->pageDisplayChanged = $this->exposeImporter() == TRUE ?: FALSE;

    if (!$this->isNew()) {
      /** @var \Drupal\entity_import\Entity\EntityImporter $original */
      $original = $storage->loadUnchanged($this->getOriginalId());
      $this->pageDisplayChanged = $this->exposeImporter() != $original->exposeImporter()
        ?: FALSE;
    }
  }
  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    $this->onChange();
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    $this
      ->deleteOption()
      ->deleteFieldMapping();

    $this->onChange();
  }

  /**
   * {@inheritdoc}
   */
  public function onChange() {
    $this->clearMigrationPluginDiscovery();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function createMigrationInstance($bundle, array $definition = []) {
    $migration_definition = $this->getMigrationDefinition($bundle, $definition);

    return $this
      ->migrationPluginManager()
      ->createStubMigration($migration_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstBundle() {
    $bundles = $this->getImporterBundles();
    return reset($bundles);
  }

  /**
   * {@inheritdoc}
   */
  public function hasFieldMappings() {
    return !empty($this->getFieldMapping());
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleBundles() {
    return count($this->getImporterBundles()) > 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMappingOptions() {
    $options = [];

    foreach ($this->getFieldMapping() as $field_mapping) {
      $options[$field_mapping->name] = $field_mapping->label();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMapping() {
    return $this->getFieldMappingStorage()
      ->loadMultiple($this->getFieldMappingByImporterType());
  }

  /**
   * {@inheritDoc}
   */
  public function getMissingUniqueIdentifiers(array $identifiers) {
    $missing = [];

    foreach ($this->getFieldMappingUniqueIdentifiers() as $values) {
      if (!isset($values['reference_type'])) {
        continue;
      }
      $identifier_name = $values['reference_type'] === 'field_type'
        ? $values['identifier_name']
        : $values['identifier_type'];

      if (!isset($identifiers[$identifier_name])) {
        $missing[] = $identifier_name;
      }
    }

    return $missing;
  }

  /**
   * {@inheritDoc}
   */
  public function hasFieldMappingUniqueIdentifiers() {
    return !empty($this->getFieldMappingUniqueIdentifiers());
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMappingUniqueIdentifiers() {
    $unique_identifiers = $this
      ->getOptionsConfig()
      ->get('unique_identifiers.items');

    return $unique_identifiers ?: [];
  }

  /**
   * {@inheritDoc}
   */
  public function getDependencyMigrations(
    $bundle,
    $order = TRUE,
    $definition = []
  ) {
    $migrations = $this->createDependencyMigrations(
      $this->createMigrationInstance($bundle, $definition)
    );

    return !$order ? $migrations : array_reverse($migrations);
  }

  /**
   * {@inheritdoc}
   */
  public function loadDependencyMigration($plugin_id, $bundle) {
    return $this->getDependencyMigrations($bundle)[$plugin_id] ?? NULL;
  }

  /**
   * Create dependency migrations.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The required migration instance.
   * @param array $values
   * @param array $parents
   * @param array $migrations
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function createDependencyMigrations(
    MigrationInterface $migration,
    $values = [],
    $parents = [],
    &$migrations = []
  ) {
    $migrations[$migration->id()] = $migration;

    foreach ($migration->getMigrationDependencies()['optional'] as $plugin_id) {
      $configuration = NestedArray::getValue($values, $parents);

      /** @var MigrationInterface $instance */
      $instance = $this
        ->migrationPluginManager()
        ->createInstance($plugin_id, $configuration);

      $this->createDependencyMigrations($instance, $values, $parents, $migrations);
    }

    return $migrations;
  }

  /**
   * Get importer option configuration.
   *
   * @param bool $editable
   *   Determine if the field mapping configurations are editable.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   */
  protected function getOptionConfig($editable = FALSE) {
    $name = $this->getOptionConfigName();
    $factory = static::getConfigManager()->getConfigFactory();

    return $editable ? $factory->getEditable($name) : $factory->get($name);
  }

  /**
   * Get the importer option config name.
   *
   * @return string
   *   The importer option config name.
   */
  protected function getOptionConfigName() {
    return 'entity_import.options.'. $this->id();
  }

  /**
   * Delete importer options.
   *
   * @return $this
   */
  protected function deleteOption() {
    $this->getOptionConfig(TRUE)->delete();

    return $this;
  }

  /**
   * Delete field mappings.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function deleteFieldMapping() {
    foreach ($this->getFieldMapping() as $field_mapping) {
      $field_mapping->delete();
    }

    return $this;
  }

  /**
   * Delete migration plugin discovery cache.
   */
  protected function clearMigrationPluginDiscovery() {
    $this
      ->getMigrationDiscoveryCache()
      ->delete('migration_plugins');

    return $this;
  }

  /**
   * Get field mapping by bundle.
   *
   * @param $bundle
   *   The bundle name.
   *
   * @return array|mixed
   */
  protected function getFieldMappingByBundle($bundle) {
    return $this->getFieldMappingsKeyedByBundle()[$bundle] ?? [];
  }

  /**
   * Get field mapping keyed by bundle.
   */
  protected function getFieldMappingsKeyedByBundle() {
    $field_mappings = [];

    /** @var \Drupal\entity_import\Entity\EntityImporterFieldMapping $field_mapping */
    foreach ($this->getFieldMapping() as $entity_type_id => $field_mapping) {
      $field_mappings[$field_mapping->getImporterBundle()][] = $field_mapping;
    }

    return $field_mappings;
  }

  /**
   * Get the migration definition.
   *
   * @param $bundle
   *   The entity bundle type.
   * @param array $definition
   *
   * @return mixed
   */
  protected function getMigrationDefinition($bundle, array $definition = []) {
    if (!isset($bundle)) {
      throw new \RuntimeException(
        'The entity bundle is required for the migration definition.'
      );
    }
    $migration_definition = [
      'id' => $this->getMigrationPluginId($bundle),
      'label' => $this->t('@label: @bundle', [
        '@label' => $this->label(),
        '@bundle' => $bundle,
      ]),
      'source' => $this->getMigrateSourceDefinition(),
      'process' => $this->getMigrateProcessDefinition($bundle),
      'destination' => $this->getMigrateDestinationDefinition($bundle),
      'migration_dependencies' => $this->calculateMigrationDependencies($bundle),
    ];

    return array_merge_recursive($migration_definition, $definition);
  }

  /**
   * Get migrate source definition.
   *
   * @return array
   *   An array of full definition.
   */
  protected function getMigrateSourceDefinition() {
    $plugin_id = $this->getImporterSourcePluginId();

    return [
      'plugin' => $plugin_id,
      'importer_id' => $this->id(),
    ] + $this->getImporterSourceConfiguration();
  }

  /**
   * Get migrate destination definition.
   *
   * @param $bundle
   *   The entity bundle name.
   *
   * @return array
   *   An array of full definition.
   */
  protected function getMigrateDestinationDefinition($bundle) {
    return [
      'plugin' => "entity:{$this->getImporterEntityType()}",
      'default_bundle' => $bundle,
    ];
  }

  /**
   * Get migrate process definition.
   *
   * @param $bundle
   *   The entity bundle name.
   *
   * @return array
   *   An array of full definition.
   */
  protected function getMigrateProcessDefinition($bundle) {
    $definition = [];

    foreach ($this->getFieldMappingByBundle($bundle) as $field_mapping) {
      if (!$field_mapping instanceof EntityImporterFieldMapping) {
        continue;
      }
      $processes = [];

      $source = trim($field_mapping->getSource());
      $configuration = $field_mapping->getProcessingConfiguration();

      // Iterate over all processes that were configured in a field mapping.
      if (isset($configuration['plugins'])
        && !empty($configuration['plugins'])) {
        $count = 1;

        foreach ($configuration['plugins'] as $plugin_id => $info) {
          $process = [
            'plugin' => $plugin_id,
          ] + array_filter($info['settings']);

          // Only add the source directive to the first process plugin. The
          // rest of the processes will inherit the value from the pipeline.
          if ($count === 1) {
            $process['source'] = $source;
          }
          $count++;

          $processes[] = $process;
        }
      }

      $definition[$field_mapping->getDestination()] = !empty($processes)
        ? $processes
        : $source;
    }

    return $definition;
  }

  /**
   * Calculate migration dependencies.
   *
   * @param $bundle
   *   The entity bundle name.
   *
   * @return array
   *   An array of migration dependencies.
   */
  protected function calculateMigrationDependencies($bundle) {
    $dependencies = [
      'optional' => [],
      'required' => []
    ];

    // Add migration dependencies that were assigned on the importer.
    foreach ($this->getMigrationDependencies() as $level => $dependency) {
      $migration = array_values($dependency['migration']);
      $dependencies[$level] = array_merge(
        $dependencies[$level], $migration
      );
    }

    return array_merge_recursive(
      $dependencies,
      $this->getMigrationLookupDependencies($bundle)
    );
  }

  /**
   * Get migration lookup dependencies.
   *
   * @param $bundle
   *   The entity bundle name.
   *
   * @return array
   *   An array of dependencies.
   */
  protected function getMigrationLookupDependencies($bundle) {
    $plugin_id = 'entity_import_migrate_lookup';
    $dependencies = [
      'optional' => []
    ];

    /** @var \Drupal\entity_import\Entity\EntityImporterFieldMapping $field_mapping */
    foreach ($this->getFieldMappingByBundle($bundle) as $name => $field_mapping) {
      if (!$field_mapping->hasProcessingPlugin($plugin_id)) {
        continue;
      }
      $configuration = $field_mapping->getProcessingConfiguration();

      if (!isset($configuration['plugins'])) {
        continue;
      }
      $plugins = $configuration['plugins'];

      if (!isset($plugins[$plugin_id])) {
        continue;
      }
      $plugin = $plugins[$plugin_id];

      if (!isset($plugin['settings']['migration'])
        || empty($plugin['settings']['migration'])) {
        continue;
      }
      $migrations = is_array($plugin['settings']['migration'])
        ? array_values($plugin['settings']['migration'])
        : [$plugin['settings']['migration']];

      $dependencies['optional'] = array_merge($dependencies['optional'], $migrations);
    }

    return $dependencies;
  }

  /**
   * Get entity source value.
   *
   * @param $key
   *   The key name.
   * @param null $default
   *   The default value if key is not found.
   *
   * @return mixed|NULL
   */
  protected function getSourceInfoValue($key, $default = NULL) {
    return $this->getMigrationSource()[$key] ?? $default;
  }

  /**
   * Get entity info value.
   *
   * @param $key
   *   The key name.
   * @param null $default
   *   The default value if key is not found.
   *
   * @return mixed|NULL
   */
  protected function getEntityInfoValue($key, $default = NULL) {
    return $this->getMigrationEntity()[$key] ?? $default;
  }

  /**
   * Get field mapping configurations by importer type.
   *
   * @return array|int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFieldMappingByImporterType() {
    $query = $this->getFieldMappingStorage()
      ->getQuery()
      ->condition('importer_type', $this->id());

    return $query->execute();
  }

  /**
   * Get field mapping storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFieldMappingStorage() {
    return $this->entityTypeManager()
      ->getStorage('entity_importer_field_mapping');
  }

  /**
   * Get migration discovery cache backend.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   */
  protected function getMigrationDiscoveryCache() {
    return \Drupal::service('cache.discovery_migration');
  }

  /**
   * Get importer options configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   */
  protected function getOptionsConfig() {
    return \Drupal::config("entity_import.options.{$this->id()}");
  }

  /**
   * Entity import source manager.
   *
   * @return \Drupal\entity_import\EntityImportSourceManager
   */
  protected function entityImportSourceManager() {
    return \Drupal::service('entity_import.source.manager');
  }

  /**
   * Migration plugin manager.
   *
   * @return \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected function migrationPluginManager() {
    return \Drupal::service('plugin.manager.migration');
  }
}
