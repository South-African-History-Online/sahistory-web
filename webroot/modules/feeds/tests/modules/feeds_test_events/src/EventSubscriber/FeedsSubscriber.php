<?php

namespace Drupal\feeds_test_events\EventSubscriber;

use Drupal\feeds\Event\CleanEvent;
use Drupal\feeds\Event\ClearEvent;
use Drupal\feeds\Event\DeleteFeedsEvent;
use Drupal\feeds\Event\EntityEvent;
use Drupal\feeds\Event\ExpireEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\FetchEvent;
use Drupal\feeds\Event\ImportFinishedEvent;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Event\ProcessEvent;
use Drupal\feeds\Exception\EmptyFeedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * React on authors being processed.
 */
class FeedsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FeedsEvents::FEEDS_DELETE => ['onDelete'],
      FeedsEvents::INIT_IMPORT => ['onInitImport'],
      FeedsEvents::FETCH => [
        ['preFetch', FeedsEvents::BEFORE],
        ['postFetch', FeedsEvents::AFTER],
      ],
      FeedsEvents::PARSE => [
        ['preParse', FeedsEvents::BEFORE],
        ['postParse', FeedsEvents::AFTER],
      ],
      FeedsEvents::PROCESS => [
        ['preProcess', FeedsEvents::BEFORE],
        ['postProcess', FeedsEvents::AFTER],
      ],
      FeedsEvents::PROCESS_ENTITY_PREVALIDATE => ['prevalidate'],
      FeedsEvents::PROCESS_ENTITY_PRESAVE => ['preSave'],
      FeedsEvents::PROCESS_ENTITY_POSTSAVE => ['postSave'],
      FeedsEvents::CLEAN => ['onClean'],
      FeedsEvents::INIT_CLEAR => ['onInitClear'],
      FeedsEvents::CLEAR => ['onClear'],
      FeedsEvents::INIT_EXPIRE => ['onInitExpire'],
      FeedsEvents::EXPIRE => ['onExpire'],
      FeedsEvents::IMPORT_FINISHED => ['onFinish'],
    ];
  }

  /**
   * Acts on multiple feeds getting deleted.
   */
  public function onDelete(DeleteFeedsEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on an import being initiated.
   */
  public function onInitImport(InitEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on event before fetching.
   */
  public function preFetch(FetchEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on fetcher result.
   */
  public function postFetch(FetchEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on event before parsing.
   */
  public function preParse(ParseEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on parser result.
   */
  public function postParse(ParseEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on event before processing.
   */
  public function preProcess(ProcessEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on process result.
   */
  public function postProcess(ProcessEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on an entity before validation.
   */
  public function prevalidate(EntityEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');

    $feed_type_id = $event->getFeed()->getType()->id();
    switch ($feed_type_id) {
      case 'no_title':
        // A title is required, set a title on the entity to prevent validation
        // errors.
        $event->getEntity()->title = 'foo';
        break;
    }
  }

  /**
   * Acts on presaving an entity.
   */
  public function preSave(EntityEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');

    $feed_type_id = $event->getFeed()->getType()->id();
    switch ($feed_type_id) {
      case 'import_skip':
        // We do not save the node called 'Lorem ipsum'.
        if ($event->getEntity()->getTitle() == 'Lorem ipsum') {
          throw new EmptyFeedException();
        }
        break;
    }
  }

  /**
   * Acts on postsaving an entity.
   */
  public function postSave(EntityEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on the cleaning stage.
   */
  public function onClean(CleanEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on event before deleting items begins.
   */
  public function onInitClear(InitEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on event where deleting items has began.
   */
  public function onClear(ClearEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on event before expiring items begins.
   */
  public function onInitExpire(InitEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on event where expiring items has began.
   */
  public function onExpire(ExpireEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

  /**
   * Acts on the completion of an import.
   */
  public function onFinish(ImportFinishedEvent $event) {
    $GLOBALS['feeds_test_events'][] = (__METHOD__ . ' called');
  }

}
