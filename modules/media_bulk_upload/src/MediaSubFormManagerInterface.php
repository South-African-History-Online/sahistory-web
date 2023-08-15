<?php
/**
 * Created by PhpStorm.
 * User: jeffreybertoen
 * Date: 05-02-19
 * Time: 07:33
 */

namespace Drupal\media_bulk_upload;


use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface;

/**
 * Class MediaSubFormManager.
 *
 * @package Drupal\media_bulk_upload
 */
interface MediaSubFormManagerInterface {

  /**
   * Get the target field settings for the media type.
   *
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   Media Type.
   *
   * @return string
   *   The directory location to store the files.
   */
  public function getTargetFieldDirectory(MediaTypeInterface $mediaType);

  /**
   * Get media entity form fields that are available in all given $mediaForms.
   *
   * @param array $form
   *   Render array containing the form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface $mediaBulkConfig
   *   The media bulk config entity.
   *
   * @return $this
   *   MediaSubFormManager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildMediaSubForm(array &$form, FormStateInterface $form_state, MediaBulkConfigInterface $mediaBulkConfig);

  /**
   * Get the media form display for the given media type.
   *
   * @param \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface $mediaBulkConfig
   *   Media bulk config entity.
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   The media type.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The media form display to get the field widgets from.
   */
  public function getMediaFormDisplay(MediaBulkConfigInterface $mediaBulkConfig, MediaTypeInterface $mediaType);

  /**
   * Get the form field components shared between the media types.
   *
   * @param \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface $mediaBulkConfig
   *   MediaBulkConfig.
   *
   * @return array
   *   The list of field names shared between the media types.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFields(MediaBulkConfigInterface $mediaBulkConfig);

  /**
   * Get the field components for the given media type.
   *
   * @param \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface $mediaBulkConfig
   *   The media bulk config entity.
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   The media type.
   *
   * @return array
   *   List of field components.
   */
  public function getMediaEntityFieldComponents(MediaBulkConfigInterface $mediaBulkConfig, MediaTypeInterface $mediaType);

  /**
   * Configure all the shared fields.
   *
   * Will set all the correct parents and make all the fields optional.
   *
   * @param array $elements
   *   Form elements from the media type form.
   * @param array $allowedFields
   *   Fields that are allowed to be shown in the media bulk upload form.
   *
   * @return $this
   *   MediaBulkUploadForm.
   */
  public function configureSharedFields(array &$elements, array $allowedFields);

  /**
   * Make sure the fields are optional, instead of required.
   *
   * @param array $elements
   *   The form elements to check the required settings on.
   *
   * @return $this
   *   MediaSubFormManager.
   */
  public function forceFieldsAsOptional(array &$elements);

  /**
   * Check if the media form fields should be used in the upload form.
   *
   * @param \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface $mediaBulkConfig
   *   The media bulk configuration entity.
   *
   * @return bool
   *   True if the media form fields should be used.
   */
  public function validateMediaFormDisplayUse(MediaBulkConfigInterface $mediaBulkConfig);

  /**
   * Get Media Type Manager.
   *
   * @return \Drupal\media_bulk_upload\MediaTypeManager
   *   Media Type Manager.
   */
  public function getMediaTypeManager();

  /**
   * Get the default max file size.
   *
   * @return string
   *   The default max file size.
   */
  public function getDefaultMaxFileSize();
}