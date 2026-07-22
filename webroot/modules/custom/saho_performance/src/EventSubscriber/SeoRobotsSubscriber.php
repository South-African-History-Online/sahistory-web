<?php

namespace Drupal\saho_performance\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sends X-Robots-Tag: noindex on the faceted search-result routes.
 *
 * The /search and /archives Views expose an effectively infinite faceted
 * URL space (search_api_fulltext + sort_by + page + facets_*), which
 * Google crawls and then discards as "Crawled - currently not indexed" -
 * ~50k such URLs were burning crawl budget that should reach real content.
 *
 * We de-index them with a "noindex, follow" header rather than a robots.txt
 * Disallow: Googlebot must be able to fetch these URLs to see the noindex
 * and drop them, and "follow" preserves link equity to real content while
 * they drain. A robots.txt Disallow (crawl budget reclamation) is a
 * deliberate SECOND step, applied only after the index has drained -
 * disallowing first would trap the URLs in the index permanently.
 *
 * Matching is by exact route name, which is immune to language-prefix
 * variation (/zu/search), trailing slashes and query strings, and cannot
 * accidentally match a real content page aliased under a similar path.
 */
class SeoRobotsSubscriber implements EventSubscriberInterface {

  /**
   * Route names of the faceted search-result pages to de-index.
   */
  protected const NOINDEX_ROUTES = [
    // /search - global Search API results.
    'view.saho_global_search.page_1',
    // /archives - archive Search API results.
    'view.saho_archive_search.page_1',
    // /archive - legacy archive listing.
    'view.archive.page_1',
  ];

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new SeoRobotsSubscriber object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['onResponse', -10],
    ];
  }

  /**
   * Adds a noindex X-Robots-Tag on the faceted search-result routes.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onResponse(ResponseEvent $event) {
    if (!in_array($this->routeMatch->getRouteName(), static::NOINDEX_ROUTES, TRUE)) {
      return;
    }

    $response = $event->getResponse();

    // Only HTML responses carry an indexable document.
    $content_type = $response->headers->get('Content-Type');
    if (!$content_type || strpos($content_type, 'text/html') === FALSE) {
      return;
    }

    // "follow" keeps internal links to real content passing equity while
    // Google recrawls and drops these URLs from the index.
    $response->headers->set('X-Robots-Tag', 'noindex, follow');
  }

}
