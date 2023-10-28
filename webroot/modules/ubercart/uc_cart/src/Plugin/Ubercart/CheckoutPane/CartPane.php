<?php

namespace Drupal\uc_cart\Plugin\Ubercart\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_cart\CheckoutPanePluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Displays the cart contents for review during checkout.
 *
 * @CheckoutPane(
 *   id = "cart",
 *   title = @Translation("Cart contents"),
 *   weight = 1,
 * )
 */
class CartPane extends CheckoutPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $build = array(
      '#theme' => 'uc_cart_review_table',
      '#items' => $order->products,
    );
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function review(OrderInterface $order) {
    $review[] = array(
      '#theme' => 'uc_cart_review_table',
      '#items' => $order->products,
      '#show_subtotal' => FALSE,
    );
    return $review;
  }

}
