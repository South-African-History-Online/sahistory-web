<?php

namespace Drupal\uc_cart_links\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure general shopping cart settings for this site.
 */
class CartLinksSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_links_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_cart_links.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cart_links_config = $this->config('uc_cart_links.settings');

    $form['uc_cart_links_add_show'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display the cart link product action when you add a product to your cart.'),
      '#default_value' => $cart_links_config->get('add_show'),
    );
    $form['uc_cart_links_track'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Track clicks through Cart Links that specify tracking IDs.'),
      '#default_value' => $cart_links_config->get('track'),
    );
    $form['uc_cart_links_empty'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Cart Links to empty customer carts.'),
      '#default_value' => $cart_links_config->get('empty'),
    );
    $form['uc_cart_links_messages'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Cart Links messages'),
      '#description' => $this->t('Enter messages available to the Cart Links API for display through a link. Separate messages with a line break. Each message should have a numeric key and text value, separated by "|". For example: 1337|Message text.'),
      '#default_value' => $cart_links_config->get('messages'),
    );
    $form['uc_cart_links_restrictions'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Cart Links restrictions'),
      '#description' => $this->t('To restrict what Cart Links may be used on your site, enter all valid Cart Links in this textbox.  Separate links with a line break. Leave blank to permit any cart link.'),
      '#default_value' => $cart_links_config->get('restrictions'),
    );
    $form['uc_cart_links_invalid_page'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Invalid link redirect page'),
      '#description' => $this->t('Enter the URL to redirect to when an invalid cart link is used.'),
      '#default_value' => $cart_links_config->get('invalid_page'),
      '#size' => 32,
      '#field_prefix' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $messages = (string) $form_state->getValue('uc_cart_links_messages');
    if (!empty($messages)) {
      $data = explode("\n", $messages);
      foreach ($data as $message) {
        // Ignore blank lines.
        if (preg_match('/^\s*$/', $message)) {
           continue;
        }
        // Check for properly formattted messages.
        // Each line must be one or more numeric characters for the key followed
        // by "|" followed by one or more characters for the value. Both the key
        // and the value may have leading and/or trailing whitespace.
        elseif (!preg_match('/^\s*[1-9][0-9]*\s*\|\s*\S+.*$/', $message)) {
           $form_state->setErrorByName('uc_cart_links_messages', $this->t('Invalid Cart Links message "%message". Messages must be a numeric key followed by "|" followed by a value.', ['%message' => $message]));
           break;
        }
      }
    }

    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cart_links_config = $this->config('uc_cart_links.settings');

    $cart_links_config
      ->setData(array(
        'add_show' => (boolean) $form_state->getValue('uc_cart_links_add_show'),
        'track' => (boolean) $form_state->getValue('uc_cart_links_track'),
        'empty' => (boolean) $form_state->getValue('uc_cart_links_empty'),
        'messages' => (string) $form_state->getValue('uc_cart_links_messages'),
        'restrictions' => (string) $form_state->getValue('uc_cart_links_restrictions'),
        'invalid_page' => (string) $form_state->getValue('uc_cart_links_invalid_page'),
      ))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
