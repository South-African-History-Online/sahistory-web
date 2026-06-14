<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org BreadcrumbList structured data.
 *
 * Generates breadcrumb navigation schema for improved search engine
 * understanding of site hierarchy and navigation.
 */
class BreadcrumbSchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * Constructs a BreadcrumbSchemaBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function supports(string $node_type): bool {
    // This builder is for breadcrumb schema, not node-specific.
    return $node_type === 'breadcrumb';
  }

  /**
   * {@inheritdoc}
   */
  public function build(?NodeInterface $node = NULL): array {
    $request = \Drupal::request();
    $base_url = $request->getSchemeAndHttpHost();
    $route_match = \Drupal::routeMatch();

    // Start with home.
    $items = [
      [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Home',
        'item' => $base_url,
      ],
    ];

    $position = 2;

    // If we're on a node page, add node-specific breadcrumbs.
    $node = $route_match->getParameter('node');
    if ($node instanceof NodeInterface) {
      $node_type = $node->getType();

      // Add content type as second level.
      //
      // Each entry maps a node type to its human-readable label and the
      // canonical landing page path. Google voids any BreadcrumbList item
      // whose 'item' URL is not a live 200 response, so the paths below are
      // verified-200 landing pages on production (not the bare node-type
      // machine name, which 404s or 301s). Image nodes have no dedicated
      // images landing (the /images path 301s to the home page), so they
      // share the verified-200 /archives landing alongside archive nodes.
      // Node types without any stable 200 landing page are intentionally
      // omitted: it is better to drop one breadcrumb level than to void the
      // entire BreadcrumbList with a 404/301 URL.
      $type_landings = [
        'article' => ['name' => 'Politics & Society', 'path' => 'politics-society'],
        'biography' => ['name' => 'Biographies', 'path' => 'biographies'],
        'event' => ['name' => 'This Day in History', 'path' => 'this-day-in-history'],
        'archive' => ['name' => 'Archives', 'path' => 'archives'],
        'image' => ['name' => 'Archives', 'path' => 'archives'],
        'place' => ['name' => 'Places', 'path' => 'places'],
      ];

      if (isset($type_landings[$node_type])) {
        $items[] = [
          '@type' => 'ListItem',
          'position' => $position++,
          'name' => $type_landings[$node_type]['name'],
          'item' => $base_url . '/' . $type_landings[$node_type]['path'],
        ];
      }

      // Add current page.
      $items[] = [
        '@type' => 'ListItem',
        'position' => $position,
        'name' => $node->getTitle(),
        'item' => $node->toUrl()->setAbsolute()->toString(),
      ];
    }

    // Only return breadcrumb schema if we have more than just home.
    if (count($items) <= 1) {
      return [];
    }

    return [
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => $items,
    ];
  }

}
