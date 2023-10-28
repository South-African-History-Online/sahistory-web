<?php

namespace Drupal\uc_order;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for Ubercart orders.
 */
class OrderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $order, $operation, AccountInterface $account) {
    /** @var \Drupal\uc_order\OrderInterface $order */

    switch ($operation) {
      case 'view':
      case 'invoice':
        // Admins can view all orders.
        if ($account->hasPermission('view all orders')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        // Non-anonymous users can view their own orders and invoices with permission.
        $permission = $operation == 'view' ? 'view own orders' : 'view own invoices';
        if ($account->id() && $account->id() == $order->getOwnerId() && $account->hasPermission($permission)) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->cacheUntilEntityChanges($order);
        }
        return AccessResult::forbidden()->cachePerPermissions()->cachePerUser()->cacheUntilEntityChanges($order);

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit orders')->cachePerPermissions()->cachePerUser();

      case 'delete':
        if ($account->hasPermission('unconditionally delete orders')) {
          // Unconditional deletion perms are always TRUE.
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
        }
        if ($account->hasPermission('delete orders')) {
          // Only users with unconditional deletion perms can delete completed orders.
          if ($order->getStateId() == 'completed') {
            return AccessResult::forbidden()->cachePerPermissions()->cachePerUser()->cacheUntilEntityChanges($order);
          }
          else {
            // See if any modules have a say in this order's eligibility for deletion.
            $module_handler = \Drupal::moduleHandler();
            foreach ($module_handler->getImplementations('uc_order_can_delete') as $module) {
              $function = $module . '_uc_order_can_delete';
              if ($function($order) === FALSE) {
                return AccessResult::forbidden()->cachePerPermissions()->cachePerUser()->cacheUntilEntityChanges($order);
              }
            }

            return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->cacheUntilEntityChanges($order);
          }
        }
        return AccessResult::forbidden()->cachePerPermissions()->cachePerUser()->cacheUntilEntityChanges($order);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create orders')->cachePerPermissions();
  }

}
