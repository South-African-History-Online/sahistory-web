<?php

namespace Drupal\uc_product\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\node\NodeInterface;

/**
 * Provides an access checker for products.
 */
class ProductAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(NodeInterface $node) {
    return AccessResult::allowedIf(uc_product_is_product($node));
  }

}
