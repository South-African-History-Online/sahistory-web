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
      $type_labels = [
        'article' => 'Articles',
        'biography' => 'Biographies',
        'event' => 'Events',
        'archive' => 'Archives',
        'place' => 'Places',
        'product' => 'Products',
      ];

      if (isset($type_labels[$node_type])) {
        $items[] = [
          '@type' => 'ListItem',
          'position' => $position++,
          'name' => $type_labels[$node_type],
          'item' => $base_url . '/' . $node_type,
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
