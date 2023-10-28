<?php

namespace Drupal\basic_cart\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure checkout settings for this site.
 */
class CheckOutSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basic_cart_admin_checkout_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'basic_cart.checkout',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('basic_cart.checkout');
    $form['email_messages'] = array(
      '#title' => t('Email messages'),
      '#type' => 'fieldset',
      '#description' => t('Here you can customize the mails sent to the site administrator and customer, after an order is placed.'),
    );

    $form['email_messages']['basic_cart_administrator_emails'] = array(
      '#title' => t('Administrator emails'),
      '#type' => 'textarea',
      '#description' => t('After each placed order, an email with the order details will be sent to all the addresses from the list above. Please add one email address per line.'),
      '#default_value' => $config->get('admin_emails') ? $config->get('admin_emails') : \Drupal::config('system.site')->get('mail'),
    );

    $form['email_messages']['basic_cart_subject_admin'] = array(
      '#title' => t('Subject'),
      '#type' => 'textfield',
      '#description' => t("Subject field for the administrator's email."),
      '#default_value' => $config->get('admin')['subject'],
    );

    $form['email_messages']['basic_cart_administer_message'] = array(
      '#title' => t('Admin email'),
      '#type' => 'textarea',
      '#description' => t('This email will be sent to the site administrator just after an order is placed. Please see all available tokens below. For listing the products, please use: [basic_cart_order:products]'),
      '#default_value' => $config->get('admin')['body'],
    );

    $form['email_messages']['basic_cart_send_emailto_user'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Send an email to the customer after an order is placed'),
      '#default_value' => $config->get('send_emailto_user'),
    );

    $form['email_messages']['basic_cart_subject_user'] = array(
      '#title' => t('Subject'),
      '#type' => 'textfield',
      '#description' => t("Subject field for the user's email."),
      '#default_value' => $config->get('user')['subject'],
    );

    $form['email_messages']['basic_cart_user_message'] = array(
      '#title' => t('User email'),
      '#type' => 'textarea',
      '#description' => t('This email will be sent to the user just after an order is placed. Please see all available tokens below. For listing the products, please use: [basic_cart_order:products]'),
      '#default_value' => $config->get('user')['body'],
    );

    $form['thankyou'] = array(
      '#title' => t('Thank you page'),
      '#type' => 'fieldset',
      '#description' => t('Thank you page customization.'),
    );

    $form['thankyou']['basic_cart_thankyou_page_title'] = array(
      '#title' => t('Title'),
      '#type' => 'textfield',
      '#description' => t("Thank you page title."),
      '#default_value' => $config->get('thankyou')['title'],
    );

    $form['thankyou']['basic_cart_thankyou_page_text'] = array(
      '#title' => t('Text'),
      '#type' => 'textarea',
      '#description' => t('Thank you page text.'),
      '#default_value' => $config->get('thankyou')['text'],
    );

    $form['thankyou']['basic_cart_thankyou_custom_page'] = array(
      '#title' => t('Redirect to custom thankyou page url'),
      '#type' => 'textfield',
      '#description' => t('Redirect to your custom url after the successfull order creation.(if value given, page will be redirected to given url, instead of showing thankyou page content above)'),
      '#default_value' => $config->get('thankyou')['custom_page'],
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $admin = array("subject" => $form_state->getValue('basic_cart_subject_admin'), "body" => $form_state->getValue('basic_cart_administer_message'));
    $user = array("subject" => $form_state->getValue('basic_cart_subject_user'), "body" => $form_state->getValue('basic_cart_user_message'));
    $thankyou = array(
      "title" => $form_state->getValue('basic_cart_thankyou_page_title'),
      "text" => $form_state->getValue('basic_cart_thankyou_page_text'),
      "custom_page" => $form_state->getValue('basic_cart_thankyou_custom_page'),
    );
    $this->config('basic_cart.checkout')
      ->set('admin_emails', $form_state->getValue('basic_cart_administrator_emails'))
      ->set('admin', $admin)
      ->set('user', $user)
      ->set('send_emailto_user', $form_state->getValue('basic_cart_send_emailto_user'))
      ->set('thankyou', $thankyou)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
