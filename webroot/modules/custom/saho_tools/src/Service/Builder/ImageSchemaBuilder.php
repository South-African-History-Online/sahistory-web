<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org ImageObject structured data for Image nodes.
 *
 * Maps SAHO image/gallery content to Schema.org ImageObject
 * vocabulary for optimal image discovery and attribution.
 */
class ImageSchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * Constructs an ImageSchemaBuilder.
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
    return $node_type === 'image' || $node_type === 'gallery_image';
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
      '@type' => 'ImageObject',
      'name' => $node->getTitle(),
      'url' => $node->toUrl()->setAbsolute()->toString(),
    ];

    // Get the image field based on node type.
    $image_field = $node->getType() === 'gallery_image' ? 'field_gallery_image' : 'field_image';

    if ($node->hasField($image_field) && !$node->get($image_field)->isEmpty()) {
      $field_item = $node->get($image_field)->first();
      // @phpstan-ignore-next-line
      if ($field_item && $field_item->entity instanceof File) {
        // @phpstan-ignore-next-line
        $file = $field_item->entity;
        $image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());

        $schema['contentUrl'] = $image_url;
        $schema['encodingFormat'] = $file->getMimeType();
        $schema['fileFormat'] = $file->getMimeType();

        // Add width and height if available.
        $width = $field_item->get('width')->getValue();
        if ($width) {
          $schema['width'] = (int) $width;
        }
        $height = $field_item->get('height')->getValue();
        if ($height) {
          $schema['height'] = (int) $height;
        }

        // Add alt text as caption.
        $alt = $field_item->get('alt')->getValue();
        if ($alt) {
          $schema['caption'] = $alt;
        }
      }
    }

    // Add description from body or caption field.
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $node->get('body')->value;
      $description = strip_tags($body);
      if (strlen($description) > 300) {
        $description = substr($description, 0, 297) . '...';
      }
      $schema['description'] = $description;
    }

    // Add creator/photographer if available.
    if ($node->hasField('field_photographer') && !$node->get('field_photographer')->isEmpty()) {
      $schema['creator'] = [
        '@type' => 'Person',
        'name' => $node->get('field_photographer')->value,
      ];
    }
    elseif ($node->hasField('field_author') && !$node->get('field_author')->isEmpty()) {
      $schema['creator'] = [
        '@type' => 'Person',
        'name' => $node->get('field_author')->value,
      ];
    }

    // Add copyright holder.
    $schema['copyrightHolder'] = [
      '@type' => 'Organization',
      'name' => 'South African History Online',
    ];

    // Add license.
    $schema['license'] = 'https://creativecommons.org/licenses/by-nc-sa/4.0/';
    $schema['isAccessibleForFree'] = TRUE;

    // Add IPTC Photo Metadata properties (schema.org 2020 update).
    $request = \Drupal::request();
    $base_url = $request->getSchemeAndHttpHost();

    $schema['acquireLicensePage'] = $base_url . '/about/copyright-licensing';

    $schema['copyrightNotice'] = '© ' . date('Y') . ' South African History Online. Licensed under CC BY-NC-SA 4.0.';

    // Add creditText based on creator.
    if (isset($schema['creator'])) {
      $creator_name = $schema['creator']['name'];
      $schema['creditText'] = 'Photo by ' . $creator_name . ' / SAHO';
    }
    else {
      $schema['creditText'] = 'South African History Online (SAHO)';
    }

    return $schema;
  }

}
