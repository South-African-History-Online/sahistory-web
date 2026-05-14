<?php

/**
 * @file
 * Post-update hooks for the champion_access module.
 */

/**
 * Backfill the champion role for existing paid champion orders.
 *
 * Before PR #350 the champion role was synced from commerce_subscription
 * state; that path is dead now that champion membership is a one-time
 * payment. This grants the role to every customer who already has a paid
 * order containing a CHAMPION-* product, so existing champions are not
 * lost in the switch to the role-based model.
 *
 * Idempotent: ChampionRoleService::grantChampionRole() skips users who
 * already have the role, so the hook is safe to re-run.
 *
 * Multisite-safe: champion_access is enabled on the main site too, where
 * commerce_order does not exist - the hook detects that and no-ops
 * instead of throwing PluginNotFoundException.
 */
function champion_access_post_update_backfill_champion_role(?array &$sandbox = NULL): string {
  $entity_type_manager = \Drupal::entityTypeManager();
  if (!$entity_type_manager->hasDefinition('commerce_order')) {
    return 'Skipped: commerce_order is not available on this site (expected on every site in the multisite except the shop).';
  }

  /** @var \Drupal\champion_access\Service\ChampionRoleService $role_service */
  $role_service = \Drupal::service('champion_access.role_service');
  $order_storage = $entity_type_manager->getStorage('commerce_order');
  $user_storage = $entity_type_manager->getStorage('user');

  // All placed (non-cart) orders belonging to a real account.
  $order_ids = $order_storage->getQuery()
    ->condition('state', 'draft', '<>')
    ->condition('uid', 0, '>')
    ->accessCheck(FALSE)
    ->execute();

  $granted = 0;
  $handled_uids = [];
  foreach ($order_storage->loadMultiple($order_ids) as $order) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $uid = $order->getCustomerId();
    if (isset($handled_uids[$uid])
      || !$order->isPaid()
      || !$role_service->orderHasChampionProduct($order)) {
      continue;
    }
    $handled_uids[$uid] = TRUE;
    $user = $user_storage->load($uid);
    if ($user && $role_service->grantChampionRole($user)) {
      $granted++;
    }
  }

  return sprintf('Backfilled the champion role: %d user(s) granted.', $granted);
}
