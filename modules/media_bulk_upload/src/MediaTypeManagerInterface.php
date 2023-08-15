<?php

namespace Drupal\media_bulk_upload;


use Drupal\media\MediaTypeInterface;
use Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface;

/**
 * Interface MediaTypeManagerInterface
 *
 * @package Drupal\media_bulk_upload
 */
interface MediaTypeManagerInterface {

  /**
   * Get the Media Type extensions.
   *
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   Media Type.
   *
   * @return array
   *   Target Field Settings.
   */
  public function getMediaTypeExtensions(MediaTypeInterface $mediaType);

  /**
   * Return the media type target field.
   *
   * @param array $targetFieldSettings
   *   Target field settings.
   *
   * @return array
   *   The allowed file extensions.
   */
  public function getTargetFieldExtensions(array $targetFieldSettings);

  /**
   * Get the target field settings for the media type.
   *
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   Media Type.
   *
   * @return array
   *   The field settings.
   */
  public function getTargetFieldSettings(MediaTypeInterface $mediaType);

  /**
   * Get the target field name.
   *
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   Media Type.
   *
   * @return string
   *   Name of the field.
   */
  public function getTargetFieldName(MediaTypeInterface $mediaType);

  /**
   * Get the media types for a specific file extension.
   *
   * @param string $extension
   *   File extension.
   *
   * @return \Drupal\media\MediaTypeInterface[]
   *   Media Types
   *
   * @throws \Exception
   */
  public function getMediaTypeIdsByFileExtension($extension);

  /**
   * Get the target maximum upload size.
   *
   * Gets the maximum upload size for a file compared to the current
   * $maxFileSize, from the media type.
   *
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   Media Type.
   *
   * @return string
   *   Returns the max file size as a string.
   */
  public function getTargetFieldMaxSize(MediaTypeInterface $mediaType);

  /**
   * Get Media Types configured for the Media Bulk Upload Form.
   *
   * @param \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface $mediaBulkConfig
   *   Media Bulk Config.
   *
   * @return \Drupal\media\MediaTypeInterface[]
   *   Media Types.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getBulkMediaTypes(MediaBulkConfigInterface $mediaBulkConfig);
}