<?php

namespace Drupal\saho_tools\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;

/**
 * Service for generating Schema.org JSON-LD structured data.
 *
 * This service orchestrates all Schema.org builders and provides
 * centralized schema generation for SAHO content.
 */
class SchemaOrgService {

  /**
   * Registered schema builders keyed by content type.
   *
   * @var \Drupal\saho_tools\Service\SchemaOrgBuilderInterface[]
   */
  protected array $builders = [];

  /**
   * Constructs a SchemaOrgService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected RendererInterface $renderer,
    protected CacheBackendInterface $cache,
  ) {}

  /**
   * Register a schema builder for a content type.
   *
   * @param string $type
   *   The content type machine name.
   * @param \Drupal\saho_tools\Service\SchemaOrgBuilderInterface $builder
   *   The builder instance.
   */
  public function registerBuilder(string $type, SchemaOrgBuilderInterface $builder): void {
    $this->builders[$type] = $builder;
  }

  /**
   * Generate Schema.org structured data for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return array
   *   Schema.org structured data, or empty array if no builder available.
   */
  public function generateSchemaForNode(NodeInterface $node): array {
    $node_type = $node->getType();
    $cache_key = 'schema_org:node:' . $node->id() . ':' . $node->getChangedTime();

    // Try to get from cache.
    if ($cached = $this->cache->get($cache_key)) {
      return $cached->data;
    }

    // Find the appropriate builder.
    if (isset($this->builders[$node_type])) {
      $schema = $this->builders[$node_type]->build($node);
    }
    else {
      // No specific builder, return empty array.
      $schema = [];
    }

    // Cache for 1 hour.
    if (!empty($schema)) {
      $this->cache->set($cache_key, $schema, time() + 3600, ['node:' . $node->id()]);
    }

    return $schema;
  }

  /**
   * Generate site-wide Organization schema.
   *
   * @return array
   *   Schema.org Organization structured data.
   */
  public function generateOrganizationSchema(): array {
    $cache_key = 'schema_org:organization:sitewide';

    // Try to get from cache.
    if ($cached = $this->cache->get($cache_key)) {
      return $cached->data;
    }

    // Use the organization builder if registered.
    if (isset($this->builders['organization'])) {
      $schema = $this->builders['organization']->build(NULL);
    }
    else {
      // Fallback to basic organization schema.
      $schema = $this->buildBasicOrganizationSchema();
    }

    // Cache for 24 hours.
    $this->cache->set($cache_key, $schema, time() + 86400, ['config:system.site']);

    return $schema;
  }

  /**
   * Generate BreadcrumbList schema for current page.
   *
   * @return array
   *   Schema.org BreadcrumbList structured data.
   */
  public function generateBreadcrumbSchema(): array {
    // Use the breadcrumb builder if registered.
    if (isset($this->builders['breadcrumb'])) {
      return $this->builders['breadcrumb']->build(NULL);
    }

    return [];
  }

  /**
   * Build basic fallback Organization schema.
   *
   * @return array
   *   Basic organization schema.
   */
  protected function buildBasicOrganizationSchema(): array {
    $config = \Drupal::config('system.site');
    $request = \Drupal::request();
    $base_url = $request->getSchemeAndHttpHost();

    return [
      '@context' => 'https://schema.org',
      '@type' => ['Organization', 'EducationalOrganization'],
      'name' => 'South African History Online',
      'alternateName' => 'SAHO',
      'url' => $base_url,
      'logo' => $base_url . '/themes/custom/saho/logo.png',
      'description' => 'South African History Online - The premier destination for South African history online.',
      'sameAs' => [
        'https://www.facebook.com/sahistoryonline',
        'https://twitter.com/sahistoryonline',
      ],
    ];
  }

  /**
   * Clear schema cache for a specific node.
   *
   * @param int $node_id
   *   The node ID.
   */
  public function clearNodeCache(int $node_id): void {
    $this->cache->invalidate('schema_org:node:' . $node_id);
  }

  /**
   * Clear all schema caches.
   */
  public function clearAllCaches(): void {
    $this->cache->deleteAll();
  }

}
