<?php

/**
 * @file
 * Default Rules configurations for the uc_role module.
 */

/**
 * Implements hook_default_rules_configuration().
 */
function uc_role_default_rules_configuration() {
  $configs = array();

  // Renew all the roles on an order when the status matches what's set
  // in the roles admin settings.
  $rule = rules_reaction_rule();
  $rule->label = t('Grant or renew purchased roles');
  $rule->active = TRUE;
  $rule->event('uc_order_status_update');
  $rule->condition('data_is', array('data:select' => 'updated_order:order-status', 'value' => 'payment_received'))
    ->action('uc_role_order_renew', array('order:select' => 'updated_order', 'message' => FALSE));
  $configs['uc_role_renewal'] = $rule;

  // Notify the user when a role is granted.
  $rule = rules_reaction_rule();
  $rule->label = t('Notify customer when a role is granted');
  $rule->active = TRUE;
  $rule->event('uc_role_notify_grant');
  $rule->action('uc_role_order_email', array(
    'order:select' => 'order',
    'expiration:select' => 'expiration',
    'from' => uc_store_email_from(),
    'addresses' => '[order:email]',
    'subject' => t('[store:name]: [expiration:name] role granted'),
    'message' => t("[order:first-name] [order:last-name], \n\nThanks to your order, [order:link], at [store:name] you now have a new role, [expiration:name].\n\nThanks again, \n\n[store:name]\n[site:slogan]"),
    'format' => filter_default_format(),
  ));
  $configs['uc_role_notify_grant'] = $rule;

  // Notify the user when a role is revoked.
  $rule = rules_reaction_rule();
  $rule->label = t('Notify customer when a role is revoked');
  $rule->active = TRUE;
  $rule->event('uc_role_notify_revoke');
  $rule->action('uc_role_user_email', array(
    'account:select' => 'account',
    'expiration:select' => 'expiration',
    'from' => uc_store_email_from(),
    'addresses' => '[account:mail]',
    'subject' => t('[store:name]: [expiration:name] role expired'),
    'message' => t("The role, [expiration:name], you acquired by purchasing a product at our store has expired. Any special access or privileges that came with it are now gone. You can purchase it again by going to [store:link]\n\nThanks again, \n\n[store:name]\n[site:slogan]"),
    'format' => filter_default_format(),
  ));
  $configs['uc_role_notify_revoke'] = $rule;

  // Notify the user when a role is renewed.
  $rule = rules_reaction_rule();
  $rule->label = t('Notify customer when a role is renewed');
  $rule->active = TRUE;
  $rule->event('uc_role_notify_renew');
  $rule->action('uc_role_order_email', array(
    'order:select' => 'order',
    'expiration:select' => 'expiration',
    'from' => uc_store_email_from(),
    'addresses' => '[order:email]',
    'subject' => t('[store:name]: [expiration:name] role renewed'),
    'message' => t("[order:first-name] [order:last-name], \n\nThanks to your order, [order:link], at [store:name] you have renewed the role, [expiration:name]. It is now set to expire on [expiration:expiration:short].\n\nThanks again, \n\n[store:name]\n[site:slogan]"),
    'format' => filter_default_format(),
  ));
  $configs['uc_role_notify_renew'] = $rule;

  // Notify the user when a role is about to expire.
  $rule = rules_reaction_rule();
  $rule->label = t('Notify customer when a role is about to expire');
  $rule->active = TRUE;
  $rule->event('uc_role_notify_reminder');
  $rule->action('uc_role_user_email', array(
    'account:select' => 'account',
    'expiration:select' => 'expiration',
    'from' => uc_store_email_from(),
    'addresses' => '[account:mail]',
    'subject' => t('[store:name]: [expiration:name] role expiration notice'),
    'message' => t("This message is to remind you that the role, [expiration:name], you acquired by making a purchase at our store will expire at [expiration:expiration:short]. You may visit [store:link] to renew this role before it expires.\n\nThanks again, \n\n[store:name]\n[site:slogan]"),
    'format' => filter_default_format(),
  ));
  $configs['uc_role_notify_reminder'] = $rule;

  return $configs;
}
