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

    // Normalize the Vary header so a CDN (Cloudflare) can actually cache
    // anonymous HTML. Drupal emits "Vary: Cookie" (and a downstream layer adds
    // "User-Agent"); Cloudflare refuses to cache any response whose Vary header
    // contains anything other than Accept-Encoding, so those pages always hit
    // the origin. We only collapse Vary to Accept-Encoding when the response is
    // a public-cacheable GET with NO Drupal session cookie present, i.e. a true
    // anonymous page. Authenticated users keep the original Vary, and the
    // Cloudflare cache rule additionally bypasses cache on the SESS cookie, so
    // logged-in visitors can never be served a shared cached page.
    $cache_control = (string) $response->headers->get('Cache-Control', '');
    $is_public = strpos($cache_control, 'public') !== FALSE
      && strpos($cache_control, 'private') === FALSE
      && strpos($cache_control, 'no-store') === FALSE;
    $has_session = FALSE;
    foreach ($request->cookies->keys() as $cookie_name) {
      if (strpos($cookie_name, 'SESS') !== FALSE) {
        $has_session = TRUE;
        break;
      }
    }
    if ($request->isMethodCacheable() && $is_public && !$has_session) {
      $response->headers->set('Vary', 'Accept-Encoding');
    }

    // Add performance hints. Only anonymous-cacheable pages: Cloudflare
    // serves Early Hints from cache per URL, and those are the responses
    // it caches.
    if ($request->isMethodCacheable() && $is_public && !$has_session) {
      $this->addLinkHeaders($response, $request);
    }

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
    // Preload the page's render-blocking stylesheets via HTTP Link headers.
    // Cloudflare's Early Hints feature turns exactly these into 103
    // responses, so browsers fetch the blocking CSS before the HTML body
    // arrives - attacking the render chain without touching any stylesheet.
    // The aggregate URLs are hashed per page-set, so they are read from the
    // response itself rather than configured statically. (The old static
    // dns-prefetch hints pointed at hosts the redesign no longer uses.)
    $content = $response->getContent();
    if (!is_string($content) || $content === '') {
      return;
    }
    // Only the <head> section, and only same-origin stylesheet links.
    $head_end = strpos($content, '</head>');
    $head = $head_end === FALSE ? substr($content, 0, 65536) : substr($content, 0, $head_end);
    if (!preg_match_all('/<link[^>]+rel="stylesheet"[^>]+href="(\/[^"]+\.css[^"]*)"/', $head, $matches)) {
      return;
    }
    $hints = [];
    foreach (array_slice($matches[1], 0, 4) as $href) {
      // & in HTML attributes arrives as &amp;.
      $href = str_replace('&amp;', '&', $href);
      $hints[] = '<' . $href . '>; rel=preload; as=style';
    }
    if ($hints) {
      $response->headers->set('Link', implode(', ', $hints));
    }
  }

}
