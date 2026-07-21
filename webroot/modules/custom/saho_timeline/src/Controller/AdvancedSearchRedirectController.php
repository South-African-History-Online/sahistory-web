<?php

declare(strict_types=1);

namespace Drupal\saho_timeline\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\LocalRedirectResponse;

/**
 * Retires /search/advanced: the URL 301s to the faceted /search.
 *
 * The advanced query builder had no real use next to the scoped registers
 * and the faceted global search (retired 2026-07-21); the route survives
 * only so inbound links and indexed URLs land somewhere useful.
 */
final class AdvancedSearchRedirectController extends ControllerBase {

  /**
   * Redirects the legacy advanced-search URL to the global search page.
   */
  public function redirectToSearch(): LocalRedirectResponse {
    $response = new LocalRedirectResponse('/search', 301);
    $response->getCacheableMetadata()->setCacheMaxAge(86400);
    return $response;
  }

}
