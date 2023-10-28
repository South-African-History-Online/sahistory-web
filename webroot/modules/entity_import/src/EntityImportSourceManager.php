<?php

namespace Drupal\entity_import;

use Drupal\migrate\Plugin\MigrateSourcePluginManager;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * Define entity import source.
 */
class EntityImportSourceManager implements EntityImportSourceManagerInterface {

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationManager;

  /**
   * @var \Drupal\migrate\Plugin\MigrateSourcePluginManager
   */
  protected $migrateSourceManager;

  /**
   * Entity import source construct.
   *
   * @param \Drupal\migrate\Plugin\MigrateSourcePluginManager $migrate_source_manager
   *   The migrate source manager.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_manager
   *   The migration manager.
   */
  public function __construct(
    MigrateSourcePluginManager $migrate_source_manager,
    MigrationPluginManagerInterface $migration_manager
  ) {
    $this->migrationManager = $migration_manager;
    $this->migrateSourceManager = $migrate_source_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceStubInstance(
    $plugin_id,
    $configuration = [],
    MigrationInterface $migration = NULL
  ) {
    $migration = isset($migration)
      ? $migration
      : $this->migrationManager->createStubMigration([]);

    return $this
      ->migrateSourceManager
      ->createInstance($plugin_id, $configuration, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionAsOptions() {
    $options = [];

    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      $options[$plugin_id] = $definition->getLabel();
    }

    return $options;
  }

  /**
   * Get entity import definitions.
   *
   * @return array
   *   The entity import source definitions.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getDefinitions() {
    $definitions = [];

    foreach ($this->migrateSourceManager->getDefinitions() as $plugin_id => $definition) {
      $interface = 'Drupal\entity_import\Plugin\migrate\source\EntityImportSourceInterface';

      if (is_subclass_of($definition['class'], $interface)) {
        $definitions[$plugin_id] = $this->createSourceStubInstance($plugin_id);
      }
    }

    return $definitions;
  }
}
