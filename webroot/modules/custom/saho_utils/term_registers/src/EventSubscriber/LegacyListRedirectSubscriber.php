<?php

declare(strict_types=1);

namespace Drupal\term_registers\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects retired legacy list-view paths onto their landing register (301).
 *
 * The D7-era list views (women-biographies, prisoners-list, archives-books
 * and friends) were left enabled and orphaned when the Open Record landings
 * replaced them; nothing links to them but search engines and old bookmarks
 * still do. The views are now disabled (status: false in config/sync), so
 * their routes no longer exist - this subscriber therefore runs BEFORE the
 * router (priority above RouterListener's 32 and the redirect module's 33)
 * and matches the raw request path.
 *
 * Targets follow _saho_landing_registers(): where a register tile exists the
 * landing opens with that register applied (BEF's param[VAL]=VAL format);
 * otherwise the landing root. Kill-switch shared with the term redirects:
 * term_registers.settings:enabled.
 */
final class LegacyListRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Lower-cased legacy path (no slashes) => target path + optional query.
   */
  public const MAP = [
    // Biography lists.
    'women-biographies' => ['path' => '/biographies', 'query' => ['tid_1' => ['13' => '13']]],
    'deaths-in-detention-list' => ['path' => '/biographies', 'query' => ['register' => ['21904' => '21904']]],
    'biogrpahies-level-3' => ['path' => '/biographies'],
    'prisoners-list' => ['path' => '/biographies'],
    'biographies-of-musicians' => ['path' => '/biographies'],
    'biographies-of-musicians-0test' => ['path' => '/biographies'],
    'biographies-arts-and-culture' => ['path' => '/biographies'],
    'biographies-alpha-pagination' => ['path' => '/biographies'],
    'people-by-organisation' => ['path' => '/biographies'],
    // Place lists.
    'places-alpha-pagination' => ['path' => '/places'],
    'places-alpha-pagination-2' => ['path' => '/places'],
    'places-limpopo' => ['path' => '/places'],
    'places-audit' => ['path' => '/places'],
    'saho-places' => ['path' => '/places'],
    // Archive lists.
    'archives-alpha-pagination' => ['path' => '/archives'],
    'archives-by-type' => ['path' => '/archives'],
    'archives-by-publication-date' => ['path' => '/archives'],
    'archives-books' => ['path' => '/archives', 'query' => ['type' => ['Online book' => 'Online book']]],
    'online-book-list' => ['path' => '/archives', 'query' => ['type' => ['Online book' => 'Online book']]],
  ];

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Before routing: the legacy views are disabled, so the router would
    // 404 these paths at priority 32 and the redirect module's own listener
    // runs at 33. Path matching needs no route match.
    return [KernelEvents::REQUEST => ['onRequest', 34]];
  }

  /**
   * Issues the register redirect for a retired legacy list path.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }
    $path = mb_strtolower(trim($event->getRequest()->getPathInfo(), '/'));
    $target = self::MAP[$path] ?? NULL;
    if ($target === NULL) {
      return;
    }
    $settings = $this->configFactory->get('term_registers.settings');
    if (!$settings->get('enabled')) {
      return;
    }

    $url = $target['path'];
    if (!empty($target['query'])) {
      $url .= '?' . http_build_query($target['query']);
    }

    $response = new LocalRedirectResponse($url, 301);
    $response->addCacheableDependency(
      (new CacheableMetadata())
        ->addCacheContexts(['url.path'])
        ->addCacheableDependency($settings)
    );
    $event->setResponse($response);
  }

}
