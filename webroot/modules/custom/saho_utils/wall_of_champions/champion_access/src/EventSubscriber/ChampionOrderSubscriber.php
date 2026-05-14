<?php

namespace Drupal\champion_access\EventSubscriber;

use Drupal\champion_access\Service\ChampionRoleService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Grants the champion role when a paid order contains a champion product.
 *
 * Replaces the old commerce_recurring SubscriptionStateSubscriber: since
 * PR #350 champion membership is a one-time payment, so the signal is a
 * paid order, not a subscription state change.
 *
 * Uses the literal 'commerce_order.order.paid' event name (not the
 * OrderEvents::ORDER_PAID constant) so the class carries no hard
 * dependency on commerce_order - champion_access is also enabled on the
 * main site, where commerce is not installed and the event never fires.
 *
 * This is the primary grant path; ChampionRoleService::ensureChampionRole()
 * (called on login) and the module's backfill post-update are the
 * safety nets for purchases this event missed.
 */
class ChampionOrderSubscriber implements EventSubscriberInterface {

  /**
   * The champion role service.
   *
   * @var \Drupal\champion_access\Service\ChampionRoleService
   */
  protected ChampionRoleService $roleService;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructs a ChampionOrderSubscriber.
   *
   * @param \Drupal\champion_access\Service\ChampionRoleService $role_service
   *   The champion role service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    ChampionRoleService $role_service,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->roleService = $role_service;
    $this->logger = $logger_factory->get('champion_access');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Literal event name (not OrderEvents::ORDER_PAID) - see class docblock.
    return [
      'commerce_order.order.paid' => 'onOrderPaid',
    ];
  }

  /**
   * Grants the champion role when a champion order is fully paid.
   *
   * @param object $event
   *   The order paid event (OrderEvent from commerce_order).
   */
  public function onOrderPaid(object $event): void {
    if (!method_exists($event, 'getOrder')) {
      return;
    }
    $order = $event->getOrder();

    // The method_exists guard keeps the type-hinted call below safe even
    // if some other event ever reuses this name with a non-order payload.
    if (!method_exists($order, 'getItems')
      || !$this->roleService->orderHasChampionProduct($order)) {
      return;
    }

    $customer = $order->getCustomer();
    if (!$customer || $customer->isAnonymous()) {
      return;
    }

    if ($this->roleService->grantChampionRole($customer)) {
      $this->logger->info(
        'Granted the champion role to user @uid after paid champion order @order.',
        ['@uid' => $customer->id(), '@order' => $order->id()]
      );
    }
  }

}
