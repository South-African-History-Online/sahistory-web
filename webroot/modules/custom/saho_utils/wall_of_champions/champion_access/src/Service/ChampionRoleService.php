<?php

namespace Drupal\champion_access\Service;

use Drupal\Core\Database\Connection;
use Drupal\user\UserInterface;

/**
 * Syncs the 'champion' Drupal role based on active Commerce subscriptions.
 */
class ChampionRoleService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructs a ChampionRoleService.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Syncs the champion role for the given user based on subscription status.
   *
   * Adds the 'champion' role if the user has an active champion_membership
   * subscription, and removes it if they do not.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account to sync.
   */
  public function syncRole(UserInterface $user): void {
    $has_active = $this->hasActiveChampionSubscription($user->id());
    $has_role = $user->hasRole('champion');

    if ($has_active && !$has_role) {
      $user->addRole('champion');
      $user->save();
    }
    elseif (!$has_active && $has_role) {
      $user->removeRole('champion');
      $user->save();
    }
  }

  /**
   * Checks whether a user has an active champion_membership subscription.
   *
   * @param int $uid
   *   The user ID to check.
   *
   * @return bool
   *   TRUE if the user has an active champion subscription.
   */
  public function hasActiveChampionSubscription(int $uid): bool {
    // Guard: if the commerce_subscription table doesn't exist (e.g. main site),
    // return FALSE rather than throwing a database error.
    if (!$this->database->schema()->tableExists('commerce_subscription')) {
      return FALSE;
    }

    try {
      $query = $this->database->select('commerce_subscription', 'cs');
      $query->join('commerce_product_variation', 'cpv', 'cs.purchased_entity__target_id = cpv.variation_id');
      $count = $query
        ->condition('cs.uid', $uid)
        ->condition('cs.state', 'active')
        ->condition('cpv.type', 'champion_membership')
        ->countQuery()
        ->execute()
        ->fetchField();

      return (int) $count > 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

}
