<?php

/**
 * @file
 * Default rules configurations.
 */

/**
 * Implements hook_default_rules_configuration().
 */
function uc_order_default_rules_configuration() {
  $rule = rules_reaction_rule();
  $rule->label = t('E-mail an order update notification');
  $rule->active = TRUE;
  $rule->event('uc_order_status_email_update')
    ->condition(rules_condition('data_is', array('data:select' => 'order:order-status', 'value' => 'in_checkout'))->negate())
    ->action('uc_order_email', array(
      'order:select' => 'order',
      'from' => uc_store_email_from(),
      'addresses' => '[order:email]',
      'subject' => t('Order #[order:order-number] Update'),
      'message' => t("[order:first-name] [order:last-name],\n\nYour order number [order:link] at [store:name] has been updated.\n\nOrder status: [order:order-status]\n\nOrder comment:\n[order:last-comment]\n\nBrowse to the following page to login to your account and view your order details:\n[site:login-link]\n\n\nThanks again,\n\n[store:name]\n[site:slogan]"),
      'format' => filter_default_format(),
    ));

  $configs['uc_order_update_email_customer'] = $rule;

  return $configs;
}
