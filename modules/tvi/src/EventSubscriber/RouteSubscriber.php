<?php

namespace Drupal\tvi\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Alters the route for using Taxonomy Views Integrator.
 *
 * Views is allowed to alter and builds the routes first, and we then alter this
 * route after the fact. The idea is so we can have multiple views that define
 * `taxonomy/term/%` as the page path, and the settings form on the term/vocab
 * let the user determine which view displays.
 *
 * Otherwise, Views will always control the path, and that's not what we want.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Update the term router to use the TVI controller.
    if ($route = $collection->get('entity.taxonomy_term.canonical')) {
      $route->setDefault('_controller', '\Drupal\tvi\Controller\TaxonomyViewsIntegratorTermPageController::render');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run after Views subscriber has ran.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
    return $events;
  }

}
