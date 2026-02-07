<?php

namespace Drupal\saho_utils\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Service for unified image extraction from entities.
 *
 * Eliminates duplicate code across SAHO blocks by providing centralized
 * image extraction logic for various entity types and field configurations.
 */
class ImageExtractorService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Mapping of content types to their primary image field names.
   *
   * @var array
   */
  protected const CONTENT_TYPE_IMAGE_FIELDS = [
    'article' => 'field_article_image',
    'biography' => 'field_bio_pic',
    'event' => 'field_event_image',
    'place' => 'field_place_image',
    'default' => 'field_image',
  ];

  /**
   * Constructs an ImageExtractorService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $file_url_generator,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * Extract image URL from any entity.
   *
   * Handles FileInterface entities, Media entities with source plugins,
   * and direct file references. Auto-detects field name if not provided.
   *
   * @param mixed $entity
   *   The entity to extract the image from.
   * @param string|null $field_name
   *   Optional field name. If null, auto-detects based on content type.
   *
   * @return string|null
   *   The image URL or NULL if no image found.
   */
  public function extractImageUrl($entity, ?string $field_name = NULL): ?string {
    if (!$entity instanceof ContentEntityInterface) {
      return NULL;
    }

    // Handle FileInterface entities directly.
    if ($entity instanceof FileInterface) {
      return $this->fileUrlGenerator->generateAbsoluteString($entity->getFileUri());
    }

    // Auto-detect field name if not provided.
    if ($field_name === NULL) {
      $field_name = $this->findImageFieldForContentType($entity->bundle());
    }

    // Check if field exists and has a value.
    if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
      return NULL;
    }

    $field_value = $entity->get($field_name)->first();
    if (!$field_value) {
      return NULL;
    }

    // Handle Media reference fields.
    if ($field_value->getFieldDefinition()->getType() === 'entity_reference' &&
        $field_value->getFieldDefinition()->getSetting('target_type') === 'media') {
      $referenced_entity = $field_value->get('entity')->getValue();
      return $this->extractImageFromMedia($referenced_entity);
    }

    // Handle direct image fields.
    if ($field_value->getFieldDefinition()->getType() === 'image') {
      $file = $field_value->get('entity')->getValue();
      if ($file instanceof FileInterface) {
        return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    // Handle direct file reference fields.
    if ($field_value->getFieldDefinition()->getType() === 'entity_reference' &&
        $field_value->getFieldDefinition()->getSetting('target_type') === 'file') {
      $file = $field_value->get('entity')->getValue();
      if ($file instanceof FileInterface) {
        return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    return NULL;
  }

  /**
   * Extract image URL from a Media entity.
   *
   * @param \Drupal\media\MediaInterface|null $media
   *   The media entity.
   *
   * @return string|null
   *   The image URL or NULL if no image found.
   */
  protected function extractImageFromMedia(?MediaInterface $media): ?string {
    if (!$media) {
      return NULL;
    }

    // Get the source field from the media type.
    $source = $media->getSource();
    $source_field = $source->getConfiguration()['source_field'];

    if (!$media->hasField($source_field) || $media->get($source_field)->isEmpty()) {
      return NULL;
    }

    $field_item = $media->get($source_field)->first();
    if (!$field_item) {
      return NULL;
    }

    $file = $field_item->get('entity')->getValue();
    if ($file instanceof FileInterface) {
      return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    }

    return NULL;
  }

  /**
   * Get image URL with image style applied.
   *
   * Checks for WebP derivatives and falls back to original style.
   *
   * @param mixed $entity
   *   The entity to extract the image from.
   * @param string|null $style
   *   The image style name (e.g., 'large', 'medium').
   * @param string|null $field_name
   *   Optional field name. If null, auto-detects based on content type.
   *
   * @return string|null
   *   The styled image URL or NULL if no image found.
   */
  public function extractImageWithDerivatives($entity, ?string $style = NULL, ?string $field_name = NULL): ?string {
    if (!$entity instanceof ContentEntityInterface) {
      return NULL;
    }

    // Auto-detect field name if not provided.
    if ($field_name === NULL) {
      $field_name = $this->findImageFieldForContentType($entity->bundle());
    }

    // Check if field exists and has a value.
    if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
      return NULL;
    }

    $field_value = $entity->get($field_name)->first();
    if (!$field_value) {
      return NULL;
    }

    // Get the file entity.
    $file = NULL;

    // Handle Media reference fields.
    if ($field_value->getFieldDefinition()->getType() === 'entity_reference' &&
        $field_value->getFieldDefinition()->getSetting('target_type') === 'media') {
      $media = $field_value->get('entity')->getValue();
      if ($media instanceof MediaInterface) {
        $source = $media->getSource();
        $source_field = $source->getConfiguration()['source_field'];
        if ($media->hasField($source_field) && !$media->get($source_field)->isEmpty()) {
          $media_field_value = $media->get($source_field)->first();
          if ($media_field_value) {
            $file = $media_field_value->get('entity')->getValue();
          }
        }
      }
    }
    // Handle direct image fields.
    elseif ($field_value->getFieldDefinition()->getType() === 'image') {
      $file = $field_value->get('entity')->getValue();
    }
    // Handle direct file reference fields.
    elseif ($field_value->getFieldDefinition()->getType() === 'entity_reference' &&
            $field_value->getFieldDefinition()->getSetting('target_type') === 'file') {
      $file = $field_value->get('entity')->getValue();
    }

    if (!$file instanceof FileInterface) {
      return NULL;
    }

    // If no style specified, return original.
    if ($style === NULL) {
      return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    }

    // Try to load the image style.
    try {
      $image_style = $this->entityTypeManager->getStorage('image_style')->load($style);
      if (!$image_style) {
        // Style not found, return original.
        return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }

      // Check for WebP derivative first.
      $webp_style_name = $style . '_webp';
      $webp_style = $this->entityTypeManager->getStorage('image_style')->load($webp_style_name);
      if ($webp_style) {
        return $this->fileUrlGenerator->transformRelative(
          $webp_style->buildUrl($file->getFileUri())
        );
      }

      // Fall back to original style.
      return $this->fileUrlGenerator->transformRelative(
        $image_style->buildUrl($file->getFileUri())
      );
    }
    catch (\Exception $e) {
      // If anything fails, return original.
      return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    }
  }

  /**
   * Find the primary image field for a content type.
   *
   * Maps content types to their standard image field names.
   *
   * @param string $bundle
   *   The content type bundle (e.g., 'article', 'biography').
   *
   * @return string
   *   The image field name for this content type.
   */
  public function findImageFieldForContentType(string $bundle): string {
    return self::CONTENT_TYPE_IMAGE_FIELDS[$bundle] ?? self::CONTENT_TYPE_IMAGE_FIELDS['default'];
  }

  /**
   * Check if entity has an image.
   *
   * @param mixed $entity
   *   The entity to check.
   * @param string|null $field_name
   *   Optional field name. If null, auto-detects based on content type.
   *
   * @return bool
   *   TRUE if the entity has an image, FALSE otherwise.
   */
  public function hasImage($entity, ?string $field_name = NULL): bool {
    if (!$entity instanceof ContentEntityInterface) {
      return FALSE;
    }

    // FileInterface entities are images.
    if ($entity instanceof FileInterface) {
      return TRUE;
    }

    // Auto-detect field name if not provided.
    if ($field_name === NULL) {
      $field_name = $this->findImageFieldForContentType($entity->bundle());
    }

    // Check if field exists and has a value.
    if (!$entity->hasField($field_name)) {
      return FALSE;
    }

    return !$entity->get($field_name)->isEmpty();
  }

  /**
   * Extract image dimensions from an entity.
   *
   * @param mixed $entity
   *   The entity to extract dimensions from.
   * @param string|null $field_name
   *   Optional field name. If null, auto-detects based on content type.
   *
   * @return array|null
   *   Array with 'width' and 'height' keys, or NULL if unavailable.
   */
  public function extractImageDimensions($entity, ?string $field_name = NULL): ?array {
    if (!$entity instanceof ContentEntityInterface) {
      return NULL;
    }

    // Auto-detect field name if not provided.
    if ($field_name === NULL) {
      $field_name = $this->findImageFieldForContentType($entity->bundle());
    }

    // Check if field exists and has a value.
    if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
      return NULL;
    }

    $field_value = $entity->get($field_name)->first();
    if (!$field_value) {
      return NULL;
    }

    // For image fields, dimensions are stored directly.
    if ($field_value->getFieldDefinition()->getType() === 'image') {
      $width = $field_value->get('width')->getValue();
      $height = $field_value->get('height')->getValue();

      if ($width && $height) {
        return [
          'width' => (int) $width,
          'height' => (int) $height,
        ];
      }
    }

    // For media references, get dimensions from the source field.
    if ($field_value->getFieldDefinition()->getType() === 'entity_reference' &&
        $field_value->getFieldDefinition()->getSetting('target_type') === 'media') {
      $media = $field_value->get('entity')->getValue();
      if ($media instanceof MediaInterface) {
        $source = $media->getSource();
        $source_field = $source->getConfiguration()['source_field'];
        if ($media->hasField($source_field) && !$media->get($source_field)->isEmpty()) {
          $source_value = $media->get($source_field)->first();
          if ($source_value) {
            $width = $source_value->get('width')->getValue();
            $height = $source_value->get('height')->getValue();

            if ($width && $height) {
              return [
                'width' => (int) $width,
                'height' => (int) $height,
              ];
            }
          }
        }
      }
    }

    return NULL;
  }

}
