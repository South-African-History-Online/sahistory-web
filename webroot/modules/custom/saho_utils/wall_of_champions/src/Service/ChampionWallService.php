<?php

namespace Drupal\wall_of_champions\Service;

use Drupal\Core\Database\Connection;

/**
 * Builds the public Wall of Champions listing.
 *
 * Source of truth is the 'champion' Drupal role (granted from paid
 * champion orders by champion_access, or by hand for honorary champions)
 * plus the user's explicit opt-in. Before PR #350 the wall queried
 * commerce_subscription, which no longer exists for champion membership.
 *
 * "Champion since" is best-effort: the earliest placed champion order for
 * the user, or NULL for honorary champions who have no order at all - the
 * templates omit the date line when it is NULL.
 */
class ChampionWallService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructs a ChampionWallService.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Returns opted-in champions for public display.
   *
   * @param int|null $limit
   *   Optional maximum number of champions to return.
   * @param string $sort
   *   Sort order: 'alphabetical', 'random', or 'recent' (default - any
   *   other value, including the legacy 'recent_subscribers', is treated
   *   as 'recent').
   *
   * @return array
   *   A list of champions, each an array with keys: uid, display_name,
   *   testimonial, member_since (timestamp or NULL).
   */
  public function getChampions(?int $limit = NULL, string $sort = 'recent'): array {
    // First and last name fields are optional - only join them if present.
    $has_name_fields = $this->database->schema()->tableExists('user__field_first_name')
      && $this->database->schema()->tableExists('user__field_last_name');

    $query = $this->database->select('users_field_data', 'u');
    $query->innerJoin('user__roles', 'r', 'r.entity_id = u.uid AND r.roles_target_id = :role', [':role' => 'champion']);
    $query->innerJoin('user__field_champion_wall_opt_in', 'opt', 'opt.entity_id = u.uid');
    if ($has_name_fields) {
      $query->leftJoin('user__field_first_name', 'fn', 'fn.entity_id = u.uid');
      $query->leftJoin('user__field_last_name', 'ln', 'ln.entity_id = u.uid');
    }
    $query->leftJoin('user__field_champion_testimonial', 'test', 'test.entity_id = u.uid');

    $query->fields('u', ['uid', 'name', 'created']);
    if ($has_name_fields) {
      $query->addField('fn', 'field_first_name_value', 'first_name');
      $query->addField('ln', 'field_last_name_value', 'last_name');
    }
    $query->addField('test', 'field_champion_testimonial_value', 'testimonial');

    $query->condition('opt.field_champion_wall_opt_in_value', 1);
    $query->condition('u.status', 1);
    $query->distinct();

    // Alphabetical can be ordered and limited in SQL. 'recent' depends on
    // the member_since map (a second query) so it is sorted in PHP below.
    if ($sort === 'alphabetical') {
      if ($has_name_fields) {
        $query->orderBy('fn.field_first_name_value');
        $query->orderBy('ln.field_last_name_value');
      }
      else {
        $query->orderBy('u.name');
      }
      if ($limit) {
        $query->range(0, $limit);
      }
    }

    $rows = $query->execute()->fetchAll();
    $member_since = $this->getMemberSinceMap(array_column($rows, 'uid'));

    $champions = [];
    foreach ($rows as $row) {
      $display_name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
      if ($display_name === '') {
        // Never expose the internal Drupal account name publicly.
        $display_name = 'SAHO Champion';
      }

      $testimonial = '';
      if (!empty($row->testimonial)) {
        $testimonial = strip_tags($row->testimonial);
        if (mb_strlen($testimonial) > 250) {
          $testimonial = mb_substr($testimonial, 0, 250) . '...';
        }
      }

      $champions[] = [
        'uid' => $row->uid,
        'display_name' => $display_name,
        'testimonial' => $testimonial,
        'member_since' => $member_since[$row->uid] ?? NULL,
      ];
    }

    if ($sort === 'random') {
      shuffle($champions);
    }
    elseif ($sort !== 'alphabetical') {
      // 'recent' (default): newest champion-since first, honorary
      // champions with no order date last.
      usort($champions, fn($a, $b) => ($b['member_since'] ?? 0) <=> ($a['member_since'] ?? 0));
    }

    // Alphabetical was already limited in SQL.
    if ($limit && $sort !== 'alphabetical') {
      $champions = array_slice($champions, 0, $limit);
    }

    return $champions;
  }

  /**
   * Counts opted-in champions.
   *
   * @return int
   *   The number of active, opted-in champions.
   */
  public function getTotalCount(): int {
    $query = $this->database->select('users_field_data', 'u');
    $query->innerJoin('user__roles', 'r', 'r.entity_id = u.uid AND r.roles_target_id = :role', [':role' => 'champion']);
    $query->innerJoin('user__field_champion_wall_opt_in', 'opt', 'opt.entity_id = u.uid');
    $query->condition('opt.field_champion_wall_opt_in_value', 1);
    $query->condition('u.status', 1);
    $query->addExpression('COUNT(DISTINCT u.uid)', 'total');

    return (int) $query->execute()->fetchField();
  }

  /**
   * Maps champion uids to their earliest placed champion-order timestamp.
   *
   * Defensive: returns an empty map if commerce_order is unavailable, so
   * the wall still renders - champions just show no "Champion since" date.
   *
   * @param array $uids
   *   The champion user IDs to look up.
   *
   * @return array
   *   A map of uid => earliest order placed timestamp.
   */
  protected function getMemberSinceMap(array $uids): array {
    if (empty($uids) || !$this->database->schema()->tableExists('commerce_order')) {
      return [];
    }

    try {
      $query = $this->database->select('commerce_order', 'o');
      $query->innerJoin('commerce_order_item', 'oi', 'oi.order_id = o.order_id');
      $query->innerJoin('commerce_product_variation_field_data', 'v', 'v.variation_id = oi.purchased_entity');
      $query->addField('o', 'uid');
      $query->addExpression('MIN(o.placed)', 'member_since');
      $query->condition('o.uid', $uids, 'IN');
      $query->isNotNull('o.placed');
      $query->condition('v.sku', 'CHAMPION-%', 'LIKE');
      $query->groupBy('o.uid');

      return $query->execute()->fetchAllKeyed();
    }
    catch (\Exception $e) {
      return [];
    }
  }

}
