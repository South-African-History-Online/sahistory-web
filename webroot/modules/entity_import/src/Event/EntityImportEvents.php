<?php

namespace Drupal\entity_import\Event;

/**
 * Define entity import events handler.
 */
final class EntityImportEvents {

  /**
   * Entity import prepare configurations for the migration stub. The
   * EntityImportMigrationStubEvent() is provided to all subscribers.
   *
   * @see \Drupal\entity_import\Event\EntityImportMigrationStubEvent
   */
  const ENTITY_IMPORT_PREPARE_MIGRATION_STUB = 'entity_import.prepare_migration_stub';
}
