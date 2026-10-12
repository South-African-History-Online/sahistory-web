<?php

declare(strict_types=1);

namespace Drupal\saho_linkfix\EventSubscriber;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\saho_linkfix\Service\ArchivePdfLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects dead archive PDF URLs to the genuine copy on disk.
 *
 * Handles two families of 404:
 *  - /archive/NAME.pdf - the old site's bare archive path, still requested by
 *    search engines and inbound links (hundreds a day in dblog).
 *  - /sites/default/files/{DC/<folder>|archivefiles|archive}/NAME.pdf - paths
 *    from records whose directory no longer exists. The Apache DC/ chain in
 *    the untracked .htaccess covers part of this; doing it here keeps the rule
 *    in git and adds case-insensitive matching.
 *
 * Missing files under the public directory are routed to index.php by the
 * front controller rules, so they arrive here as a 404 like any other path.
 */
final class ArchivePdfFallbackSubscriber implements EventSubscriberInterface {

  /**
   * Dead path patterns; group 1 is the bare file name.
   */
  private const PATTERNS = [
    '~^/archive/([^/]+\.pdf)$~i',
    '~^/sites/default/files/(?:DC/[^/]+|archivefiles|archive)/([^/]+\.pdf)$~i',
  ];

  public function __construct(
    protected readonly ArchivePdfLocator $locator,
    protected readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Above core's ExceptionLoggingSubscriber (50): a redirected request is
    // not a 404, so it must not land in dblog as "page not found". Setting the
    // response stops propagation, so the logger and redirect_404 never see it.
    // Shares the slot with the legacy .htm fallback; the path families differ.
    return [KernelEvents::EXCEPTION => ['onException', 60]];
  }

  /**
   * Turn a dead archive PDF 404 into a 301 to the genuine copy.
   */
  public function onException(ExceptionEvent $event): void {
    if (!$event->getThrowable() instanceof NotFoundHttpException) {
      return;
    }
    $path = rawurldecode($event->getRequest()->getPathInfo());
    $name = NULL;
    foreach (self::PATTERNS as $pattern) {
      if (preg_match($pattern, $path, $m)) {
        $name = $m[1];
        break;
      }
    }
    if ($name === NULL) {
      return;
    }

    $target = $this->locator->locate($name);
    if ($target === NULL) {
      return;
    }
    $url = $this->fileUrlGenerator->generateString('public://' . $target);
    if (rawurldecode($url) === $path) {
      return;
    }

    $response = new LocalRedirectResponse($url, 301);
    // Same lifetime as the .htm fallback so Cloudflare can hold the redirect.
    $response->getCacheableMetadata()->setCacheMaxAge(86400);
    $event->setResponse($response);
  }

}
