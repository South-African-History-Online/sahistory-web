<?php

namespace Drupal\champion_access\Form;

use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Customer-facing confirmation form for canceling a champion membership.
 *
 * Route: /user/{user}/subscriptions/{commerce_subscription}/cancel.
 *
 * Access is granted to the subscription owner or any user with the
 * 'administer commerce_subscription' permission (i.e. shop_manager role).
 * Subscriptions not in a cancelable state return 403 before the form renders.
 *
 * On submit, a state machine transition fires 'cancel' on the subscription.
 * This triggers SubscriptionStateSubscriber, which calls the PayFast API to
 * deactivate the billing token and then removes the champion Drupal role.
 */
class SubscriptionCancelForm extends ConfirmFormBase {

  /**
   * The subscription being canceled.
   *
   * @var \Drupal\commerce_recurring\Entity\SubscriptionInterface
   */
  protected SubscriptionInterface $subscription;

  /**
   * The subscription owner whose profile is in the route.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $user;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'champion_access_subscription_cancel';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): string {
    return (string) $this->t('Cancel your Champion Membership?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    if ($this->isOnWall()) {
      return (string) $this->t(
        'Your Champion access will be canceled. You will also be removed from the Wall of Champions - your display name, photo, and testimonial are saved and will be restored if you rejoin.'
      );
    }
    return (string) $this->t(
      'Your Champion access will be canceled. You will lose access to Champion-only content.'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): string {
    return (string) $this->t('Yes, cancel my membership');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): string {
    return (string) $this->t('Keep my membership');
  }

  /**
   * {@inheritdoc}
   *
   * Returns to the subscription detail page.
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('entity.commerce_subscription.customer_view', [
      'user' => $this->user->id(),
      'commerce_subscription' => $this->subscription->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?UserInterface $user = NULL,
    ?SubscriptionInterface $commerce_subscription = NULL,
  ): array {
    $this->user = $user;
    $this->subscription = $commerce_subscription;
    $form = parent::buildForm($form, $form_state);

    // Style the confirm button as a danger action.
    $form['actions']['submit']['#attributes']['class'][] = 'button--danger';

    return $form;
  }

  /**
   * Access callback for the cancel route.
   *
   * Grants access when the current user owns the subscription OR has the
   * 'administer commerce_subscription' permission (shop_manager role).
   *
   * Also denies access if the subscription is not in a cancelable state
   * (e.g. already canceled, expired) - checked before ownership to avoid
   * leaking subscription existence to unauthorised users.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged-in account.
   * @param \Drupal\user\UserInterface $user
   *   The {user} route parameter.
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $commerce_subscription
   *   The {commerce_subscription} route parameter.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(
    AccountInterface $account,
    UserInterface $user,
    SubscriptionInterface $commerce_subscription,
  ): AccessResultInterface {
    // Block access if the subscription cannot be canceled from its current
    // state. applyTransitionById() would throw - better to 403 cleanly.
    $transitions = $commerce_subscription->get('state')->first()->getTransitions();
    if (!isset($transitions['cancel'])) {
      return AccessResult::forbidden('Subscription is not in a cancelable state.')
        ->addCacheableDependency($commerce_subscription);
    }

    $is_owner = (int) $account->id() === (int) $user->id()
      && (int) $account->id() === (int) $commerce_subscription->getCustomerId();

    $is_manager = $account->hasPermission('administer commerce_subscription');

    return AccessResult::allowedIf($is_owner || $is_manager)
      ->addCacheContexts(['user'])
      ->addCacheableDependency($commerce_subscription);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Apply the cancel transition via the state machine rather than setting
    // the state field directly. This fires commerce_recurring entity events,
    // which trigger SubscriptionStateSubscriber → PayFast cancel + role sync.
    try {
      $this->subscription->get('state')->first()->applyTransitionById('cancel');
      $this->subscription->save();
      $this->messenger()->addStatus(
        $this->t('Your Champion membership has been canceled.')
      );
    }
    catch (\InvalidArgumentException $e) {
      // The subscription state changed between access check and submission
      // (e.g. dunning expired it). Inform the user and redirect gracefully.
      $this->messenger()->addError(
        $this->t('Your subscription could not be canceled at this time. It may have already ended. Please contact us if you need help.')
      );
    }

    $form_state->setRedirectUrl(
      Url::fromRoute('view.commerce_user_subscriptions.page_1', [
        'user' => $this->user->id(),
      ])
    );
  }

  /**
   * Returns TRUE if the subscription owner is opted into the Wall of Champions.
   *
   * @return bool
   *   TRUE if the user's name is currently displayed on the wall.
   */
  protected function isOnWall(): bool {
    return $this->user->hasField('field_champion_wall_opt_in')
      && (bool) $this->user->get('field_champion_wall_opt_in')->value;
  }

}
