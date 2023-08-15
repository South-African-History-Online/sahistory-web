<?php

/**
 * @file
 * Token hooks for the uc_store module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function uc_store_token_info() {
  $type = array(
    'name' => t('Store information'),
    'description' => t('Tokens for store-specific, but globally available, information.'),
  );

  $site['login-link'] = array(
    'name' => t('Login URL'),
    'description' => t('A link to the site login page.'),
  );
  $site['logo'] = array(
    'name' => t('Logo'),
    'description' => t('The image showing the site logo.'),
  );

  $store['name'] = array(
    'name' => t('Store name'),
    'description' => t('The Ubercart store name.'),
  );
  $store['link'] = array(
    'name' => t('Store link'),
    'description' => t('A link to the Ubercart store using the store name.'),
  );
  $store['email'] = array(
    'name' => t('Email'),
    'description' => t('The Ubercart store e-mail address.'),
  );
  $store['phone'] = array(
    'name' => t('Phone number'),
    'description' => t('The Ubercart store phone number.'),
  );
  $store['fax'] = array(
    'name' => t('Fax number'),
    'description' => t('The Ubercart store fax number.'),
  );
  $store['address'] = array(
    'name' => t('Address'),
    'description' => t('The Ubercart store mailing address.'),
  );
  $store['help-url'] = array(
    'name' => t('Help page URL'),
    'description' => t('The URL to the store help page.'),
    'type' => 'url',
  );

  return array(
    'types' => array('store' => $type),
    'tokens' => array(
      'site' => $site,
      'store' => $store,
    ),
  );
}

/**
 * Implements hook_tokens().
 */
function uc_store_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = array();

  if ($type == 'site') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'login-link':
          $login_link = Url::fromRoute('user.page', [], ['absolute' => TRUE])->toString();
          $replacements[$original] = Link::fromTextAndUrl($login_link, Url::fromUri($login_link))->toString();
          break;

        case 'logo':
          // Use a logo; but only if we have one to use.
          $replacements[$original] = '';
          if ($uri = theme_get_setting('logo.url')) {
            $logo = array(
              '#theme' => 'image',
              '#uri' => $uri,
            );
            $replacements[$original] = drupal_render($logo);
          }
          break;
      }
    }
  }

  if ($type == 'store') {
    $config = \Drupal::config('uc_store.settings');

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'name':
          $replacements[$original] = uc_store_name();
          break;

        case 'link':
          $replacements[$original] = Link::createFromRoute(uc_store_name(), '<front>', [], ['absolute' => TRUE])->toString();
          break;

        case 'email':
          $replacements[$original] = uc_store_email();
          break;

        case 'phone':
          $replacements[$original] = $config->get('phone');
          break;

        case 'fax':
          $replacements[$original] = $config->get('fax');
          break;

        case 'address':
          // Cast Address object to string to get country-specific formatting.
          $address = array('#markup' => (string) uc_store_address());
          $replacements[$original] = drupal_render($address);
          break;

        case 'help-url':
          $replacements[$original] = Url::fromUserInput('/' . $config->get('help_page'), ['absolute' => TRUE])->toString();
          break;
      }
    }

    // Handle chaining for tokens that have 'type' defined in hook_token_info()
    if ($link_tokens = $token_service->findWithPrefix($tokens, 'help-url')) {
      $replacements += $token_service->generate('url', $link_tokens, array('path' => $config->get('help_page')), $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
