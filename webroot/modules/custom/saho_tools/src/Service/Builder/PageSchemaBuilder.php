<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org WebPage structured data for Page nodes.
 *
 * Maps SAHO standard pages to Schema.org WebPage vocabulary
 * for optimal discovery by search engines and AI systems.
 */
class PageSchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * Constructs a PageSchemaBuilder.
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
    return $node_type === 'page';
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

    // Add main image from field_image.
    $image_url = $this->getImageUrl($node, ['field_image']);
    if ($image_url) {
      $schema['image'] = [
        '@type' => 'ImageObject',
        'url' => $image_url,
        'contentUrl' => $image_url,
      ];
    }

    // Add publisher (SAHO organization).
    $schema['publisher'] = $this->getPublisherSchema();

    // Add breadcrumb.
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

    // Add accessibility and language properties.
    $schema['isAccessibleForFree'] = TRUE;
    $schema['inLanguage'] = 'en-ZA';

    return $schema;
  }

  /**
   * Get image URL from node image fields.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param array $field_names
   *   Array of field names to check in priority order.
   *
   * @return string|null
   *   The absolute image URL or NULL if not found.
   */
  protected function getImageUrl(NodeInterface $node, array $field_names): ?string {
    foreach ($field_names as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        $field_value = $node->get($field_name);

        // Handle file fields.
        if ($field_value->entity instanceof File) {
          return $this->fileUrlGenerator->generateAbsoluteString($field_value->entity->getFileUri());
        }

        // Handle media entity reference fields.
        if ($field_value->entity instanceof ContentEntityInterface && $field_value->entity->hasField('field_media_image')) {
          $media_entity = $field_value->entity;
          if (!$media_entity->get('field_media_image')->isEmpty()) {
            $file_entity = $media_entity->get('field_media_image')->entity;
            if ($file_entity instanceof File) {
              return $this->fileUrlGenerator->generateAbsoluteString($file_entity->getFileUri());
            }
          }
        }
      }
    }

    return NULL;
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
