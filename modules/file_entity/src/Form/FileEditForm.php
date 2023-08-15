<?php

namespace Drupal\file_entity\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\file_entity\Entity\FileType;
use Drupal\file_entity\UploadValidatorsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for file type forms.
 */
class FileEditForm extends ContentEntityForm {

  use UploadValidatorsTrait;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $form */
    $form = parent::create($container);
    $form->renderer = $container->get('renderer');
    $form->fileSystem = $container->get('file_system');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEntity() {
    if ($this->entity->bundle() == FILE_TYPE_NONE) {
      $this->entity->updateBundle();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var FileInterface $file */
    $file = $this->entity;

    if ($this->operation == 'edit') {
      if ($file->bundle() == 'undefined') {
        $type = $this->t('file');
      }
      else {
        $type = FileType::load($file->bundle())->label();
      }

      $form['#title'] = $this->t('<em>Edit @type</em> "@title"', array(
        '@type' => $type,
        '@title' => $file->label(),
      ));

      // Add a 'replace this file' upload field if the file is writeable.
      if ($file->isWritable()) {
        // Set up replacement file validation.
        $replacement_options = array();
        // Replacement file must have the same extension as the original file.
        $replacement_options['file_extensions'] = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

        $form['replace_upload'] = array(
          '#type' => 'managed_file',
          '#title' => $this->t('Replace file'),
          '#upload_validators' => $this->getUploadValidators($replacement_options),
        );

        $file_upload_help = array(
          '#theme' => 'file_upload_help',
          '#description' => $this->t('This file will replace the existing file. This action cannot be undone.'),
          '#upload_validators' => $form['replace_upload']['#upload_validators'],
        );
        $form['replace_upload']['#description'] = $this->renderer->render($file_upload_help);
      }
    }

    return parent::form($form, $form_state, $file);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $file = $this->entity;
    $insert = $file->isNew();
    $file->save();

    $t_args = array('%title' => $file->label());

    if ($insert) {
      $this->messenger()->addMessage(t('%title has been created.', $t_args));
    }
    else {
      $this->messenger()->addMessage(t('%title has been updated.', $t_args));
    }

    // Check if file ID exists.
    if ($file->id()) {
      $form_state->setRedirectUrl($file->toUrl());
    }
    else {
      // In the unlikely case something went wrong on save, the file will be
      // rebuilt and file form redisplayed the same way as in preview.
      $this->messenger()->addMessage(t('The post could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Handle the replacement file if uploaded.
    if ($form_state->getValue('replace_upload')) {
      // Save the file as a temporary file.
      $file = file_save_upload('replace_upload', $form['replace_upload']['#upload_validators']);
      if (!empty($file)) {
        // Put the temporary file in form_state so we can save it on submit.
        $form_state->setValue('replace_upload', $file);
      }
      elseif ($file === FALSE) {
        // File uploaded failed.
        $form_state->setError($form['replace_upload'], t('The replacement file could not be uploaded.'));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Check if a replacement file has been uploaded.
    if ($form_state->getValue('replace_upload')) {
      $replacement = $form_state->getValue('replace_upload')[0];
      if ($replacement instanceof FileEntity) {
        $entity_replacement = $replacement;
      } else {
        $entity_replacement = File::load($replacement);
      }
      $log_args = array('@old' => $this->entity->getFilename(), '@new' => $entity_replacement->getFileName());
      // Move file from temp to permanent home.
      if ($this->fileSystem->copy($entity_replacement->getFileUri(), $this->entity->getFileUri(), FileSystemInterface::EXISTS_REPLACE)) {
        $entity_replacement->delete();
        \Drupal::logger('file_entity')->info('File @old was replaced by @new', $log_args);
      }
      else {
        \Drupal::logger('file_entity')->notice('File @old failed to be replaced by @new', $log_args);
      }
    }
    parent::submitForm($form, $form_state);
  }
}
