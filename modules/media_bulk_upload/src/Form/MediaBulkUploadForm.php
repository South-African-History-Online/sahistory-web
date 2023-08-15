<?php

namespace Drupal\media_bulk_upload\Form;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface;
use Drupal\media_bulk_upload\MediaSubFormManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkMediaUploadForm.
 *
 * @package Drupal\media_upload\Form
 */
class MediaBulkUploadForm extends FormBase {

  /**
   * Media Type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaTypeStorage;

  /**
   * Media Bulk Config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaBulkConfigStorage;

  /**
   * Media entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * File entity storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * Media SubForm Manager.
   *
   * @var \Drupal\media_bulk_upload\MediaSubFormManager
   */
  protected $mediaSubFormManager;

  /**
   * The max file size for the media bulk form.
   *
   * @var string
   */
  protected $maxFileSizeForm;

  /**
   * The allowed extensions for the media bulk form.
   *
   * @var array
   */
  private $allowed_extensions = [];

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * BulkMediaUploadForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\media_bulk_upload\MediaSubFormManager $mediaSubFormManager
   *   Media Sub Form Manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current User.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MediaSubFormManager $mediaSubFormManager, AccountProxyInterface $currentUser) {
    $this->mediaTypeStorage = $entityTypeManager->getStorage('media_type');
    $this->mediaBulkConfigStorage = $entityTypeManager->getStorage('media_bulk_config');
    $this->mediaStorage = $entityTypeManager->getStorage('media');
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->maxFileSizeForm = ini_get('upload_max_filesize');
    $this->mediaSubFormManager = $mediaSubFormManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('media_bulk_upload.subform_manager'),
      $container->get('current_user')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'media_bulk_upload_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface|null $media_bulk_config
   *   The media bulk configuration entity.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state, MediaBulkConfigInterface $media_bulk_config = NULL) {
    $mediaBulkConfig = $media_bulk_config;

    if ($mediaBulkConfig === NULL) {
      return $form;
    }

    $mediaTypeManager = $this->mediaSubFormManager->getMediaTypeManager();
    $mediaTypes = $this->mediaSubFormManager->getMediaTypeManager()->getBulkMediaTypes($mediaBulkConfig);
    $mediaTypeLabels = [];

    foreach ($mediaTypes as $mediaType) {
      $extensions = $mediaTypeManager->getMediaTypeExtensions($mediaType);
      natsort($extensions);
      $this->addAllowedExtensions($extensions);

      $maxFileSize = $mediaTypeManager->getTargetFieldMaxSize($mediaType);
      if (empty($maxFileSize)) {
        $maxFileSize = $this->mediaSubFormManager->getDefaultMaxFileSize();
      }

      $mediaTypeLabels[] = $mediaType->label() . ' (max ' . $maxFileSize . '): ' . implode(', ', $extensions);
      if ($this->isMaxFileSizeLarger($maxFileSize)) {
        $this->setMaxFileSizeForm($maxFileSize);
      }
    }

    $form['#tree'] = TRUE;
    $form['information_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'media-bulk-upload-information-wrapper',
        ],
      ],
    ];
    $form['information_wrapper']['information_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $this->t('Information'),
      '#attributes' => [
        'class' => [
          'form-control-label',
        ],
        'for' => 'media_bulk_upload_information',
      ],
    ];

    $form['information_wrapper']['information'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('Media Types:'),
      '#items' => $mediaTypeLabels,
    ];

    $form['information_wrapper']['warning'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#id' => 'media_bulk_upload_information',
      '#name' => 'media_bulk_upload_information',
      '#value' => '<p>Please be 
        aware that if file extensions overlap between the media types that are 
        available in this upload form, that the media entity will be assigned 
        automatically to one of these types.</p>',
    ];

    $form['dropzonejs'] = [
      '#type' => 'dropzonejs',
      '#title' => $this->t('Dropzone'),
      '#required' => TRUE,
      '#dropzone_description' => $this->t('Click or drop your files here'),
      '#max_filesize' => $this->maxFileSizeForm,
      '#extensions' => implode(' ', $this->allowed_extensions),
    ];


    if ($this->mediaSubFormManager->validateMediaFormDisplayUse($mediaBulkConfig)) {
      $form['fields'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Fields'),
        'shared' => [],
      ];
      $this->mediaSubFormManager->buildMediaSubForm($form, $form_state, $mediaBulkConfig);
    }

    $form['media_bundle_config'] = [
      '#type' => 'value',
      '#value' => $mediaBulkConfig->id(),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Add allowed extensions.
   *
   * @param array $extensions
   *   Allowed Extensions.
   *
   * @return $this
   *   MediaBulkUploadForm.
   */
  private function addAllowedExtensions(array $extensions) {
    $this->allowed_extensions = array_unique(array_merge($this->allowed_extensions, $extensions));
    return $this;
  }

  /**
   * Validate if a max file size is bigger then the current max file size.
   *
   * @param string $MaxFileSize
   *   File Size.
   *
   * @return bool
   *  TRUE if the given size is larger than the one that is set.
   */
  private function isMaxFileSizeLarger($MaxFileSize) {
    $size = Bytes::toInt($MaxFileSize);
    $currentSize = Bytes::toInt($this->maxFileSizeForm);

    return ($size > $currentSize);
  }

  /**
   * Set the max File size for the form.
   *
   * @param string $newMaxFileSize
   *   File Size.
   *
   * @return $this
   *   MediaBulkUploadForm.
   */
  private function setMaxFileSizeForm($newMaxFileSize) {
    $this->maxFileSizeForm = $newMaxFileSize;
    return $this;
  }

  /**
   * Submit handler to create the file entities and media entities.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $mediaBundleConfigId = $values['media_bundle_config'];

    /** @var MediaBulkConfigInterface $mediaBulkConfig */
    $mediaBulkConfig = $this->mediaBulkConfigStorage->load($mediaBundleConfigId);
    $files = $values['dropzonejs']['uploaded_files'];

    $mediaTypes = $this->mediaSubFormManager->getMediaTypeManager()->getBulkMediaTypes($mediaBulkConfig);
    $mediaType = reset($mediaTypes);
    $mediaFormDisplay = $this->mediaSubFormManager->getMediaFormDisplay($mediaBulkConfig, $mediaType);

    $this->prepareFormValues($form_state);

    $savedMediaItems = [];
    foreach ($files as $file) {
      try {
        $media = $this->processFile($mediaBulkConfig, $file);
        if (!$media) {
          continue;
        }
        if ($this->mediaSubFormManager->validateMediaFormDisplayUse($mediaBulkConfig)) {
          $extracted = $mediaFormDisplay->extractFormValues($media, $form['fields']['shared'], $form_state);
          $this->copyFormValuesToEntity($media, $extracted, $form_state);
        }
        $media->save();
        $savedMediaItems[] = $media;
      } catch (\Exception $e) {
        watchdog_exception('media_bulk_upload', $e);
      }
    }

    if (!empty($savedMediaItems)) {
      $this->messenger()->addStatus($this->t('@count media item(s) are created.', ['@count' => count($savedMediaItems)]));
    }
  }

  /**
   * Process a file upload.
   *
   * Will create a file entity and prepare a media entity with data.
   *
   * @param \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface $mediaBulkConfig
   *   Media Bulk Config.
   * @param array $file
   *   File upload data.
   *
   * @return \Drupal\media\MediaInterface
   *   The unsaved media entity that is created.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  private function processFile(MediaBulkConfigInterface $mediaBulkConfig, array $file) {
    $fileInfo = pathinfo($file['filename']);
    $filename = $fileInfo['basename'];

    if (!$this->validateFilename($fileInfo)) {
      $this->messenger()->addError($this->t('File :filename does not have a valid extension or filename.', [':filename' => $filename]));
      throw new \Exception("File $filename does not have a valid extension or filename.");
    }

    $allowedMediaTypes = $this->mediaSubFormManager->getMediaTypeManager()
      ->getBulkMediaTypes($mediaBulkConfig);
    $matchingMediaTypes = $this->mediaSubFormManager->getMediaTypeManager()
      ->getMediaTypeIdsByFileExtension($fileInfo['extension']);

    $mediaTypes = array_intersect_key($matchingMediaTypes, $allowedMediaTypes);
    $mediaType = reset($mediaTypes);

    if (!$this->validateFileSize($mediaType, $file['path'])) {
      $fileSizeSetting = $this->mediaSubFormManager->getMediaTypeManager()
        ->getTargetFieldMaxSize($mediaType);
      $mediaTypeLabel = $mediaType->label();
      $this->messenger()
        ->addError($this->t('File :filename exceeds the maximum file size of :file_size for media type :media_type exceeded.', [
          ':filename' => $filename,
          ':file_size' => $fileSizeSetting,
          ':media_type' => $mediaTypeLabel,
        ]));
      throw new \Exception("File $filename exceeds the maximum file size of $fileSizeSetting for media type $mediaTypeLabel exceeded.");
    }

    $destination = $this->mediaSubFormManager->getTargetFieldDirectory($mediaType) . '/' . $file['filename'];

    /** @var \Drupal\file\FileInterface $fileEntity */
    $fileEntity = $this->fileStorage->create([
      'uri' => $file['path'],
      'uid' => $this->currentUser->id(),
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $fileEntity->save();

    file_move($fileEntity, $destination);

    if (!$fileEntity) {
      $this->messenger()->addError($this->t('File :filename could not be created.', [':filename' => $filename]), 'error');
      throw new \Exception('File entity could not be created.');
    }

    $values = $this->getNewMediaValues($mediaType, $fileInfo, $fileEntity);
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->mediaStorage->create($values);
    return $media;
  }

  /**
   * Validate if the filename and extension are valid in the provided file info.
   *
   * @param array $fileInfo
   *   File info.
   *
   * @return bool
   *   If the file info validates, returns true.
   */
  private function validateFilename(array $fileInfo) {
    return !(empty($fileInfo['filename']) || empty($fileInfo['extension']));
  }

  /**
   * Check the size of a file.
   *
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   Media Type.
   * @param string $filePath
   *   File path.
   *
   * @return bool
   *   True if max size for a given file do not exceeds max size for its type.
   */
  private function validateFileSize(MediaTypeInterface $mediaType, $filePath) {
    $fileSizeSetting = $this->mediaSubFormManager->getMediaTypeManager()->getTargetFieldMaxSize($mediaType);
    $fileSize = filesize($filePath);
    $maxFileSize = !empty($fileSizeSetting)
      ? Bytes::toInt($fileSizeSetting)
      : file_upload_max_size();

    if ($maxFileSize == 0) {
      return true;
    }

    return $fileSize <= $maxFileSize;
  }

  /**
   * Builds the array of all necessary info for the new media entity.
   *
   * @param \Drupal\media\MediaTypeInterface $mediaType
   *   Media Type ID.
   * @param array $fileInfo
   *   File info.
   * @param \Drupal\file\FileInterface $file
   *   File entity.
   *
   * @return array
   *   Return an array describing the new media entity.
   */
  private function getNewMediaValues(MediaTypeInterface $mediaType, array $fileInfo, FileInterface $file) {
    $targetFieldName = $this->mediaSubFormManager->getMediaTypeManager()
      ->getTargetFieldName($mediaType);
    return [
      'bundle' => $mediaType->id(),
      'name' => $fileInfo['filename'],
      $targetFieldName => [
        'target_id' => $file->id(),
        'title' => $fileInfo['filename'],
      ],
    ];
  }

  /**
   * Copy the submitted values for the media subform to the media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media Entity.
   * @param array $extracted
   *   Extracted entity values.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   */
  private function copyFormValuesToEntity(MediaInterface $media, array $extracted, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $name => $values) {
      if (isset($extracted[$name]) || !$media->hasField($name)) {
        continue;
      }
      $media->set($name, $values);
    }
  }

  /**
   * Prepare form submitted values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   *
   * @return $this
   *   Media Bulk Upload Form.
   */
  private function prepareFormValues(FormStateInterface $form_state) {
    // If the shared name is empty, remove it from the form state.
    // Otherwise the extractFormValues function will override with an empty value.
    $shared = $form_state->getValue(['fields', 'shared']);
    if (empty($shared['name'][0]['value'])) {
      unset($shared['name']);
      $form_state->setValue(['fields', 'shared'], $shared);
    }
    return $this;
  }
}
