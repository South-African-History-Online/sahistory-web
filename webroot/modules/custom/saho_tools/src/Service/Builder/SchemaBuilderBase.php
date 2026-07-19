<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Site\Settings;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Shared identity plumbing for all SAHO schema.org builders.
 *
 * Centralises three things every builder needs:
 * - the canonical base URL (apex host; www 301s to it in production), so
 *   local/staging hosts never leak into structured data;
 * - the stable identity block: @id on the permanent /ref/ URL from saho_refs,
 *   url on the canonical alias, and a SAHO-ref PropertyValue identifier;
 * - @id-linked stubs for the single sitewide Organization/WebSite entities,
 *   so publisher/holdingArchive/provider references dedupe to one entity
 *   instead of re-inlining SAHO on every block.
 */
abstract class SchemaBuilderBase implements SchemaOrgBuilderInterface {

  /**
   * Default canonical base URL (apex - www redirects here).
   */
  protected const CANONICAL_BASE = 'https://sahistory.org.za';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * Returns the canonical base URL, without a trailing slash.
   *
   * Overridable per environment via $settings['saho_canonical_url'].
   */
  protected function canonicalBaseUrl(): string {
    return rtrim(Settings::get('saho_canonical_url', static::CANONICAL_BASE), '/');
  }

  /**
   * Returns the node's canonical URL on the canonical host.
   */
  protected function canonicalNodeUrl(NodeInterface $node): string {
    return $this->canonicalBaseUrl() . $node->toUrl()->toString();
  }

  /**
   * Returns the node's SAHO display ref (e.g. "R-0123456"), if available.
   */
  protected function refCode(NodeInterface $node): ?string {
    if (!\Drupal::hasService('saho_refs.display_ref')) {
      return NULL;
    }
    $ref = \Drupal::service('saho_refs.display_ref')->getRef($node);
    return is_string($ref) && $ref !== '' ? $ref : NULL;
  }

  /**
   * Returns the permanent /ref/ URL for the node, if refs are available.
   */
  protected function refUrl(NodeInterface $node): ?string {
    $code = $this->refCode($node);
    return $code === NULL ? NULL : $this->canonicalBaseUrl() . '/ref/' . $code;
  }

  /**
   * Builds the shared identity properties for a node schema.
   *
   * @id is the permanent /ref/ URL (an opaque IRI - never fetched, so the
   * 301 it serves is irrelevant); url stays the canonical alias that matches
   * rel=canonical and the sitemap. Do not invert them.
   *
   * @return array
   *   Keys: @id, url, mainEntityOfPage, and identifier when a ref exists.
   */
  protected function identityProperties(NodeInterface $node): array {
    $url = $this->canonicalNodeUrl($node);
    $properties = [
      '@id' => $this->refUrl($node) ?? $url,
      'url' => $url,
      'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $url,
      ],
    ];
    $code = $this->refCode($node);
    if ($code !== NULL) {
      $properties['identifier'] = [
        '@type' => 'PropertyValue',
        'propertyID' => 'SAHO',
        'value' => $code,
      ];
    }
    return $properties;
  }

  /**
   * Returns the @id of the sitewide SAHO Organization entity.
   */
  protected function organizationId(): string {
    return $this->canonicalBaseUrl() . '/#organization';
  }

  /**
   * Returns an @id-linked stub for the SAHO organization.
   *
   * Keeps @type and name inline so literal-minded validators stay green
   * while consumers dedupe on the shared @id.
   *
   * @param string $type
   *   The schema.org type to present the stub as.
   *
   * @return array
   *   The stub reference.
   */
  protected function organizationRef(string $type = 'Organization'): array {
    return [
      '@id' => $this->organizationId(),
      '@type' => $type,
      'name' => 'South African History Online',
    ];
  }

  /**
   * Returns the @id of the sitewide WebSite entity.
   */
  protected function websiteId(): string {
    return $this->canonicalBaseUrl() . '/#website';
  }

}
