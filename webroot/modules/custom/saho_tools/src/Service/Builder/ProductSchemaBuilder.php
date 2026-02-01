<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org Book/Product structured data for Product nodes.
 *
 * Maps SAHO product content (books, publications) to Schema.org
 * Book/Product vocabulary for e-commerce and catalog discovery.
 */
class ProductSchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * Constructs a ProductSchemaBuilder.
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
    return $node_type === 'product';
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
      '@type' => 'Book',
      'name' => $node->getTitle(),
      'url' => $node->toUrl()->setAbsolute()->toString(),
    ];

    // Add description.
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $node->get('body')->value;
      $description = strip_tags($body);
      if (strlen($description) > 500) {
        $description = substr($description, 0, 497) . '...';
      }
      $schema['description'] = $description;
    }

    // Add product image.
    if ($node->hasField('field_product_image') && !$node->get('field_product_image')->isEmpty()) {
      $field_value = $node->get('field_product_image');
      if ($field_value->entity instanceof File) {
        $image_url = $this->fileUrlGenerator->generateAbsoluteString($field_value->entity->getFileUri());
        $schema['image'] = [
          '@type' => 'ImageObject',
          'url' => $image_url,
          'contentUrl' => $image_url,
        ];
      }
    }

    // Add ISBN if available.
    if ($node->hasField('field_isbn') && !$node->get('field_isbn')->isEmpty()) {
      $schema['isbn'] = $node->get('field_isbn')->value;
    }

    // Add author if available.
    if ($node->hasField('field_author') && !$node->get('field_author')->isEmpty()) {
      $authors = [];
      foreach ($node->get('field_author') as $author) {
        if (!empty($author->value)) {
          $authors[] = [
            '@type' => 'Person',
            'name' => $author->value,
          ];
        }
      }
      if (!empty($authors)) {
        $schema['author'] = count($authors) === 1 ? $authors[0] : $authors;
      }
    }

    // Add publisher if available.
    if ($node->hasField('field_publisher') && !$node->get('field_publisher')->isEmpty()) {
      $schema['publisher'] = [
        '@type' => 'Organization',
        'name' => $node->get('field_publisher')->value,
      ];
    }

    // Add publication date.
    if ($node->hasField('field_publication_date') && !$node->get('field_publication_date')->isEmpty()) {
      $schema['datePublished'] = $node->get('field_publication_date')->value;
    }

    // Add inLanguage.
    $schema['inLanguage'] = 'en-ZA';

    return $schema;
  }

}
