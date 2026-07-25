<?php

declare(strict_types=1);

namespace Drupal\saho_connections\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\saho_connections\ConnectionsBuilder;
use Drupal\saho_connections\ConnectionsBuilderInterface;
use Drupal\saho_refs\DisplayRefService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The per-record connections hub: the full list behind the rail's counts.
 *
 * The rail shows "People 487" but renders a bounded sample; this page IS
 * the list - one canonical URL per record, one connected-record type at a
 * time (?tab=), 50 rows per page. Counts come from the same builder the
 * rail reads, so the two surfaces can never disagree.
 */
final class ConnectionsHubController extends ControllerBase {

  /**
   * Record bundles that carry the Open Record apparatus.
   *
   * Everything else 404s: the hub must not mint an indexable URL for every
   * node on the site.
   */
  private const RECORD_BUNDLES = [
    'article',
    'biography',
    'place',
    'archive',
    'event',
    'image',
    'upcomingevent',
  ];

  /**
   * Rows per page - dense ruled rows; ANC's 487 people = 10 pages.
   */
  private const PAGE_SIZE = 50;

  public function __construct(
    private readonly ConnectionsBuilderInterface $connections,
    private readonly PagerManagerInterface $pagerManager,
    private readonly RequestStack $requestStack,
    private readonly DisplayRefService $displayRef,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('saho_connections.builder'),
      $container->get('pager.manager'),
      $container->get('request_stack'),
      $container->get('saho_refs.display_ref'),
    );
  }

  /**
   * Page title: "Connections · <record label>".
   */
  public function title(NodeInterface $node): string {
    return 'Connections · ' . $node->label();
  }

  /**
   * Builds the hub page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The record node.
   *
   * @return array
   *   A render array.
   */
  public function page(NodeInterface $node): array {
    if (!in_array($node->bundle(), self::RECORD_BUNDLES, TRUE)) {
      throw new NotFoundHttpException();
    }
    $inventory = $this->connections->inventory($node);
    if ($inventory === []) {
      throw new NotFoundHttpException();
    }

    // ?tab= is the one facet; anything not in the inventory falls back to
    // the first tab, in rail order. (Bad values are 200s: every query-string
    // variant of this route is noindexed anyway.) all()[...] rather than
    // get(): InputBag::get() throws on array input (?tab[]=x), and scraper
    // requests must degrade, not error.
    $requested = $this->requestStack->getCurrentRequest()->query->all()['tab'] ?? NULL;
    $tab_id = is_string($requested) && isset($inventory[$requested])
      ? $requested
      : array_key_first($inventory);

    $total = $inventory[$tab_id]['count'];
    $pager = $this->pagerManager->createPager($total, self::PAGE_SIZE);
    $result = $this->connections->items($node, $tab_id, $pager->getCurrentPage(), self::PAGE_SIZE);

    $hub_url = Url::fromRoute('saho_connections.hub', ['node' => $node->id()]);
    $chips = [];
    foreach ($inventory as $id => $info) {
      $chips[] = [
        'id' => $id,
        'label' => $info['label'],
        'count' => $info['count'],
        'href' => $hub_url->setOption('query', ['tab' => $id])->toString(),
        'active' => $id === $tab_id,
      ];
    }

    $build = [
      '#theme' => 'saho_connections_hub',
      '#record' => [
        'title' => $node->label(),
        'href' => $node->toUrl()->toString(),
        'type' => $node->bundle(),
        'reference' => $this->displayRef->getRef($node),
        'total' => $this->connections->totalCount($node),
      ],
      '#total' => $total,
      '#chips' => $chips,
      '#active' => [
        'id' => $tab_id,
        'label' => $result['label'],
      ],
      '#items' => $result['items'],
      '#pager' => ['#type' => 'pager'],
      // The archive tab defers to the richer faceted index as well.
      '#collection_url' => $tab_id === 'archive' && $total > ConnectionsBuilder::THRESHOLD
        ? Url::fromUserInput('/archives', ['query' => ['collection' => $node->id()]])->toString()
        : '',
      '#attached' => ['library' => ['saho_connections/connections-hub']],
      '#cache' => [
        'contexts' => [
          'user.permissions',
          'url.query_args:tab',
          'url.query_args:page',
        ],
        'tags' => $node->getCacheTags(),
      ],
    ];
    foreach ($this->connections->cacheTags() as $tag) {
      $build['#cache']['tags'][] = $tag;
    }
    return $build;
  }

}
