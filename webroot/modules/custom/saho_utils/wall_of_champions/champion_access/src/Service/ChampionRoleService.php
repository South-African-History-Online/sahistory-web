<?php

namespace Drupal\champion_access\Service;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;

/**
 * Grants the 'champion' Drupal role based on champion product purchases.
 *
 * Since PR #350 champion membership is a one-time payment, not a
 * commerce_subscription. "Who is a champion" is now the 'champion' role
 * itself: granted when a paid order contains a CHAMPION-* product, and
 * grantable by hand for honorary champions. The role is additive only -
 * it is never auto-removed, because a one-time payment has no expiry and
 * honorary champions have no order at all. To revoke, an admin removes
 * the role manually.
 *
 * Multisite-safe: champion_access is enabled on the main site too, where
 * commerce is not installed. The commerce-touching methods guard on the
 * commerce_order entity type definition and no-op when it is absent.
 */
class ChampionRoleService {

  /**
   * SKU prefix shared by every champion membership product variation.
   */
  protected const CHAMPION_SKU_PREFIX = 'CHAMPION-';

  /**
   * The 'champion' role ID.
   */
  protected const CHAMPION_ROLE = 'champion';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a ChampionRoleService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Grants the 'champion' role to a user if they do not already have it.
   *
   * Additive and idempotent - never removes the role.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account to grant the role to.
   *
   * @return bool
   *   TRUE if the role was added, FALSE if the user already had it.
   */
  public function grantChampionRole(UserInterface $user): bool {
    if ($user->hasRole(self::CHAMPION_ROLE)) {
      return FALSE;
    }
    $user->addRole(self::CHAMPION_ROLE);
    $user->save();
    return TRUE;
  }

  /**
   * Checks whether an order contains a champion membership product.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order to inspect.
   *
   * @return bool
   *   TRUE if any order item is a CHAMPION-* product variation.
   */
  public function orderHasChampionProduct(OrderInterface $order): bool {
    foreach ($order->getItems() as $item) {
      $purchased = $item->getPurchasedEntity();
      if ($purchased
        && method_exists($purchased, 'getSku')
        && str_starts_with((string) $purchased->getSku(), self::CHAMPION_SKU_PREFIX)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Ensures a user has the champion role if they have a paid champion order.
   *
   * A login-time backfill and safety net for purchases where the
   * order-paid event was missed (or that predate this code). Additive
   * only. Multisite-safe: no-ops on sites without commerce_order.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account to check.
   */
  public function ensureChampionRole(UserInterface $user): void {
    // Already a champion (paid or honorary) - nothing to do.
    if ($user->hasRole(self::CHAMPION_ROLE)) {
      return;
    }
    // commerce_order only exists on the shop site.
    if (!$this->entityTypeManager->hasDefinition('commerce_order')) {
      return;
    }

    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $order_ids = $order_storage->getQuery()
      ->condition('uid', $user->id())
      ->condition('state', 'draft', '<>')
      ->accessCheck(FALSE)
      ->execute();
    if (!$order_ids) {
      return;
    }

    foreach ($order_storage->loadMultiple($order_ids) as $order) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      if ($order->isPaid() && $this->orderHasChampionProduct($order)) {
        $this->grantChampionRole($user);
        return;
      }
    }
  }

}
