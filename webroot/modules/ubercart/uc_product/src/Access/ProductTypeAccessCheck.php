<?php

namespace Drupal\uc_product\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Provides an access checker for product-type nodes.
 */
class ProductTypeAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(NodeTypeInterface $node_type) {
    return AccessResult::allowedIf($node_type->getThirdPartySetting('uc_product', 'product', FALSE));
  }

}
