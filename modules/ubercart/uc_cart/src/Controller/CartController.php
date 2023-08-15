<?php

namespace Drupal\uc_cart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_cart\CartManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for the shopping cart.
 */
class CartController extends ControllerBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\uc_cart\CartManager
   */
  protected $cartManager;

  /**
   * Constructs a CartController.
   *
   * @param \Drupal\uc_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   */
  public function __construct(CartManagerInterface $cart_manager) {
    $this->cartManager = $cart_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('uc_cart.manager')
    );
  }

  /**
   * Displays the cart view page.
   *
   * Show the products in the cart with a form to adjust cart contents or go to
   * checkout.
   */
  public function listing() {
    // Load the array of shopping cart items.
    $cart = $this->cartManager->get();
    $items = $cart->getContents();

    // Display the empty cart page if there are no items in the cart.
    if (empty($items)) {
      $build = [
        '#theme' => 'uc_cart_empty',
      ];

      \Drupal::service('renderer')->addCacheableDependency($build, $cart);

      return $build;
    }

    return $this->formBuilder()->getForm('Drupal\uc_cart\Form\CartForm', $cart);
  }

}
