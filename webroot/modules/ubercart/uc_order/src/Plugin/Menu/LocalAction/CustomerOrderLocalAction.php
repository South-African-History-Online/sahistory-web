<?php

namespace Drupal\uc_order\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Modifies the 'Create order for this customer' local action.
 */
class CustomerOrderLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    return [
      'user' => $route_match->getRawParameter('arg_0'),
    ];
  }

}
