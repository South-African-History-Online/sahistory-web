<?php

namespace Drupal\saho_dashboard\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;

/**
 * Builds the contributor dashboard shown on a user's own profile page.
 *
 * Deliberately lightweight: three read-only sections (stats, recent work,
 * review reminders) driven by two small queries. No moderation states, no
 * task queues - just a gentle nudge towards content that has gone stale.
 */
class DashboardBuilder {

  /**
   * Content untouched for this long is suggested for review (3 years).
   */
  const REVIEW_AGE_SECONDS = 94608000;

  /**
   * Maximum items listed per section.
   */
  const ITEMS_PER_SECTION = 6;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a DashboardBuilder.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, TimeInterface $time) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * Builds the dashboard render data for the given account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user whose profile page is being viewed.
   *
   * @return array|null
   *   Render-ready data, or NULL when the user has no content activity.
   */
  public function build(UserInterface $account) {
    $uid = (int) $account->id();
    $stats = $this->getStats($uid);
    if ($stats['worked_on'] === 0) {
      return NULL;
    }

    return [
      'stats' => $stats,
      'recent' => $this->getRecentItems($uid),
      'review' => $this->getReviewItems($uid),
    ];
  }

  /**
   * Returns headline counts for the user.
   */
  protected function getStats(int $uid): array {
    $authored = (int) $this->database->select('node_field_data', 'n')
      ->condition('n.uid', $uid)
      ->condition('n.default_langcode', 1)
      ->countQuery()
      ->execute()
      ->fetchField();

    $worked_on = (int) $this->database->query(
      'SELECT COUNT(DISTINCT r.nid) FROM {node_revision} r WHERE r.revision_uid = :uid',
      [':uid' => $uid]
    )->fetchField();

    return [
      'authored' => $authored,
      'worked_on' => max($worked_on, $authored),
    ];
  }

  /**
   * Nodes the user last touched, by their own newest revision.
   *
   * Sorting by the user's own revision timestamp (not node.changed) keeps
   * bulk re-saves by other people or scripts from drowning out the user's
   * actual edits.
   */
  protected function getRecentItems(int $uid): array {
    $rows = $this->database->queryRange(
      'SELECT r.nid, MAX(r.revision_timestamp) AS own_ts
       FROM {node_revision} r
       INNER JOIN {node_field_data} n ON n.nid = r.nid AND n.default_langcode = 1
       WHERE r.revision_uid = :uid
       GROUP BY r.nid
       ORDER BY own_ts DESC',
      0, self::ITEMS_PER_SECTION,
      [':uid' => $uid]
    )->fetchAllKeyed();

    return $this->loadItems(array_keys($rows), array_map('intval', $rows));
  }

  /**
   * Published nodes the user authored that have gone stale, oldest first.
   */
  protected function getReviewItems(int $uid): array {
    $cutoff = $this->time->getRequestTime() - self::REVIEW_AGE_SECONDS;
    $nids = $this->database->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('n.uid', $uid)
      ->condition('n.status', 1)
      ->condition('n.default_langcode', 1)
      ->condition('n.changed', $cutoff, '<')
      ->orderBy('n.changed', 'ASC')
      ->range(0, self::ITEMS_PER_SECTION)
      ->execute()
      ->fetchCol();

    return $this->loadItems($nids);
  }

  /**
   * Loads nodes and maps them to simple template-ready rows.
   *
   * @param array $nids
   *   Node IDs in display order.
   * @param array $timestamps
   *   Optional nid-keyed timestamps overriding node.changed for display -
   *   used to show the user's own last edit rather than the global one.
   */
  protected function loadItems(array $nids, array $timestamps = []): array {
    if (!$nids) {
      return [];
    }
    $items = [];
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    foreach ($nids as $nid) {
      if (!isset($nodes[$nid])) {
        continue;
      }
      $node = $nodes[$nid];
      if (!$node->access('view')) {
        continue;
      }
      $type = $this->entityTypeManager->getStorage('node_type')->load($node->bundle());
      $timestamp = $timestamps[$nid] ?? $node->getChangedTime();
      $items[] = [
        'title' => $node->label(),
        'url' => $node->toUrl()->toString(),
        'edit_url' => $node->access('update') ? $node->toUrl('edit-form')->toString() : NULL,
        'type' => $type ? $type->label() : $node->bundle(),
        'published' => $node->isPublished(),
        'changed' => $this->dateFormatter->format($timestamp, 'custom', 'j F Y'),
        'changed_ago' => $this->dateFormatter->formatTimeDiffSince($timestamp, ['granularity' => 1]),
      ];
    }
    return $items;
  }

}
