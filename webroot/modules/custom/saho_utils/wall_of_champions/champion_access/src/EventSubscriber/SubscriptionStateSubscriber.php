<?php

namespace Drupal\champion_access\EventSubscriber;

use Drupal\champion_access\Service\ChampionRoleService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\saho_payfast\Service\PayfastApiService;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to Commerce Recurring subscription updates to sync the champion role.
 *
 * Uses the string event name directly to avoid a hard dependency on the
 * RecurringEvents class constants, allowing the module to be enabled on sites
 * where commerce_recurring may not be active.
 *
 * When a champion_membership subscription transitions to 'canceled', this
 * subscriber also calls the PayFast API to cancel the recurring billing token
 * at the gateway. saho_payfast is a hard dependency of this module, but the
 * service is still injected as nullable so cancellation degrades gracefully
 * if settings are misconfigured rather than throwing an uncaught exception.
 */
class SubscriptionStateSubscriber implements EventSubscriberInterface {

  /**
   * The champion role service.
   *
   * @var \Drupal\champion_access\Service\ChampionRoleService
   */
  protected ChampionRoleService $roleService;

  /**
   * The PayFast API service.
   *
   * @var \Drupal\saho_payfast\Service\PayfastApiService|null
   */
  protected ?PayfastApiService $payfastApi;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructs a SubscriptionStateSubscriber.
   *
   * @param \Drupal\champion_access\Service\ChampionRoleService $role_service
   *   The champion role service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\saho_payfast\Service\PayfastApiService|null $payfast_api
   *   The PayFast API service, or NULL if saho_payfast is not enabled.
   */
  public function __construct(
    ChampionRoleService $role_service,
    LoggerChannelFactoryInterface $logger_factory,
    ?PayfastApiService $payfast_api = NULL,
  ) {
    $this->roleService = $role_service;
    $this->logger = $logger_factory->get('champion_access');
    $this->payfastApi = $payfast_api;
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
   * When the subscription state is 'canceled', first calls the PayFast API
   * to cancel the recurring billing token at the gateway, then syncs the
   * Drupal role so the user loses champion access.
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

    // When a subscription is canceled, notify PayFast before syncing the role.
    // This ensures the billing token is deactivated at the gateway regardless
    // of who initiated the cancellation (customer UI, admin UI, or dunning).
    if ($subscription->get('state')->value === 'canceled') {
      $this->cancelAtGateway($subscription);
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

  /**
   * Calls the PayFast API to cancel the subscription's billing token.
   *
   * Silently skips if:
   * - payfastApi is NULL (misconfigured; saho_payfast is a declared dep).
   * - The subscription has no associated payment method entity.
   * - The payment method has no remote token (test/manual subscriptions).
   *
   * Logs a warning if the API call fails so operators can correlate the
   * subscription entity ID with a failed gateway cancel in watchdog.
   *
   * @param object $subscription
   *   The commerce_subscription entity being canceled.
   */
  protected function cancelAtGateway(object $subscription): void {
    if ($this->payfastApi === NULL) {
      return;
    }

    $payment_method_field = $subscription->get('payment_method');
    if ($payment_method_field->isEmpty()) {
      return;
    }

    $payment_method = $payment_method_field->entity;
    if ($payment_method === NULL) {
      return;
    }

    // remote_id holds the PayFast subscription token set during ITN processing.
    $token = $payment_method->getRemoteId();
    if (empty($token)) {
      return;
    }

    $success = $this->payfastApi->cancelSubscription($token);

    if (!$success) {
      $this->logger->warning(
        'PayFast gateway cancel failed for subscription @id (token @token). The Drupal subscription state has been updated but the recurring billing token may still be active at PayFast.',
        ['@id' => $subscription->id(), '@token' => $token]
      );
    }
  }

}
