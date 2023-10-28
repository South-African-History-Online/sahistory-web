<?php

namespace Drupal\entity_import;

use Drupal\entity_import\Event\EntityImportEvents;
use Drupal\entity_import\Event\EntityImportMigrationStubEvent;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Define entity import process manager.
 */
class EntityImportProcessManager implements EntityImportProcessManagerInterface {

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationManager;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migratePluginManager;

  /**
   * Entity import process manager constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_manager
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $migrate_plugin_manager
   */
  public function __construct(
    EventDispatcherInterface $event_dispatcher,
    MigrationPluginManagerInterface $migration_manager,
    MigratePluginManagerInterface $migrate_plugin_manager
  ) {
    $this->eventDispatcher = $event_dispatcher;
    $this->migrationManager = $migration_manager;
    $this->migratePluginManager = $migrate_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createPluginInstance(
    $plugin_id,
    $configuration = [],
    MigrationInterface $migration = NULL
  ) {
    $prepare_event = $this->eventDispatcher
      ->dispatch(
        EntityImportEvents::ENTITY_IMPORT_PREPARE_MIGRATION_STUB,
        new EntityImportMigrationStubEvent($plugin_id, [])
      );

    $migration = isset($migration)
      ? $migration
      : $this->migrationManager->createStubMigration($prepare_event->getConfiguration());

    return $this
      ->migratePluginManager
      ->createInstance($plugin_id, $configuration, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public function getMigrationProcessInfo() {
    $info = [];

    foreach ($this->getPluginInstances() as $plugin_id => $instance) {
      $info['options'][$plugin_id] = $instance->getLabel();
      $info['instances'][$plugin_id] = $instance;
    }

    return $info;
  }

  /**
   * Get entity import process plugin instances.
   *
   * @return array
   *   An array plugin instances.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getPluginInstances() {
    $definitions = [];

    foreach ($this->migratePluginManager->getDefinitions() as $plugin_id => $definition) {
      $interface = '\Drupal\entity_import\Plugin\migrate\process\EntityImportProcessInterface';

      if (!is_subclass_of($definition['class'], $interface)) {
        continue;
      }
      $definitions[$plugin_id] = $this->createPluginInstance($plugin_id);
    }

    return $definitions;
  }
}
