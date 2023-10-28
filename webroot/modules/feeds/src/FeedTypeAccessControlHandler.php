<?php

namespace Drupal\feeds;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the feeds_feed_type entity.
 *
 * @see \Drupal\feeds\Entity\FeedType
 *
 * @todo Provide more granular permissions.
 */
class FeedTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        $has_perm = $account->hasPermission('administer feeds') || $account->hasPermission("view {$entity->id()} feeds");
        return AccessResult::allowedIf($has_perm);

        break;

      case 'delete':
        if ($entity->isNew()) {
          return AccessResult::forbidden()->addCacheableDependency($entity);
        }
        // The delete operation is not cacheable since the locked status can
        // change in the background.
        return AccessResult::allowedIf($account->hasPermission('administer feeds') && !$entity->isLocked())->addCacheableDependency(FALSE);

        break;

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
