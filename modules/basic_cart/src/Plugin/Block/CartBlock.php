<?php

namespace Drupal\basic_cart\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\basic_cart\Utility;

/**
 * Provides a 'Basic Cart' block.
 *
 * @Block(
 *   id = "basic_cart_cartblock",
 *   admin_label = @Translation("Basic Cart Block")
 * )
 */
class CartBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = Utility::cartSettings();
    return array(
      '#theme' => 'basic_cart_cart_template',
      '#basic_cart' => Utility::getCartData(),
      '#type' => 'markup',
      '#title' => $config->get('cart_block_title'),
      '#cache' => array('max-age' => 0),
    );
  }

}
