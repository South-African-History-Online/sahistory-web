<?php

namespace Drupal\saho_dashboard\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Builds the contributor dashboard shown on a user's own profile page.
 *
 * Deliberately lightweight: read-only sections (role badges, achievements,
 * task shortcuts, recent work, review reminders) computed live from existing
 * tables. No moderation states, no task queues, no new storage - just a
 * gentle, playful nudge towards the work that keeps the record healthy.
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
   * Achievement catalog: thresholds are bronze / silver / gold.
   */
  const ACHIEVEMENTS = [
    'quill' => [
      'name' => 'The Quill',
      'metric' => 'pieces worked on',
      'blurb' => 'Every edit adds a line to the record.',
      'tiers' => [10, 250, 1000],
    ],
    'compass' => [
      'name' => 'The Compass',
      'metric' => 'kinds of content touched',
      'blurb' => 'Biographies, places, events - ranging widely.',
      'tiers' => [3, 6, 10],
    ],
    'lantern' => [
      'name' => 'The Lantern',
      'metric' => 'stale pages revived',
      'blurb' => 'Brought light to pages untouched for 3+ years.',
      'tiers' => [5, 25, 100],
    ],
    'laurel' => [
      'name' => 'The Laurel',
      'metric' => 'years contributing',
      'blurb' => 'Long service to South African history.',
      'tiers' => [1, 5, 10],
    ],
    'ember' => [
      'name' => 'The Ember',
      'metric' => 'edits in the last 30 days',
      'blurb' => 'Keeping the fire going right now.',
      'tiers' => [5, 20, 50],
    ],
  ];

  /**
   * Role badge map: role id => [label, icon, badge variant].
   */
  const ROLE_BADGES = [
    'superadmin' => ['Custodian of the Record', 'crown', 'heritage-red'],
    'administrator' => ['Custodian of the Record', 'crown', 'heritage-red'],
    'editor' => ['Editor', 'quill', 'slate-blue'],
    'moderator' => ['Moderator', 'shield', 'slate-blue'],
    'researcher' => ['Researcher', 'magnifier', 'muted-gold'],
    'researcher_advanced_' => ['Advanced researcher', 'magnifier', 'muted-gold'],
  ];

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
   * @param \Drupal\Core\Session\AccountInterface $viewer
   *   The user looking at the page (task tiles gate on their permissions).
   * @param bool $owner
   *   TRUE when the viewer is looking at their own profile.
   *
   * @return array|null
   *   Render-ready data, or NULL when the user has no content activity.
   */
  public function build(UserInterface $account, AccountInterface $viewer, bool $owner) {
    $uid = (int) $account->id();
    $stats = $this->getStats($uid);
    if ($stats['worked_on'] === 0) {
      return NULL;
    }

    return [
      'stats' => $stats,
      'roles' => $this->getRoleBadges($account),
      'achievements' => $this->getAchievements($uid, $stats['worked_on']),
      'tasks' => $this->getTasks($uid, $viewer, $owner),
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
   * Maps the account's roles to badge pills with icons.
   */
  protected function getRoleBadges(UserInterface $account): array {
    $badges = [];
    foreach ($account->getRoles(TRUE) as $role_id) {
      if (isset(self::ROLE_BADGES[$role_id])) {
        [$label, $icon, $variant] = self::ROLE_BADGES[$role_id];
        // Administrator and superadmin share one Custodian badge.
        $badges[$label] = ['label' => $label, 'icon' => $icon, 'variant' => $variant];
      }
    }
    return array_values($badges);
  }

  /**
   * Computes the achievement medallions from existing revision data.
   */
  protected function getAchievements(int $uid, int $worked_on): array {
    $now = $this->time->getRequestTime();

    $types = (int) $this->database->query(
      'SELECT COUNT(DISTINCT n.type) FROM {node_revision} r
       INNER JOIN {node_field_data} n ON n.nid = r.nid AND n.default_langcode = 1
       WHERE r.revision_uid = :uid',
      [':uid' => $uid]
    )->fetchField();

    // A "stale rescue" is an own revision whose predecessor on the same node
    // is at least the review age older - the page had gone dark and this
    // user relit it. LAG() needs every node's revision chain, so the window
    // runs over the whole table; acceptable at this site's scale behind the
    // render cache.
    $lantern = (int) $this->database->query(
      'SELECT COUNT(*) FROM (
         SELECT revision_uid, revision_timestamp - LAG(revision_timestamp)
           OVER (PARTITION BY nid ORDER BY vid) AS gap
         FROM {node_revision}
       ) t WHERE t.revision_uid = :uid AND t.gap > :age',
      [':uid' => $uid, ':age' => self::REVIEW_AGE_SECONDS]
    )->fetchField();

    $first = (int) $this->database->query(
      'SELECT MIN(revision_timestamp) FROM {node_revision} WHERE revision_uid = :uid',
      [':uid' => $uid]
    )->fetchField();
    $years = $first ? (int) floor(($now - $first) / 31557600) : 0;

    $ember = (int) $this->database->query(
      'SELECT COUNT(*) FROM {node_revision} WHERE revision_uid = :uid AND revision_timestamp > :since',
      [':uid' => $uid, ':since' => $now - 2592000]
    )->fetchField();

    $values = [
      'quill' => $worked_on,
      'compass' => $types,
      'lantern' => $lantern,
      'laurel' => $years,
      'ember' => $ember,
    ];

    $achievements = [];
    foreach (self::ACHIEVEMENTS as $id => $def) {
      $value = $values[$id];
      $tier = NULL;
      $next = NULL;
      foreach (array_combine(['bronze', 'silver', 'gold'], $def['tiers']) as $name => $threshold) {
        if ($value >= $threshold) {
          $tier = $name;
        }
        elseif ($next === NULL) {
          $next = $threshold;
        }
      }
      $achievements[] = [
        'id' => $id,
        'icon' => $id,
        'name' => $def['name'],
        'blurb' => $def['blurb'],
        'metric' => $def['metric'],
        'value' => number_format($value),
        'tier' => $tier,
        'next' => $next ? number_format($next) : NULL,
        'progress' => $next ? min(100, (int) round($value / max($next, 1) * 100)) : 100,
      ];
    }
    return $achievements;
  }

  /**
   * Builds the permission-gated "keep the record healthy" task tiles.
   */
  protected function getTasks(int $uid, AccountInterface $viewer, bool $owner): array {
    $tiles = [];
    $schema = $this->database->schema();

    if ($viewer->hasPermission('administer comments') && $schema->tableExists('comment_field_data')) {
      $count = (int) $this->database->query(
        'SELECT COUNT(*) FROM {comment_field_data} WHERE status = 0 AND default_langcode = 1'
      )->fetchField();
      $tiles[] = $this->tile('comments', 'comment', 'Comments to approve', $count,
        '/admin/content/comment/approval',
        'Readers wrote in - each one waits for a simple yes or no.');
    }

    if ($viewer->hasPermission('administer redirects') && $schema->tableExists('redirect_404')) {
      $count = (int) $this->database->query(
        'SELECT COUNT(*) FROM {redirect_404} WHERE resolved = 0'
      )->fetchField();
      $tiles[] = $this->tile('redirects', 'link-broken', '404s to tame', $count,
        '/admin/config/search/redirect/404',
        'Dead URLs visitors keep hitting - one redirect each fixes them for good.');
    }

    if ($viewer->hasPermission('access broken links report') && $schema->tableExists('linkchecker_link')) {
      $count = (int) $this->database->query(
        'SELECT COUNT(*) FROM {linkchecker_link} WHERE fail_count > 0 AND code >= 400'
      )->fetchField();
      $tiles[] = $this->tile('brokenlinks', 'link-broken', 'Broken links in content', $count,
        '/admin/reports/linkchecker',
        'Links in our pages that answer with hard errors.');
    }

    if ($viewer->hasPermission('view any webform submission') && $schema->tableExists('webform_submission')) {
      $count = (int) $this->database->query(
        "SELECT COUNT(*) FROM {webform_submission} WHERE webform_id = 'contribute'"
      )->fetchField();
      $tiles[] = $this->tile('contribute', 'draft', 'Public contributions', $count,
        '/admin/structure/webform/manage/contribute/results/submissions',
        'Stories and corrections sent in by readers, waiting for an editorial eye.');
    }

    if ($viewer->hasPermission('access content overview')) {
      $count = (int) $this->database->query(
        "SELECT COUNT(DISTINCT entity_id) FROM {node__body} WHERE body_value LIKE '%.htm%' AND deleted = 0"
      )->fetchField();
      $tile = $this->tile('legacy', 'lantern', 'Pages with legacy links', $count, NULL,
        'Body text still pointing at the old site\'s .htm addresses - open one and repair it.');
      $nids = $this->database->queryRange(
        "SELECT DISTINCT entity_id FROM {node__body} WHERE body_value LIKE '%.htm%' AND deleted = 0",
        0, 5
      )->fetchCol();
      $tile['samples'] = $this->loadItems($nids);
      $tiles[] = $tile;
    }

    if ($owner) {
      $count = (int) $this->database->select('node_field_data', 'n')
        ->condition('n.uid', $uid)
        ->condition('n.status', 0)
        ->condition('n.default_langcode', 1)
        ->countQuery()
        ->execute()
        ->fetchField();
      if ($count > 0) {
        $url = $viewer->hasPermission('access content overview') ? '/admin/content?status=2' : NULL;
        $tiles[] = $this->tile('drafts', 'draft', 'Your unpublished drafts', $count, $url,
          'Pieces of yours the public cannot see yet.');
      }
    }

    return $tiles;
  }

  /**
   * Assembles one task tile row.
   */
  protected function tile(string $id, string $icon, string $title, int $count, ?string $path, string $blurb): array {
    return [
      'id' => $id,
      'icon' => $icon,
      'title' => $title,
      'count' => number_format($count),
      'all_clear' => $count === 0,
      'url' => $path ? Url::fromUserInput($path)->toString() : NULL,
      'blurb' => $blurb,
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
