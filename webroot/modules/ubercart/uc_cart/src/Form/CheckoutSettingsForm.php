<?php

namespace Drupal\uc_cart\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\uc_cart\Plugin\CheckoutPaneManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure general checkout settings for this site.
 */
class CheckoutSettingsForm extends ConfigFormBase {

  /**
   * The checkout pane manager.
   *
   * @var \Drupal\uc_cart\Plugin\CheckoutPaneManager
   */
  protected $checkoutPaneManager;

  /**
   * Constructs a CheckoutSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\uc_cart\Plugin\CheckoutPaneManager $checkout_pane_manager
   *   The checkout pane plugin manager.
   */
  public function __construct(ConfigFactory $config_factory, CheckoutPaneManager $checkout_pane_manager) {
    parent::__construct($config_factory);

    $this->checkoutPaneManager = $checkout_pane_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.uc_cart.checkout_pane')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_checkout_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_cart.settings',
      'uc_cart.messages',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cart_config = $this->config('uc_cart.settings');
    $messages = $this->config('uc_cart.messages');

    $form['checkout-settings'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'library' => array(
          'uc_cart/uc_cart.admin.scripts',
        ),
      ),
    );

    $form['checkout'] = array(
      '#type' => 'details',
      '#title' => $this->t('Basic settings'),
      '#group' => 'checkout-settings',
      '#weight' => -10,
    );
    $form['checkout']['uc_checkout_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable checkout.'),
      '#description' => $this->t('Disable this to use only third party checkout services, such as PayPal Express Checkout.'),
      '#default_value' => $cart_config->get('checkout_enabled'),
    );

    //@todo: Uncomment this conditional when Rules actually works.
    //if (!\Drupal::moduleHandler()->moduleExists('rules')) {
      $form['checkout']['uc_checkout_email_customer'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Send e-mail invoice to customer after checkout.'),
        '#default_value' => $cart_config->get('checkout_email_customer'),
      );
      $form['checkout']['uc_checkout_email_admin'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Send e-mail order notification to admin after checkout.'),
        '#default_value' => $cart_config->get('checkout_email_admin'),
      );
    //}

    $form['anonymous'] = array(
      '#type' => 'details',
      '#title' => $this->t('Anonymous checkout'),
      '#group' => 'checkout-settings',
      '#weight' => -5,
    );
    $form['anonymous']['uc_checkout_anonymous'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable anonymous checkout.'),
      '#description' => $this->t('Disable this to force users to log in before the checkout page.'),
      '#default_value' => $cart_config->get('checkout_anonymous'),
    );
    $anon_state = array('visible' => array('input[name="uc_checkout_anonymous"]' => array('checked' => TRUE)));
    $form['anonymous']['uc_cart_mail_existing'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Allow anonymous customers to use an existing account's email address."),
      '#default_value' => $cart_config->get('mail_existing'),
      '#description' => $this->t('If enabled, orders will be attached to the account matching the email address. If disabled, anonymous users using a registered email address must log in or use a different email address.'),
      '#states' => $anon_state,
    );
    $form['anonymous']['uc_cart_email_validation'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Require e-mail confirmation for anonymous customers.'),
      '#default_value' => $cart_config->get('email_validation'),
      '#states' => $anon_state,
    );
    $form['anonymous']['uc_cart_new_account_name'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow new customers to specify a username.'),
      '#default_value' => $cart_config->get('new_account_name'),
      '#states' => $anon_state,
    );
    $form['anonymous']['uc_cart_new_account_password'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow new customers to specify a password.'),
      '#default_value' => $cart_config->get('new_account_password'),
      '#states' => $anon_state,
    );
    $form['anonymous']['uc_new_customer_email'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Send new customers a separate e-mail with their account details.'),
      '#default_value' => $cart_config->get('new_customer_email'),
      '#states' => $anon_state,
    );
    $form['anonymous']['uc_new_customer_login'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Log in new customers after checkout.'),
      '#default_value' => $cart_config->get('new_customer_login'),
      '#states' => $anon_state,
    );
    $form['anonymous']['uc_new_customer_status_active'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Set new customer accounts to active.'),
      '#description' => $this->t('Uncheck to create new accounts but make them blocked.'),
      '#default_value' => $cart_config->get('new_customer_status_active'),
      '#states' => $anon_state,
    );

    $panes = $this->checkoutPaneManager->getPanes();
    $form['checkout']['panes'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Pane'),
        array('data' => $this->t('List postion'), 'colspan' => 2, 'class' => array(RESPONSIVE_PRIORITY_LOW)),
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'uc-checkout-pane-weight',
        ),
      ),
    );
    $form['checkout']['pane_settings']['#tree'] = TRUE;
    foreach ($panes as $id => $pane) {
      $form['checkout']['panes'][$id]['#attributes']['class'][] = 'draggable';
      $form['checkout']['panes'][$id]['status'] = array(
        '#type' => 'checkbox',
        '#title' => $pane->getTitle(),
        '#default_value' => $pane->isEnabled(),
      );
      $form['checkout']['panes'][$id]['weight'] = array(
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $pane->getTitle()]),
        '#title_display' => 'invisible',
        '#default_value' => $pane->getWeight(),
        '#attributes' => array(
          'class' => array('uc-checkout-pane-weight'),
        ),
      );
      $form['checkout']['panes'][$id]['id'] = array(
        '#type' => 'hidden',
        '#value' => $id,
      );
      $form['checkout']['panes'][$id]['#weight'] = $pane->getWeight();

      // @todo Move settingsForm to an interface.
      $pane_settings = $pane->settingsForm();
      if (!empty($pane_settings)) {
        $form['checkout']['pane_settings'][$id] = $pane_settings + array(
          '#type' => 'details',
          '#title' => $this->t('@pane pane', ['@pane' => $pane->getTitle()]),
          '#group' => 'checkout-settings',
          '#parents' => array('panes', $id, 'settings'),
        );
      }
    }

    $form['completion_messages'] = array(
      '#type' => 'details',
      '#title' => $this->t('Completion messages'),
      '#group' => 'checkout-settings',
    );
    $form['completion_messages']['uc_msg_order_logged_in'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Logged in users'),
      '#description' => $this->t('Message displayed upon checkout for a user who is logged in.'),
      '#default_value' => $messages->get('logged_in'),
      '#rows' => 3,
    );
    $form['completion_messages']['uc_msg_order_existing_user'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Existing users'),
      '#description' => $this->t("Message displayed upon checkout for a user who has an account but wasn't logged in."),
      '#default_value' => $messages->get('existing_user'),
      '#rows' => 3,
      '#states' => $anon_state,
    );
    $form['completion_messages']['uc_msg_order_new_user'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('New users'),
      '#description' => $this->t("Message displayed upon checkout for a new user whose account was just created. You may use the special tokens !new_username for the username of a newly created account and !new_password for that account's password."),
      '#default_value' => $messages->get('new_user'),
      '#rows' => 3,
      '#states' => $anon_state,
    );
    $form['completion_messages']['uc_msg_order_new_user_logged_in'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('New logged in users'),
      '#description' => $this->t('Message displayed upon checkout for a new user whose account was just created and also <em>"Login users when new customer accounts are created at checkout."</em> is set on the <a href=":url">checkout settings</a>.', [':url' => Url::fromRoute('uc_cart.checkout_settings')->toString()]),
      '#default_value' => $messages->get('new_user_logged_in'),
      '#rows' => 3,
      '#states' => $anon_state,
    );

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['completion_messages']['token_tree'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => ['uc_order', 'site', 'store'],
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cart_config = $this->config('uc_cart.settings');
    $cart_config
      ->set('checkout_enabled', $form_state->getValue('uc_checkout_enabled'));

    //@todo: Uncomment this conditional when Rules actually works.
    //if (!\Drupal::moduleHandler()->moduleExists('rules')) {
      $cart_config
        ->set('checkout_email_customer', $form_state->getValue('uc_checkout_email_customer'))
        ->set('checkout_email_admin', $form_state->getValue('uc_checkout_email_admin'));
    //}

    $cart_config
      ->set('checkout_anonymous', $form_state->getValue('uc_checkout_anonymous'))
      ->set('mail_existing', $form_state->getValue('uc_cart_mail_existing'))
      ->set('email_validation', $form_state->getValue('uc_cart_email_validation'))
      ->set('new_account_name', $form_state->getValue('uc_cart_new_account_name'))
      ->set('new_account_password', $form_state->getValue('uc_cart_new_account_password'))
      ->set('new_customer_email', $form_state->getValue('uc_new_customer_email'))
      ->set('new_customer_login', $form_state->getValue('uc_new_customer_login'))
      ->set('new_customer_status_active', $form_state->getValue('uc_new_customer_status_active'))
      ->set('panes', $form_state->getValue('panes'))
      ->save();

    $this->config('uc_cart.messages')
      ->set('logged_in', $form_state->getValue('uc_msg_order_logged_in'))
      ->set('existing_user', $form_state->getValue('uc_msg_order_existing_user'))
      ->set('new_user', $form_state->getValue('uc_msg_order_new_user'))
      ->set('new_user_logged_in', $form_state->getValue('uc_msg_order_new_user_logged_in'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
