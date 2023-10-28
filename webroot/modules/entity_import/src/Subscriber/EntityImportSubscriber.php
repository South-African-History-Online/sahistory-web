<?php

namespace Drupal\entity_import\Subscriber;

use Drupal\entity_import\Plugin\migrate\source\EntityImportSourceInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Define entity import event subscriber.
 */
class EntityImportSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::POST_IMPORT => 'onMigratePostImport'
    ];
  }

  /**
   * On migration post import.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migration import event instance.
   */
  public function onMigratePostImport(MigrateImportEvent $event) {
    $migration = $event->getMigration();
    $source_plugin = $migration->getSourcePlugin();

    if ($source_plugin instanceof EntityImportSourceInterface) {
      $source_plugin->runCleanup();
    }
  }
}
