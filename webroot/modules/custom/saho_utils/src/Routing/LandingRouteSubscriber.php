<?php

declare(strict_types=1);

namespace Drupal\saho_utils\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\saho_utils\Controller\LandingTitleController;
use Drupal\saho_utils\LandingTypes;
use Symfony\Component\Routing\RouteCollection;

/**
 * Hardens the /index/{type} landing route.
 *
 * The saho_landing view takes a node_type contextual argument. The
 * entity:node_type argument validator cannot restrict bundles (a NodeType
 * config entity has no bundle of its own), so without this subscriber every
 * node type - product, page, webform, quiz - resolves to an indexable
 * landing page. The route requirement below lets the router 404 anything
 * outside LandingTypes::LABELS before the view is even loaded.
 *
 * The title callback replaces the views default (which resolves the view
 * title on an unbuilt view, so every landing shared the head title "Browse
 * the archive") with the per-type landing label; metatag and breadcrumbs
 * follow automatically.
 */
final class LandingRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    $route = $collection->get('view.saho_landing.page_1');
    if ($route === NULL) {
      return;
    }
    $route->setRequirement('arg_0', LandingTypes::routeRequirement());
    $route->setDefault('_title_callback', LandingTitleController::class . '::title');
  }

}
