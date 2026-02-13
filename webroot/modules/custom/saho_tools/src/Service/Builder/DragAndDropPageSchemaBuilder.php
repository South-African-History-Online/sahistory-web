<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org WebPage structured data for Drag and Drop Page nodes.
 *
 * Maps SAHO landing pages and homepage to Schema.org WebPage vocabulary
 * for optimal discovery by search engines and AI systems.
 */
class DragAndDropPageSchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * Constructs a DragAndDropPageSchemaBuilder.
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
    return $node_type === 'drag_and_drop_page';
  }

  /**
   * {@inheritdoc}
   */
  public function build(NodeInterface $node): array {
    if (!$this->supports($node->getType())) {
      return [];
    }

    $schema = [
      '@context' => 'https://schema.org',
      '@type' => 'WebPage',
      'name' => $node->getTitle(),
      'url' => $node->toUrl()->setAbsolute()->toString(),
      'datePublished' => date('c', $node->getCreatedTime()),
      'dateModified' => date('c', $node->getChangedTime()),
    ];

    // Add description from body field.
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $node->get('body')->value;
      // Strip HTML tags and truncate for description.
      $description = strip_tags($body);
      // Limit to 200 characters for meta description.
      if (strlen($description) > 200) {
        $description = substr($description, 0, 197) . '...';
      }
      $schema['description'] = $description;
    }

    // Add main entity of page (SAHO organization).
    $schema['mainEntity'] = $this->getPublisherSchema();

    // Add publisher (SAHO organization).
    $schema['publisher'] = $this->getPublisherSchema();

    // Add breadcrumb if not the homepage.
    // Homepage typically doesn't have breadcrumbs.
    $is_front = \Drupal::service('path.matcher')->isFrontPage();
    if (!$is_front) {
      $schema['breadcrumb'] = [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
          [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => \Drupal::request()->getSchemeAndHttpHost(),
          ],
          [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $node->getTitle(),
            'item' => $node->toUrl()->setAbsolute()->toString(),
          ],
        ],
      ];
    }

    // Add accessibility and language properties.
    $schema['isAccessibleForFree'] = TRUE;
    $schema['inLanguage'] = 'en-ZA';

    // Add specialty property for educational content.
    $schema['specialty'] = 'South African History';

    return $schema;
  }

  /**
   * Get SAHO publisher schema.
   *
   * @return array
   *   Publisher organization schema.
   */
  protected function getPublisherSchema(): array {
    $request = \Drupal::request();
    $base_url = $request->getSchemeAndHttpHost();

    return [
      '@type' => 'Organization',
      'name' => 'South African History Online',
      'url' => $base_url,
      'logo' => [
        '@type' => 'ImageObject',
        'url' => $base_url . '/themes/custom/saho/logo.png',
      ],
    ];
  }

}
