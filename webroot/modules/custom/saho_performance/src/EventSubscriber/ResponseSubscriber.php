<?php

namespace Drupal\saho_performance\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber for performance optimizations.
 */
class ResponseSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['onResponse', -10],
    ];
  }

  /**
   * Sets performance-related headers on the response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();
    $request = $event->getRequest();

    // Only apply to HTML responses.
    $content_type = $response->headers->get('Content-Type');
    if (!$content_type || strpos($content_type, 'text/html') === FALSE) {
      return;
    }

    // Set cache headers for better performance.
    if (!$response->headers->has('Cache-Control')) {
      // Cache HTML for 5 minutes in browser, 1 hour in CDN.
      $response->headers->set('Cache-Control', 'public, max-age=300, s-maxage=3600');
    }

    // Add performance hints.
    $this->addLinkHeaders($response, $request);

    // Do NOT set Content-Encoding header here - let Apache handle compression.
    // Setting this header without actual compression causes encoding errors.
    // Add timing headers for debugging.
    if ($request->server->get('REQUEST_TIME_FLOAT')) {
      $time = microtime(TRUE) - $request->server->get('REQUEST_TIME_FLOAT');
      $response->headers->set('X-Response-Time', round($time * 1000) . 'ms');
    }

    // Security headers that also improve performance.
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

    // Feature Policy / Permissions Policy for performance.
    $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
  }

  /**
   * Add Link headers for resource hints.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response object.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  protected function addLinkHeaders($response, $request) {
    // DNS prefetch for external resources.
    $dns_prefetch = [
      '</www.google-analytics.com>; rel=dns-prefetch',
      '</fonts.gstatic.com>; rel=dns-prefetch',
    ];

    // Set Link header with resource hints.
    // Note: Font preloads are handled via HTML <link> tags in saho_performance_page_attachments()
    // to avoid duplicate fetches. CSS/JS are loaded by Drupal's library system with versioned
    // query params that won't match a static Link header URL.
    $response->headers->set('Link', implode(', ', $dns_prefetch));
  }

}
