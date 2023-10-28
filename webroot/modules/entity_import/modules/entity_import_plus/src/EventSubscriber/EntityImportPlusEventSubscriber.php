<?php

namespace Drupal\entity_import_plus\EventSubscriber;

use Drupal\entity_import\Event\EntityImportEvents;
use Drupal\entity_import\Event\EntityImportMigrationStubEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Define entity import plus event subscriber.
 */
class EntityImportPlusEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityImportEvents::ENTITY_IMPORT_PREPARE_MIGRATION_STUB => 'onPrepareMigrationStub'
    ];
  }

  /**
   * React on the creation of the migration stub instance.
   *
   * @param \Drupal\entity_import\Event\EntityImportMigrationStubEvent $event
   *   The event instance.
   */
  public function onPrepareMigrationStub(EntityImportMigrationStubEvent $event) {
    // We need to define a stub migration configuration for the destination as
    // the EntityLookup() makes unnecessary calls in their constructor.
    if ($event->getPluginId() === 'entity_import_plus_entity_lookup') {
      $event->setConfigurationValue('destination', [
        'plugin' => 'entity:node'
      ]);
    }
  }
}
