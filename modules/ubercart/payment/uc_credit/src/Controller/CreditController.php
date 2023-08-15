<?php

namespace Drupal\uc_credit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_payment\Entity\PaymentMethod;

/**
 * Utility functions for credit card payment methods.
 */
class CreditController extends ControllerBase {

  /**
   * Displays the contents of the CVV information popup window.
   *
   * @param \Drupal\uc_payment\Entity\PaymentMethod $uc_payment_method
   *   The payment method to display information for.
   *
   * @return string
   *   HTML markup for a page.
   */
  public function cvvInfo(PaymentMethod $uc_payment_method) {
    $types = $uc_payment_method->getPlugin()->getEnabledTypes();

    $build['#attached']['library'][] = 'uc_credit/uc_credit.styles';
    // @todo: Move the embedded CSS below into uc_credit.css.
    $build['title'] = array(
      '#prefix' => '<strong>',
      '#markup' => $this->t('What is the CVV?'),
      '#suffix' => '</strong>',
    );
    $build['definition'] = array(
      '#prefix' => '<p>',
      '#markup' => $this->t('CVV stands for "Card Verification Value". This number is used as a security feature to protect you from credit card fraud. Finding the number on your card is a very simple process. Just follow the directions below.'),
      '#suffix' => '</p>',
    );

    $valid_types = array_diff_key($types, ['amex' => 1]);
    if (!empty($valid_types)) {
      $build['types'] = array(
        '#prefix' => '<br /><strong>',
        '#markup' => implode(', ', $valid_types),
        '#suffix' => ':</strong>',
      );
      $build['image'] = array(
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'uc_credit') . '/images/visa_cvv.jpg',
        '#alt' => 'CVV location',
        '#attributes' => array('align' => 'left'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );
      $build['where'] = array(
        '#prefix' => '<p>',
        '#markup' => $this->t('The CVV for these cards is found on the back side of the card. It is only the last three digits on the far right of the signature panel box.'),
        '#suffix' => '</p>',
      );
    }

    if (isset($types['amex'])) {
      $build['types-amex'] = array(
        '#prefix' => '<br /><strong>',
        '#markup' => $this->t('American Express'),
        '#suffix' => ':</strong>',
      );
      $build['image-amex'] = array(
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'uc_credit') . '/images/amex_cvv.jpg',
        '#alt' => 'Amex CVV location',
        '#attributes' => array('align' => 'left'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );
      $build['where-amex'] = array(
        '#prefix' => '<p>',
        '#markup' => $this->t('The CVV on American Express cards is found on the front of the card. It is a four digit number printed in smaller text on the right side above the credit card number.'),
        '#suffix' => '</p>',
      );
    }

    $build['close'] = array(
      '#type' => 'button',
      '#prefix' => '<p align="right">',
      '#value' => $this->t('Close this window'),
      '#attributes' => array('onclick' => 'self.close();'),
      '#suffix' => '</p>',
    );

    $renderer = \Drupal::service('bare_html_page_renderer');
    // @todo: Make our own theme function to use instead of 'page'?
    return $renderer->renderBarePage($build, $this->t('CVV Info'), 'page');
  }
}
