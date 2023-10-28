<?php

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\Entity\Order;
use Drupal\user\Entity\User;

/**
 * Creates a new order and redirect to its edit screen.
 */
class OrderCreateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['customer_type'] = array(
      '#type' => 'radios',
      '#options' => array(
        'search' => $this->t('Search for an existing customer.'),
        'create' => $this->t('Create a new customer account.'),
        'none'   => $this->t('No customer account required.'),
      ),
      '#required' => TRUE,
      '#default_value' => 'search',
      '#ajax' => array(
        'callback' => array($this, 'customerSelect'),
        'wrapper'  => 'uc-order-customer',
        'progress' => array('type' => 'throbber'),
      ),
    );

    $form['customer'] = array(
      '#prefix' => '<div id="uc-order-customer">',
      '#suffix' => '</div>',
      '#tree'   => TRUE,
    );

    // Create form elements needed for customer search.
    // Shown only when the 'Search for an existing customer.' radio is selected.
    if (!$form_state->hasValue('customer_type') ||
        $form_state->getValue('customer_type') == 'search') {
      // Container for customer search fields.
      $form['customer'] += array(
        '#type' => 'fieldset',
        '#title' => $this->t('Customer search'),
        '#description' => $this->t('Enter full or partial information in one or more of the following fields, then press the "Search" button. Search results will match all the provided information.'),
      );
      // Customer first name.
      $form['customer']['first_name'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('First name'),
        '#size' => 24,
        '#maxlength' => 32,
      );
      // Customer last name.
      $form['customer']['last_name'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Last name'),
        '#size' => 24,
        '#maxlength' => 32,
      );
      // Customer e-mail address.
      $form['customer']['email'] = array(
        '#type' => 'email',
        '#title' => $this->t('E-mail'),
        '#size' => 24,
        '#maxlength' => 96,
      );
      // Customer username.
      $form['customer']['username'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Username'),
        '#size' => 24,
        '#maxlength' => 96,
      );
      $form['customer']['search'] = array(
        '#type' => 'button',
        '#value' => $this->t('Search'),
        '#limit_validation_errors' => array(),
        '#submit' => array(),
        '#ajax' => array(
          'callback' => array($this, 'customerSearch'),
          'wrapper' => 'uc-order-customer-results',
          'progress' => array('type' => 'throbber'),
        ),
      );
      $form['customer']['uid'] = array(
        '#prefix' => '<div id="uc-order-customer-results">',
        '#suffix' => '</div>',
      );

      // Search for existing customer by e-mail address.
      if ($form_state->getValue('customer')) {
        $query = db_select('users_field_data', 'u')->distinct();
        $query->leftJoin('uc_orders', 'o', 'u.uid = o.uid');
        $query->fields('u', array('uid', 'name', 'mail'))
          ->fields('o', array('billing_first_name', 'billing_last_name'))
          ->condition('u.uid', 0, '>')
          ->condition(db_or()
            ->isNull('o.billing_first_name')
            ->condition('o.billing_first_name', db_like(trim($form_state->getValue(['customer', 'first_name']))) . '%', 'LIKE')
          )
          ->condition(db_or()
            ->isNull('o.billing_last_name')
            ->condition('o.billing_last_name', db_like(trim($form_state->getValue(['customer', 'last_name']))) . '%', 'LIKE')
          )
          ->condition(db_or()
            ->condition('o.primary_email', db_like(trim($form_state->getValue(['customer', 'email']))) . '%', 'LIKE')
            ->condition('u.mail', db_like(trim($form_state->getValue(['customer', 'email']))) . '%', 'LIKE')
          )
          ->condition('u.name', db_like(trim($form_state->getValue(['customer', 'username']))) . '%', 'LIKE')
          ->orderBy('o.created', 'DESC')
          ->range(0, $limit = 11);
        $result = $query->execute();

        $options = array();
        foreach ($result as $user) {
          $name = '';
          if (!empty($user->billing_first_name) && !empty($user->billing_last_name)) {
            $name = $user->billing_first_name . ' ' . $user->billing_last_name . ' ';
          }
          // Options formatted as "First Last <email@example.com> (username)".
          $options[$user->uid] = $name . '&lt;' . $user->mail . '&gt;' . ' (' . $user->name . ')';
        }

        $max = FALSE;
        if (count($options) == $limit) {
          array_pop($options);
          $max = TRUE;
        }

        if (!empty($options)) {
          // Display search results.
          $form['customer']['uid'] += array(
            '#type' => 'radios',
            '#title' => $this->t('Select customer'),
            '#description' => $max ? $this->t('More than @limit results found. Refine your search to find other customers.', ['@limit' => $limit - 1]) : '',
            '#options' => $options,
            '#default_value' => key($options),
          );
        }
        else {
          // No search results found.
          $form['customer']['uid'] += array(
            '#markup' => '<p>' . $this->t('Search returned no results.') . '</p>',
          );
        }
      }
    }
    // Create form elements needed for new customer creation.
    // Shown only when the 'Create a new customer account.' radio is selected.
    elseif ($form_state->getValue('customer_type') == 'create') {
      // Container for new customer information.
      $form['customer'] += array(
        '#type'  => 'fieldset',
        '#title' => $this->t('New customer details'),
      );
      // Customer e-mail address.
      $form['customer']['email'] = array(
        '#type' => 'email',
        '#title' => $this->t('Customer e-mail address'),
        '#size' => 24,
        '#maxlength' => 96,
      );
      // Option to notify customer.
      $form['customer']['sendmail'] = array(
        '#type'  => 'checkbox',
        '#title' => $this->t('E-mail account details to customer.'),
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Create order'),
    );

    return $form;
  }

  /**
   * Ajax callback: updates the customer selection fields.
   */
  public function customerSelect(array $form, FormStateInterface $form_state) {
    return $form['customer'];
  }

  /**
   * Ajax callback: updates the customer search results.
   */
  public function customerSearch(array $form, FormStateInterface $form_state) {
    return $form['customer']['uid'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    switch ($form_state->getValue('customer_type')) {
      case 'search':
        if (!$form_state->hasValue(['customer', 'uid'])) {
          $form_state->setErrorByName('customer][uid', $this->t('Please select a customer.'));
        }
        break;

      case 'create':
        $email = trim($form_state->getValue(['customer', 'email']));
        $uid = db_query('SELECT uid FROM {users_field_data} WHERE mail LIKE :mail', [':mail' => $email])->fetchField();
        if ($uid) {
          $form_state->setErrorByName('customer][mail', $this->t('An account already exists for that e-mail.'));
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    switch ($form_state->getValue('customer_type')) {
      case 'search':
        $uid = $form_state->getValue(['customer', 'uid']);
        break;

      case 'create':
        // Create new account.
        $email = trim($form_state->getValue(['customer', 'email']));
        $fields = array(
          'name' => uc_store_email_to_username($email),
          'mail' => $email,
          'pass' => user_password(),
          'status' => $this->config('uc_cart.settings')->get('new_customer_status_active') ? 1 : 0,
        );
        $account = User::create($fields);
        $account->save();
        $uid = $account->id();

        if ($form_state->getValue(['customer', 'sendmail'])) {
          // Manually set the password so it appears in the e-mail.
          $account->password = $fields['pass'];
          \Drupal::service('plugin.manager.mail')->mail('user', 'register_admin_created', $email, uc_store_mail_recipient_langcode($email), array('account' => $account), uc_store_email_from());
          drupal_set_message($this->t('A welcome message has been e-mailed to the new user.'));
        }
        break;

      default:
        $uid = 0;
    }

    $order = Order::create(array(
      'uid' => $uid,
      'order_status' => uc_order_state_default('post_checkout'),
    ));
    $order->save();
    uc_order_comment_save($order->id(), $this->currentUser()->id(), $this->t('Order created by the administration.'), 'admin');

    $form_state->setRedirect('entity.uc_order.edit_form', ['uc_order' => $order->id()]);
  }

}
