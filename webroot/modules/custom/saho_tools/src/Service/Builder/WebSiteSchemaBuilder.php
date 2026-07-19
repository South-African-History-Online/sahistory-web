<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\node\NodeInterface;

/**
 * Builds Schema.org WebSite structured data for SAHO site-wide.
 *
 * Emits a WebSite object with a SearchAction potentialAction so search
 * engines can surface the sitelinks search box for SAHO. The SearchAction
 * target points at the canonical Search API view (/search) and its exposed
 * fulltext filter parameter (search_api_fulltext).
 */
class WebSiteSchemaBuilder extends SchemaBuilderBase {

  /**
   * The canonical production base URL for SAHO.
   *
   * The apex host: www.sahistory.org.za 301s to it (verified live), and it
   * matches the Sitemap host advertised in robots.txt. Kept as a public
   * const because LlmTxtController also reads it; the schema output itself
   * goes through canonicalBaseUrl() so $settings['saho_canonical_url'] can
   * override per environment.
   */
  const CANONICAL_URL = 'https://sahistory.org.za/';

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
    $base_url = $this->canonicalBaseUrl();
    return [
      '@context' => 'https://schema.org',
      '@type' => 'WebSite',
      '@id' => $this->websiteId(),
      'url' => $base_url . '/',
      'name' => 'South African History Online',
      'alternateName' => 'SAHO',
      'publisher' => $this->organizationRef(),
      'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => [
          '@type' => 'EntryPoint',
          'urlTemplate' => $base_url . '/search?search_api_fulltext={search_term_string}',
        ],
        'query-input' => 'required name=search_term_string',
      ],
    ];
  }

}
