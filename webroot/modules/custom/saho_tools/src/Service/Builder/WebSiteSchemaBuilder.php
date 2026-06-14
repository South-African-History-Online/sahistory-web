<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org WebSite structured data for SAHO site-wide.
 *
 * Emits a WebSite object with a SearchAction potentialAction so search
 * engines can surface the sitelinks search box for SAHO. The SearchAction
 * target points at the canonical Search API view (/search) and its exposed
 * fulltext filter parameter (search_api_fulltext).
 */
class WebSiteSchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * The canonical production host for SAHO.
   *
   * This matches the host advertised in robots.txt (Sitemap) and the
   * canonical apex that www.sahistory.org.za redirects to. Using the apex
   * here avoids the 301 redirect in the SearchAction target.
   */
  const CANONICAL_URL = 'https://www.sahistory.org.za/';

  /**
   * Constructs a WebSiteSchemaBuilder.
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
    // This builder is for the site-wide WebSite schema, not node-specific.
    return $node_type === 'website';
  }

  /**
   * {@inheritdoc}
   */
  public function build(?NodeInterface $node = NULL): array {
    return [
      '@context' => 'https://schema.org',
      '@type' => 'WebSite',
      'url' => self::CANONICAL_URL,
      'name' => 'South African History Online',
      'alternateName' => 'SAHO',
      'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => [
          '@type' => 'EntryPoint',
          'urlTemplate' => self::CANONICAL_URL . 'search?search_api_fulltext={search_term_string}',
        ],
        'query-input' => 'required name=search_term_string',
      ],
    ];
  }

}
