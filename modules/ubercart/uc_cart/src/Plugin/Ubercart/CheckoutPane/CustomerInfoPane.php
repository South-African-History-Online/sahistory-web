<?php

namespace Drupal\uc_cart\Plugin\Ubercart\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_cart\CheckoutPanePluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Gets the user's email address for login.
 *
 * @CheckoutPane(
 *   id = "customer",
 *   title = @Translation("Customer information"),
 *   weight = 2,
 * )
 */
class CustomerInfoPane extends CheckoutPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $cart_config = \Drupal::config('uc_cart.settings');

    if ($user->isAuthenticated()) {
      $email = $user->getEmail();
      $contents['#description'] = $this->t('Order information will be sent to your account e-mail listed below.');
      $contents['primary_email'] = array('#type' => 'hidden', '#value' => $email);
      $contents['email_text'] = array(
        '#markup' => '<div>' . $this->t('<b>E-mail address:</b> @email (<a href=":url">edit</a>)', ['@email' => $email, ':url' => Url::fromRoute('entity.user.edit_form', ['user' => $user->id()], ['query' => drupal_get_destination()])->toString()]) . '</div>',
      );
    }
    else {
      $email = $order->getEmail();
      $contents['#description'] = $this->t('Enter a valid email address for this order or <a href=":url">click here</a> to login with an existing account and return to checkout.', [':url' => Url::fromRoute('user.login', [], ['query' => drupal_get_destination()])->toString()]);
      $contents['primary_email'] = array(
        '#type' => 'email',
        '#title' => $this->t('E-mail address'),
        '#default_value' => $email,
        '#required' => TRUE,
      );

      if ($cart_config->get('email_validation')) {
        $contents['primary_email_confirm'] = array(
          '#type' => 'email',
          '#title' => $this->t('Confirm e-mail address'),
          '#default_value' => $email,
          '#required' => TRUE,
        );
      }

      $contents['new_account'] = array();

      if ($cart_config->get('new_account_name')) {
        $contents['new_account']['name'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Username'),
          '#default_value' => isset($order->data->new_user_name) ? $order->data->new_user_name : '',
          '#maxlength' => 60,
          '#size' => 32,
        );
      }
      if ($cart_config->get('new_account_password')) {
        $contents['new_account']['pass'] = array(
          '#type' => 'password',
          '#title' => $this->t('Password'),
          '#maxlength' => 32,
          '#size' => 32,
        );
        $contents['new_account']['pass_confirm'] = array(
          '#type' => 'password',
          '#title' => $this->t('Confirm password'),
          '#description' => $this->t('Passwords must match to proceed.'),
          '#maxlength' => 32,
          '#size' => 32,
        );
      }

      if (!empty($contents['new_account'])) {
        $contents['new_account'] += array(
          '#type' => 'details',
          '#title' => $this->t('New account details'),
          '#description' => $this->t('<b>Optional.</b> New customers may supply custom account details.<br />We will create these for you if no values are entered.'),
          '#open' => TRUE,
        );
      }
    }

    return $contents;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order, array $form, FormStateInterface $form_state) {
    if (\Drupal::currentUser()->isAnonymous()) {
      $cart_config = \Drupal::config('uc_cart.settings');

      $pane = $form_state->getValue(['panes', 'customer']);
      $order->setEmail($pane['primary_email']);

      // Check if the email address is already taken.
      $mail_taken = (bool) \Drupal::entityQuery('user')
        ->condition('mail', $pane['primary_email'])
        ->range(0, 1)
        ->count()
        ->execute();

      if ($cart_config->get('email_validation') && $pane['primary_email'] !== $pane['primary_email_confirm']) {
        $form_state->setErrorByName('panes][customer][primary_email_confirm', $this->t('The e-mail address did not match.'));
      }

      // Invalidate if an account already exists for this e-mail address, and the user is not logged into that account
      if (!$cart_config->get('mail_existing') && !empty($pane['primary_email']) && $mail_taken) {
        $form_state->setErrorByName('panes][customer][primary_email', $this->t('An account already exists for your e-mail address. You will either need to login with this e-mail address or use a different e-mail address.'));
      }

      // If new users can specify names or passwords then...
      if ($cart_config->get('new_account_name') || $cart_config->get('new_account_password')) {
        // Skip if an account already exists for this e-mail address.
        if ($cart_config->get('mail_existing') && $mail_taken) {
          drupal_set_message($this->t('An account already exists for your e-mail address. The new account details you entered will be disregarded.'));
        }
        else {
          // Validate the username.
          if ($cart_config->get('new_account_name') && !empty($pane['new_account']['name'])) {
            $message = user_validate_name($pane['new_account']['name']);
            $name_taken = (bool) \Drupal::entityQuery('user')
              ->condition('name', $pane['new_account']['name'])
              ->range(0, 1)
              ->count()
              ->execute();

            if (!empty($message)) {
              $form_state->setErrorByName('panes][customer][new_account][name', $message);
            }
            elseif ($name_taken) {
              $form_state->setErrorByName('panes][customer][new_account][name', $this->t('The username %name is already taken. Please enter a different name or leave the field blank for your username to be your e-mail address.', ['%name' => $pane['new_account']['name']]));
            }
            else {
              $order->data->new_user_name = $pane['new_account']['name'];
            }
          }

          // Validate the password.
          if ($cart_config->get('new_account_password')) {
            if (strcmp($pane['new_account']['pass'], $pane['new_account']['pass_confirm'])) {
              $form_state->setErrorByName('panes][customer][new_account][pass_confirm', $this->t('The passwords you entered did not match. Please try again.'));
            }
            if (!empty($pane['new_account']['pass'])) {
              $order->data->new_user_hash = \Drupal::service('password')->hash(trim($pane['new_account']['pass']));
            }
          }
        }
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function review(OrderInterface $order) {
    $review[] = array('title' => $this->t('E-mail'), 'data' => array('#plain_text' => $order->getEmail()));
    return $review;
  }

}
