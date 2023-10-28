<?php

namespace Drupal\imce\Routing;

use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RouteSubscriberBase;

/**
 * Listens to the dynamic route events.
 */
class ImceRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Conditionally declare imce.page an admin route.
    if ($route = $collection->get('imce.page')) {
      $config = \Drupal::config('imce.settings');
      if ($config->get('admin_theme')) {
        $route->setOption('_admin_route', TRUE);
      }
    }
  }

}
