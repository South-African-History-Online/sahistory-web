<?php

/**
 * @file
 * Contains \Drupal\responsive_favicons\Routing\DefaultFavicons.
 */

namespace Drupal\responsive_favicons\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Class DefaultFavicons.
 *
 * @package Drupal\responsive_favicons\Routing
 * Listens to the dynamic route events.
 */
class DefaultFavicons {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $route_collection = new RouteCollection();
    $moduleHandler = \Drupal::service('module_handler');

    // List of icons to redirect.
    // Note, in order for these to work alter the fast404 pattern to allow these
    // requests to hit Drupal. Please see the README for more information.
    $icons = [
      '/apple-touch-icon.png',
      '/apple-touch-icon-precomposed.png',
      '/browserconfig.xml',
    ];
    // Try to avoid clashing with the favicon module.
    if (!$moduleHandler->moduleExists('favicon')) {
      $icons[] = '/favicon.ico';
    }
    foreach($icons as $icon) {
      $route = new Route(
        // Path to attach this route to:
        $icon,
        // Route defaults:
        [
          '_controller' => '\Drupal\responsive_favicons\Controller\GetFile::deliver',
          '_title' => ''
        ],
        // Route requirements:
        [
          '_access' => 'TRUE',
        ]
      );
      // Add the route under a unique key.
      $key = preg_replace("/[^A-Za-z]/", '', $icon);
      $route_collection->add('responsive_favicons.' . $key, $route);
    }

    return $route_collection;
  }
}
