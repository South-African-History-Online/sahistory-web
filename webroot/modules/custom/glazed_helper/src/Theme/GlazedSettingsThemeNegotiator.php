<?php

namespace Drupal\glazed_helper\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Forces theme settings forms to use the Theme that is being configured
 */
class GlazedSettingsThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();
    return ($route && ($route->getPath() == '/admin/appearance/settings/{theme}'));
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $current_theme = $route_match->getParameter('theme');
    return $current_theme;
  }

}
