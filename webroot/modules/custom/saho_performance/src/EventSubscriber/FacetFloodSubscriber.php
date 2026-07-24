<?php

namespace Drupal\saho_performance\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Rejects absurd facet combinations on the classroom browse page.
 *
 * On 2026-07-24 a ~13.6k-IP scraper fleet enumerated the caps_topic[] x
 * grade[] checkbox space of /classroom/presentations (20k+ distinct query
 * strings in under two hours). Every unique query string misses the edge
 * cache and costs a full Drupal bootstrap plus a Views render.
 *
 * A Cloudflare challenge rule is the first line of defence; this subscriber
 * is the origin backstop for traffic that bypasses or outlives it. Requests
 * selecting more facet values than any person plausibly would are answered
 * with a cheap 400 immediately after routing, before the controller and
 * Views ever run. Per-IP rate limiting is useless against this fleet (max
 * 14 requests per IP), so the cap keys on the request shape instead.
 */
class FacetFloodSubscriber implements EventSubscriberInterface {

  /**
   * Route name of the classroom deck browse page.
   */
  protected const CLASSROOM_ROUTE = 'view.classroom_presentations.page_1';

  /**
   * Exposed filter identifiers whose selected values are counted.
   */
  protected const FACET_PARAMS = ['caps_topic', 'grade'];

  /**
   * Maximum total selected facet values before the request is rejected.
   *
   * The UI offers ~50 topics and ~12 grades; a person narrows, a bot
   * enumerates. Eight total selections is well past any real usage.
   */
  protected const MAX_FACET_VALUES = 8;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new FacetFloodSubscriber object.
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
    // Priority 30: directly after routing (RouterListener runs at 32) so the
    // route name is known, but before any controller work happens.
    return [
      KernelEvents::REQUEST => ['onRequest', 30],
    ];
  }

  /**
   * Replies 400 when too many facet values are selected.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event) {
    if (!$event->isMainRequest()
      || $this->routeMatch->getRouteName() !== static::CLASSROOM_ROUTE) {
      return;
    }

    $selected = 0;
    foreach (static::FACET_PARAMS as $param) {
      $values = $event->getRequest()->query->all()[$param] ?? [];
      $selected += is_array($values) ? count($values) : 1;
    }

    if ($selected <= static::MAX_FACET_VALUES) {
      return;
    }

    $event->setResponse(new Response(
      'Too many filters selected. Please narrow your selection to ' . static::MAX_FACET_VALUES . ' or fewer.',
      400,
      ['Content-Type' => 'text/plain; charset=UTF-8']
    ));
  }

}
