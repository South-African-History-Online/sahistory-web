<?php

namespace Drupal\saho_timeline\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Service for migrating timeline articles to events.
 */
class TimelineMigrationService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a TimelineMigrationService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Identify articles that should be migrated to timeline events.
   *
   * @return array
   *   Array of article nodes that are timeline candidates.
   */
  public function identifyTimelineArticles() {
    $storage = $this->entityTypeManager->getStorage('node');
    $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Look for articles with timeline-related tags or categories.
    $query = $storage->getQuery()
      ->condition('type', 'article')
      ->condition('status', NodeInterface::PUBLISHED)
      ->accessCheck(TRUE);

    // Check for timeline tags/categories if they exist.
    $or_group = $query->orConditionGroup();

    // Check title for timeline keywords.
    $or_group->condition('title', 'timeline', 'CONTAINS');
    $or_group->condition('title', 'chronology', 'CONTAINS');
    $or_group->condition('title', 'historical events', 'CONTAINS');

    // Check if there are timeline-related taxonomy terms.
    $timeline_terms = $taxonomy_storage->getQuery()
      ->condition('name', 'timeline', 'CONTAINS')
      ->accessCheck(TRUE)
      ->execute();

    if (!empty($timeline_terms)) {
      $or_group->condition('field_tags', $timeline_terms, 'IN');
    }

    $query->condition($or_group);
    $nids = $query->execute();

    return $storage->loadMultiple($nids);
  }

  /**
   * Migrate an article to an event node.
   *
   * @param \Drupal\node\NodeInterface $article
   *   The article node to migrate.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The created event node or NULL on failure.
   */
  public function migrateArticleToEvent(NodeInterface $article) {
    try {
      $event_data = [
        'type' => 'event',
        'title' => $article->getTitle(),
        'status' => $article->isPublished(),
        'uid' => $article->getOwnerId(),
        'created' => $article->getCreatedTime(),
        'changed' => $article->getChangedTime(),
      ];

      // Copy body field if it exists.
      if ($article->hasField('body') && !$article->get('body')->isEmpty()) {
        $event_data['body'] = $article->get('body')->getValue();
      }

      // Extract date from article if possible.
      $event_date = $this->extractEventDate($article);
      if ($event_date) {
        $event_data['field_event_date'] = $event_date;
      }

      // Copy taxonomy terms if applicable.
      if ($article->hasField('field_tags') && !$article->get('field_tags')->isEmpty()) {
        $event_data['field_event_tags'] = $article->get('field_tags')->getValue();
      }

      // Copy media/images if they exist.
      if ($article->hasField('field_image') && !$article->get('field_image')->isEmpty()) {
        $event_data['field_event_image'] = $article->get('field_image')->getValue();
      }

      // Create the event node.
      $event = $this->entityTypeManager->getStorage('node')->create($event_data);
      $event->save();

      // Log the migration.
      $this->loggerFactory->get('saho_timeline')->info('Migrated article @article_id to event @event_id', [
        '@article_id' => $article->id(),
        '@event_id' => $event->id(),
      ]);

      // Store migration mapping.
      $this->storeMigrationMapping($article->id(), $event->id());

      return $event;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('saho_timeline')->error('Failed to migrate article @id: @message', [
        '@id' => $article->id(),
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Extract event date from article.
   *
   * @param \Drupal\node\NodeInterface $article
   *   The article node.
   *
   * @return string|null
   *   The extracted date or NULL.
   */
  protected function extractEventDate(NodeInterface $article) {
    // Check if there's already a date field.
    if ($article->hasField('field_date') && !$article->get('field_date')->isEmpty()) {
      return $article->get('field_date')->value;
    }

    // Try to extract date from title or body.
    $title = $article->getTitle();
    $body = '';
    if ($article->hasField('body') && !$article->get('body')->isEmpty()) {
      $body = $article->get('body')->value;
    }

    // Look for date patterns.
    $patterns = [
    // YYYY-MM-DD.
      '/(\d{4})-(\d{1,2})-(\d{1,2})/',
    // MM/DD/YYYY.
      '/(\d{1,2})\/(\d{1,2})\/(\d{4})/',
    // Just year.
      '/(\d{4})/',
    ];

    foreach ($patterns as $pattern) {
      if (preg_match($pattern, $title . ' ' . $body, $matches)) {
        if (count($matches) == 4) {
          // Full date found.
          return sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
        }
        elseif (count($matches) == 2) {
          // Just year found.
          return sprintf('%04d-01-01', $matches[1]);
        }
      }
    }

    return NULL;
  }

  /**
   * Store migration mapping.
   *
   * @param int $article_id
   *   The original article ID.
   * @param int $event_id
   *   The new event ID.
   */
  protected function storeMigrationMapping($article_id, $event_id) {
    try {
      $this->database->insert('saho_timeline_migration')
        ->fields([
          'article_id' => $article_id,
          'event_id' => $event_id,
          'migrated' => time(),
        ])
        ->execute();
    }
    catch (\Exception $e) {
      // Table might not exist, create it.
      $this->createMigrationTable();
      // Try again.
      $this->database->insert('saho_timeline_migration')
        ->fields([
          'article_id' => $article_id,
          'event_id' => $event_id,
          'migrated' => time(),
        ])
        ->execute();
    }
  }

  /**
   * Create migration tracking table.
   */
  protected function createMigrationTable() {
    $schema = $this->database->schema();
    if (!$schema->tableExists('saho_timeline_migration')) {
      $schema->createTable('saho_timeline_migration', [
        'fields' => [
          'article_id' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
          'event_id' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
          'migrated' => [
            'type' => 'int',
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['article_id'],
        'indexes' => [
          'event_id' => ['event_id'],
        ],
      ]);
    }
  }

  /**
   * Batch migrate multiple articles.
   *
   * @param array $article_ids
   *   Array of article node IDs to migrate.
   *
   * @return array
   *   Migration results.
   */
  public function batchMigrateArticles(array $article_ids) {
    $results = [
      'success' => 0,
      'failed' => 0,
      'skipped' => 0,
      'events' => [],
    ];

    $storage = $this->entityTypeManager->getStorage('node');
    $articles = $storage->loadMultiple($article_ids);

    foreach ($articles as $article) {
      // Check if already migrated.
      $existing = $this->database->select('saho_timeline_migration', 'm')
        ->fields('m', ['event_id'])
        ->condition('article_id', $article->id())
        ->execute()
        ->fetchField();

      if ($existing) {
        $results['skipped']++;
        continue;
      }

      $event = $this->migrateArticleToEvent($article);
      if ($event) {
        $results['success']++;
        $results['events'][] = $event->id();
      }
      else {
        $results['failed']++;
      }
    }

    return $results;
  }

}
