<?php

namespace Drupal\tvi\Enhancer;

use Drupal\Core\Routing\EnhancerInterface;
use Drupal\tvi\Service\TaxonomyViewsIntegratorManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Enhancer to set correct views defaults arguments for Term based on TVI.
 *
 * By default TVI only alter route and change renderer for taxonomy term page.
 * But some modules, like Search API, Facets and others is hardly rely on
 * current_route_match service. This service allows developers to get router
 * parameters for current request current_route_match:getParameters(). This
 * method returns parameters for current route, but if TVI replaces views they
 * still remains taxonomy_term/page_1, or the first view page found by view with
 * taxonomy/term/%. This makes views detection incorrect, and modules behave
 * not as expected.
 *
 * This srvice fix it. It uses the same logit to find current view id and and
 * display id, and set it as defaults.
 */
class RouteEnhancer implements EnhancerInterface {

  /**
   * RouteEnhancer constructor.
   */
  public function __construct(TaxonomyViewsIntegratorManagerInterface $tvi_manager) {
    $this->tviManager = $tvi_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function applies(Route $route) {
    return $route->getPath() == '/taxonomy/term/{taxonomy_term}';
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (!empty($defaults['taxonomy_term']) && $term = $defaults['taxonomy_term']) {
      $term_view = $this->tviManager->getTaxonomyTermViewAndDisplayId($term);
      $defaults['view_id'] = $term_view['view_id'];
      $defaults['display_id'] = $term_view['display_id'];
    }
    return $defaults;
  }

}
