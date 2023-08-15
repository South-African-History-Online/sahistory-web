<?php

namespace Drupal\uc_cart;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;
use Drupal\uc_order\OrderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provides the cart manager service.
 */
class CartManager implements CartManagerInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(AccountProxyInterface $current_user, SessionInterface $session) {
    $this->currentUser = $current_user;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function get($id = NULL) {
    $id = $id ?: $this->getId();

    return new Cart($id);
  }

  /**
   * {@inheritdoc}
   */
  public function emptyCart($id = NULL) {
    $this->get($id)->emptyCart();
  }

  /**
   * {@inheritdoc}
   */
  protected function getId($create = TRUE) {
    if ($this->currentUser->isAuthenticated()) {
      return $this->currentUser->id();
    }
    elseif (!$this->session->has('uc_cart_id') && $create) {
      $this->session->set('uc_cart_id', md5(uniqid(rand(), TRUE)));
    }

    return $this->session->has('uc_cart_id') ? $this->session->get('uc_cart_id') : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function completeSale(OrderInterface $order, $login = TRUE) {
    // Empty that cart...
    $this->emptyCart();

    // Ensure that user creation and triggers are only run once.
    if (empty($order->data->complete_sale)) {
      $this->completeSaleAccount($order);

      // Move an order's status from "In checkout" to "Pending".
      if ($order->getStateId() == 'in_checkout') {
        $order->setStatusId(uc_order_state_default('post_checkout'));
      }

      $order->save();

      // Invoke the checkout complete trigger and hook.
      $account = $order->getOwner();
      \Drupal::moduleHandler()->invokeAll('uc_checkout_complete', array($order, $account));
      // rules_invoke_event('uc_checkout_complete', $order);
    }

    $type = $order->data->complete_sale;

    // Log in new users, if requested.
    if ($type == 'new_user' && $login && $this->currentUser->isAnonymous()) {
      if (\Drupal::config('uc_cart.settings')->get('new_customer_login')) {
        $type = 'new_user_logged_in';
        user_login_finalize($order->getOwner());
      }
    }

    $message = \Drupal::config('uc_cart.messages')->get($type);
    $message = \Drupal::token()->replace($message, array('uc_order' => $order));

    $variables['!new_username'] = isset($order->data->new_user_name) ? $order->data->new_user_name : '';
    $variables['!new_password'] = isset($order->password) ? $order->password : t('Your password');
    $message = strtr($message, $variables);

    return array(
      '#theme' => 'uc_cart_complete_sale',
      '#message' => array('#markup' => $message),
      '#order' => $order,
    );
  }

  /**
   * Link a completed sale to a user.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order entity that has just been completed.
   */
  protected function completeSaleAccount(OrderInterface $order) {
    // Order already has a user ID, so the user was logged in during checkout.
    if ($order->getOwnerId()) {
      $order->data->complete_sale = 'logged_in';
      return;
    }

    // Email address matches an existing account.
    if ($account = user_load_by_mail($order->getEmail())) {
      $order->setOwner($account);
      $order->data->complete_sale = 'existing_user';
      return;
    }

    // Set up a new user.
    $cart_config = \Drupal::config('uc_cart.settings');
    $fields = array(
      'name' => uc_store_email_to_username($order->getEmail()),
      'mail' => $order->getEmail(),
      'init' => $order->getEmail(),
      'pass' => user_password(),
      'roles' => array(),
      'status' => $cart_config->get('new_customer_status_active') ? 1 : 0,
    );

    // Override the username, if specified.
    if (isset($order->data->new_user_name)) {
      $fields['name'] = $order->data->new_user_name;
    }

    // Create the account.
    $account = User::create($fields);
    $account->save();

    // Override the password, if specified.
    if (isset($order->data->new_user_hash)) {
      db_query('UPDATE {users_field_data} SET pass = :hash WHERE uid = :uid', [':hash' => $order->data->new_user_hash, ':uid' => $account->id()]);
      $account->password = t('Your password');
    }
    else {
      $account->password = $fields['pass'];
      $order->password = $fields['pass'];
    }

    // Send the customer their account details if enabled.
    if ($cart_config->get('new_customer_email')) {
      $type = $cart_config->get('new_customer_status_active') ? 'register_no_approval_required' : 'register_pending_approval';
      \Drupal::service('plugin.manager.mail')->mail('user', $type, $order->getEmail(), uc_store_mail_recipient_langcode($order->getEmail()), array('account' => $account), uc_store_email_from());
    }

    $order->setOwner($account);
    $order->data->new_user_name = $fields['name'];
    $order->data->complete_sale =  'new_user';
  }

}
