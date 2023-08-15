<?php

namespace Drupal\media_bulk_upload;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface;

/**
 * Class MediaTypeManager
 *
 * @package Drupal\media_bulk_upload
 */
class MediaTypeManager implements MediaTypeManagerInterface {

  /**
   * List of media types, grouped by file extension.
   *
   * @var array
   */
  protected $mediaTypeExtensions;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * MediaTypeMatcher constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity Field Manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    /** @var \Drupal\media\MediaTypeInterface[] $mediaTypes */
    $mediaTypes = $entityTypeManager->getStorage('media_type')->loadMultiple();

    $this->groupMediaTypes($mediaTypes);
  }

  /**
   * Group media types by extension.
   *
   * @param \Drupal\media\MediaTypeInterface[] $mediaTypes
   *   Media Types.
   *
   * @return $this
   *   Media Type Manager.
   */
  private function groupMediaTypes($mediaTypes) {
    foreach ($mediaTypes as $mediaType) {
      $this->processMediaTypeExtensions($mediaType);
    }

    return $this;
  }

  /**
   * Process the extensions belonging to the media type.
   *
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   Media Type.
   *
   * @return $this
   *   Media Type Manager.
   */
  private function processMediaTypeExtensions(MediaTypeInterface $mediaType) {
    foreach ($this->getMediaTypeExtensions($mediaType) as $extension) {
      $this->mediaTypeExtensions[$extension][$mediaType->id()] = $mediaType;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMediaTypeExtensions(MediaTypeInterface $mediaType) {
    return $this->getTargetFieldExtensions($this->getTargetFieldSettings($mediaType));
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetFieldExtensions(array $targetFieldSettings) {
    $extensions = explode(' ', $targetFieldSettings['file_extensions']);
    return array_map('trim', $extensions);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetFieldSettings(MediaTypeInterface $mediaType) {
    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions('media', $mediaType->id());
    $targetFieldName = $this->getTargetFieldName($mediaType);

    /** @var \Drupal\field\Entity\FieldConfig $targetField */
    $targetField = $fieldDefinitions[$targetFieldName];
    return $targetField->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetFieldName(MediaTypeInterface $mediaType) {
    return $mediaType->getSource()->getConfiguration()['source_field'];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function getMediaTypeIdsByFileExtension($extension) {
    if (!isset($this->mediaTypeExtensions[$extension])) {
      throw new \Exception('No matching media type id for the given file.');
    }
    return $this->mediaTypeExtensions[$extension];
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetFieldMaxSize(MediaTypeInterface $mediaType) {
    $targetFieldSettings = $this->getTargetFieldSettings($mediaType);
    return $targetFieldSettings['max_filesize'];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBulkMediaTypes(MediaBulkConfigInterface $mediaBulkConfig) {
    $mediaTypeIds = $mediaBulkConfig->get('media_types');
    return $this->entityTypeManager->getStorage('media_type')
      ->loadMultiple($mediaTypeIds);
  }

}