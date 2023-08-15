<?php

/**
 * @file
 * Theme functions for the uc_credit module.
 */

use Drupal\Core\Url;

/**
 * Themes the credit card CVV help link.
 */
function theme_uc_credit_cvv_help(array $variables) {
  $build['image'] = array(
    '#theme' => 'image',
    '#uri' => drupal_get_path('module', 'uc_credit') . '/images/info.png',
    '#alt' => 'What is the CVV?',
    '#prefix' => '<div class="uc-credit-cvv-help">',
  );
  $build['link'] = array(
    '#type' => 'link',
    '#title' => t("What's the CVV?"),
    '#url' => Url::fromRoute('uc_credit.cvv', ['uc_payment_method' => $variables['method']], ['attributes' => ['onclick' => "window.open(this.href, 'CVV_Info', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=480,height=520'); return false;",]]),
    '#suffix' => '</div>',
  );

  return \Drupal::service('renderer')->render($build);
}
