<?php

declare(strict_types=1);

namespace Drupal\term_registers\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects mapped taxonomy term pages to their filtered landing register.
 *
 * A term page is a dead-end listing; the Open Record landings carry the
 * full register UX (facet chips, search, table/cards toggle, load-more).
 * For mapped vocabularies the canonical term route 301s to the landing
 * with the term pre-applied through its URL-only exposed filter, so every
 * term link on a record page opens the register instead.
 *
 * Unmapped vocabularies fall through untouched - which also preserves the
 * Africa term's special same-path view display and the classroom/keyword
 * term pages. Kill-switch: term_registers.settings:enabled (the config
 * cache tag rides every 301, so flipping it invalidates cached redirects
 * without a deploy).
 */
final class TermRegisterRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Vocabulary => register target.
   *
   * 'mode' tid: the landing filter is taxonomy_index_tid - the URL carries
   * ?param[TID]=TID (BEF's own checkbox format, so visible facet widgets
   * tick where they exist). 'mode' label: the /archives facets key their
   * values by term LABEL strings.
   */
  private const MAP = [
    'member_of_organisation' => ['path' => '/biographies', 'param' => 'org', 'mode' => 'tid'],
    'prison_list' => ['path' => '/biographies', 'param' => 'prison', 'mode' => 'tid'],
    'field_people_category' => ['path' => '/biographies', 'param' => 'tid_1', 'mode' => 'tid'],
    'field_people_level3_cat' => ['path' => '/biographies', 'param' => 'register', 'mode' => 'tid'],
    'field_place_type' => ['path' => '/places', 'param' => 'ptype', 'mode' => 'tid'],
    'field_places_level3' => ['path' => '/places', 'param' => 'tid_2', 'mode' => 'tid'],
    'saldru_archive_topic' => ['path' => '/archives', 'param' => 'saldru', 'mode' => 'label'],
    'field_media_library_type' => ['path' => '/archives', 'param' => 'type', 'mode' => 'label'],
  ];

  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // After routing (priority 32) so the route match is resolved; before
    // dynamic_page_cache and the controller build the term page.
    return [KernelEvents::REQUEST => ['onRequest', 30]];
  }

  /**
   * Issues the register redirect for mapped term canonical routes.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }
    // Canonical route only: edit/delete/admin/JSON:API routes have their
    // own route names and pass through untouched.
    if ($this->routeMatch->getRouteName() !== 'entity.taxonomy_term.canonical') {
      return;
    }
    $settings = $this->configFactory->get('term_registers.settings');
    if (!$settings->get('enabled')) {
      return;
    }
    $term = $this->routeMatch->getParameter('taxonomy_term');
    if (!$term instanceof TermInterface) {
      return;
    }
    $target = self::MAP[$term->bundle()] ?? NULL;
    if ($target === NULL) {
      return;
    }

    $key = $target['mode'] === 'label' ? $term->label() : $term->id();
    $url = $target['path'] . '?' . http_build_query([$target['param'] => [$key => $key]]);

    $response = new LocalRedirectResponse($url, 301);
    $response->addCacheableDependency(
      CacheableMetadata::createFromObject($term)
        ->addCacheableDependency($settings)
    );
    $event->setResponse($response);
  }

}
