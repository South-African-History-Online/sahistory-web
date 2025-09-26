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

    // Only apply to HTML responses
    $content_type = $response->headers->get('Content-Type');
    if (!$content_type || strpos($content_type, 'text/html') === FALSE) {
      return;
    }

    // Set cache headers for better performance
    if (!$response->headers->has('Cache-Control')) {
      // Cache HTML for 5 minutes in browser, 1 hour in CDN
      $response->headers->set('Cache-Control', 'public, max-age=300, s-maxage=3600');
    }

    // Add performance hints
    $this->addLinkHeaders($response, $request);

    // Enable compression hint
    $response->headers->set('Content-Encoding', 'gzip', FALSE);

    // Add timing headers for debugging
    if ($request->server->get('REQUEST_TIME_FLOAT')) {
      $time = microtime(TRUE) - $request->server->get('REQUEST_TIME_FLOAT');
      $response->headers->set('X-Response-Time', round($time * 1000) . 'ms');
    }

    // Security headers that also improve performance
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
    $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

    // Feature Policy / Permissions Policy for performance
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
    $links = [];

    // Preload critical fonts
    $fonts = [
      '</themes/custom/saho/fonts/inter/Inter-Regular.woff2>; rel=preload; as=font; type=font/woff2; crossorigin',
      '</themes/custom/saho/fonts/inter/Inter-Medium.woff2>; rel=preload; as=font; type=font/woff2; crossorigin',
      '</themes/custom/saho/fonts/inter/Inter-Bold.woff2>; rel=preload; as=font; type=font/woff2; crossorigin',
    ];

    // Preload critical CSS
    $css = [
      '</themes/custom/saho/build/css/main.style.css>; rel=preload; as=style',
    ];

    $links = array_merge($links, $fonts, $css);

    if (!empty($links)) {
      $response->headers->set('Link', implode(', ', $links));
    }

    // Early hints for HTTP/3
    if ($request->server->get('SERVER_PROTOCOL') === 'HTTP/2.0' ||
        $request->server->get('SERVER_PROTOCOL') === 'HTTP/3.0') {
      $response->headers->set('X-Early-Hints', '103');
    }
  }

}