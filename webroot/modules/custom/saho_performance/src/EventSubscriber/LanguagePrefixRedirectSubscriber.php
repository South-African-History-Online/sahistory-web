<?php

declare(strict_types=1);

namespace Drupal\saho_performance\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects language-prefixed URLs of untranslated content to English.
 *
 * The site enables 11 languages via URL path prefixes, but only classroom
 * presentation nodes carry real translations. Every other page renders
 * identical English content under all 10 non-English prefixes - each
 * self-canonical, so Google sees a 10x site mirror and piles the copies
 * into "Crawled - currently not indexed". No frontend navigation links to
 * prefixed URLs, so redirecting them breaks nothing.
 *
 * The redirect target is simply the request path minus the prefix: inbound
 * processing is strip-prefix-then-resolve-alias, so that IS the English URL
 * for the same resource, and a prefixless request never re-enters the
 * redirect branch (the default language has an empty prefix) - loop-proof
 * by construction. The one legitimate prefixed 200 - a published node
 * translation on its canonical route - passes through untouched, as do
 * admin routes (node edit / translation overview run in the admin theme,
 * so they all carry _admin_route).
 *
 * Kill-switch: saho_performance.settings:language_redirect_enabled (the
 * config cache tag rides every 301, so unticking it invalidates cached
 * redirects without a deploy).
 */
final class LanguagePrefixRedirectSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Priority 31: after RouterListener (32) so the route and its upcast
    // node parameter exist, but before redirect.route_normalizer (30) so a
    // prefixed alias 301s straight to the English alias in a single hop.
    // setResponse() stops propagation, so later REQUEST subscribers never
    // see redirected requests.
    return [KernelEvents::REQUEST => ['onRequest', 31]];
  }

  /**
   * Redirects prefixed requests for untranslated content to English.
   */
  public function onRequest(RequestEvent $event): void {
    $request = $event->getRequest();
    if (!$event->isMainRequest() || !$request->isMethodSafe()) {
      return;
    }
    $settings = $this->configFactory->get('saho_performance.settings');
    if (!$settings->get('language_redirect_enabled')) {
      return;
    }

    // Match the first path segment against the same prefix map the language
    // negotiator uses. The default language's empty prefix drops out, so a
    // hit means the URL carried a real non-English prefix.
    $negotiation = $this->configFactory->get('language.negotiation');
    $prefixes = array_filter($negotiation->get('url.prefixes') ?? []);
    $segments = explode('/', ltrim($request->getPathInfo(), '/'), 2);
    $langcode = array_search($segments[0], $prefixes, TRUE);
    if ($langcode === FALSE) {
      return;
    }

    // Editor and translation UIs (node edit, delete, translation overview)
    // all run as admin routes and pass through untouched.
    $route = $this->routeMatch->getRouteObject();
    if ($route === NULL || $route->getOption('_admin_route')) {
      return;
    }

    // The canonical node page is the only URL whose prefixed variant can be
    // a distinct document: a real published translation (classroom decks).
    // Sub-routes with a node parameter (/node/N/connections) render English
    // UI identical to their unprefixed twin and still redirect.
    if ($this->routeMatch->getRouteName() === 'entity.node.canonical') {
      $node = $this->routeMatch->getParameter('node');
      if ($node instanceof ContentEntityInterface && $node->hasTranslation($langcode)) {
        $translation = $node->getTranslation($langcode);
        if (!$translation instanceof EntityPublishedInterface || $translation->isPublished()) {
          return;
        }
      }
    }

    // Same URL minus the prefix ('/zu' becomes '/'), query string intact.
    $target = $request->getBasePath() . '/' . ($segments[1] ?? '');
    $query_string = $request->getQueryString();
    if ($query_string !== NULL) {
      $target .= '?' . $query_string;
    }

    $response = new LocalRedirectResponse($target, 301);
    $response->addCacheableDependency(
      (new CacheableMetadata())
        ->addCacheContexts(['url.path', 'url.query_args'])
        ->addCacheableDependency($settings)
        ->addCacheableDependency($negotiation)
        ->setCacheMaxAge(86400)
    );
    $event->setResponse($response);
  }

}
