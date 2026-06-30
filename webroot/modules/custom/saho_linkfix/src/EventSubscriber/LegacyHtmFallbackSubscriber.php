<?php

declare(strict_types=1);

namespace Drupal\saho_linkfix\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Last-resort smart-search redirect for unmapped legacy .htm/.html URLs.
 *
 * This replaces the Apache "smart redirect for .htm files to search" block that
 * used to live in .htaccess. That block fired before Drupal and so masked the
 * precise legacy redirects in the redirect table (which point at the exact
 * node). With those rules removed, legacy .htm requests now reach Drupal: the
 * redirect module serves the precise redirects first, and only genuinely
 * unmapped .htm requests fall through to this subscriber, which guesses a
 * typed search page exactly as the old Apache rules did.
 *
 * It runs on the 404 exception, so it never competes with a real route or a
 * precise redirect - both resolve earlier in the request.
 */
final class LegacyHtmFallbackSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Priority just above Drupal's own 404 handling so we answer first, but
    // well below the redirect module's REQUEST-phase handling.
    return [KernelEvents::EXCEPTION => ['onException', 50]];
  }

  /**
   * Convert an unmapped legacy .htm 404 into a typed search redirect.
   */
  public function onException(ExceptionEvent $event): void {
    if (!$event->getThrowable() instanceof NotFoundHttpException) {
      return;
    }
    $path = rawurldecode($event->getRequest()->getPathInfo());
    if (!preg_match('~\.html?$~i', $path)) {
      return;
    }

    $lower = strtolower($path);
    $stem = pathinfo($path, PATHINFO_FILENAME);

    // index.htm anywhere -> homepage, mirroring the old Apache rule.
    if (strtolower($stem) === 'index') {
      $event->setResponse(new TrustedRedirectResponse('/', 301));
      return;
    }
    if ($stem === '') {
      return;
    }

    // Map the legacy path category to a content-type filtered search, matching
    // the original .htaccess intent.
    [$type, $title_scoped] = match (TRUE) {
      str_contains($lower, 'people') || str_contains($lower, 'bios') => ['biography', TRUE],
      str_contains($lower, 'places') => ['place', FALSE],
      str_contains($lower, 'archive') => ['archive', FALSE],
      str_contains($lower, 'article') || str_contains($lower, 'page') => ['article', FALSE],
      // Root-level bare file (e.g. /dadoo.htm) was treated as a person.
      !str_contains(trim($path, '/'), '/') => ['biography', TRUE],
      default => [NULL, FALSE],
    };

    // Articles preserved the slug after an "articleNNN-" prefix.
    if ($type === 'article' && preg_match('/^article\d+[-_](.+)$/i', $stem, $m)) {
      $stem = $m[1];
    }

    $query = ['search_api_fulltext' => $stem];
    if ($title_scoped) {
      $query['search_api_fulltext_searched_fields'] = ['title'];
    }
    if ($type !== NULL) {
      $query['type'] = $type;
    }
    $query['sort_by'] = 'search_api_relevance';

    $url = '/search?' . http_build_query($query);
    $response = new TrustedRedirectResponse($url, 301);
    // Keep the 404 cacheable-by-path so Cloudflare can hold the redirect.
    $response->getCacheableMetadata()->setCacheMaxAge(86400);
    $event->setResponse($response);
  }

}
