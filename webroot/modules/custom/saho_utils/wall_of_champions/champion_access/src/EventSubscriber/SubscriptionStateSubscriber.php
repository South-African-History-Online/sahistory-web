<?php

namespace Drupal\champion_access\EventSubscriber;

use Drupal\champion_access\Service\ChampionRoleService;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to Commerce Recurring subscription updates to sync the champion role.
 *
 * Uses the string event name directly to avoid a hard dependency on the
 * RecurringEvents class constants, allowing the module to be enabled on sites
 * where commerce_recurring may not be active.
 */
class SubscriptionStateSubscriber implements EventSubscriberInterface {

  /**
   * The champion role service.
   *
   * @var \Drupal\champion_access\Service\ChampionRoleService
   */
  protected ChampionRoleService $roleService;

  /**
   * Constructs a SubscriptionStateSubscriber.
   *
   * @param \Drupal\champion_access\Service\ChampionRoleService $role_service
   *   The champion role service.
   */
  public function __construct(ChampionRoleService $role_service) {
    $this->roleService = $role_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // Use string event names (not class constants) to avoid hard coupling.
      'commerce_recurring.commerce_subscription.insert' => 'onSubscriptionChange',
      'commerce_recurring.commerce_subscription.update' => 'onSubscriptionChange',
    ];
  }

  /**
   * Syncs the champion role when a subscription is inserted or updated.
   *
   * @param object $event
   *   The subscription event (EntityEvent from commerce_recurring).
   */
  public function onSubscriptionChange(object $event): void {
    // getEntity() is available on all Commerce entity events.
    if (!method_exists($event, 'getEntity')) {
      return;
    }

    $subscription = $event->getEntity();

    // Only act on champion_membership subscriptions.
    if (!method_exists($subscription, 'getPurchasedEntity')) {
      return;
    }

    $variation = $subscription->getPurchasedEntity();
    if (!$variation || $variation->bundle() !== 'champion_membership') {
      return;
    }

    if (!method_exists($subscription, 'getCustomer')) {
      return;
    }

    $customer = $subscription->getCustomer();
    if (!$customer) {
      return;
    }

    // Load a fresh copy of the user to avoid stale role data.
    $user = User::load($customer->id());
    if ($user) {
      $this->roleService->syncRole($user);
    }
  }

}
