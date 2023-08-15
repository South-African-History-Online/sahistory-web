<?php

namespace Drupal\file_entity\Form;

use Drupal\Core\Archiver\ArchiverManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file_entity\UploadValidatorsTrait;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for archive type forms.
 */
class FileAddArchiveForm extends FormBase {

  use UploadValidatorsTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The archiver manager.
   *
   * @var \Drupal\Core\Archiver\ArchiverManager
   */
  protected $archiverManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(FileSystemInterface $file_system, MessengerInterface $messenger, ArchiverManager $archiver_manager) {
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
    $this->archiverManager = $archiver_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('messenger'),
      $container->get('plugin.manager.archiver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_add_archive_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [
      'file_extensions' => $this->archiverManager->getExtensions(),
    ];
    $options = $form_state->get('options') ? $form_state->get('options') : $options;
    $validators = $this->getUploadValidators($options);

    $form['upload'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('Upload an archive file'),
      '#upload_location' => 'public://',
      '#progress_indicator' => 'bar',
      '#default_value' => $form_state->has('file') ? array($form_state->get('file')->id()) : NULL,
      '#required' => TRUE,
      '#description' => $this->t('Files must be less than <strong>%valid_size</strong><br> Allowed file types: <strong>%valid_extension</strong>', array('%valid_size' => format_size($validators['file_validate_size'][0]), '%valid_extension' => $validators['file_validate_extensions'][0])),
      '#upload_validators' => $validators,
    );

    $form['pattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pattern'),
      '#description' => $this->t('Only files matching this pattern will be imported. For example, to import all jpg and gif files, the pattern would be <strong>.*jpg|.*gif</strong>. Use <strong>.*</strong> to extract all files in the archive.'),
      '#default_value' => '.*',
      '#required' => TRUE,
    );

    $form['remove_archive'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Remove archive'),
      '#description' => $this->t('Removes archive after extraction.'),
      '#default_value' => FALSE,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($archive = File::load($form_state->getValue('upload')[0])) {

      if ($archiver = $this->archiverManager->getInstance(['filepath' => $this->fileSystem->realpath($archive->getFileUri())])) {

        $extract_dir = $this->config('system.file')->get('default_scheme') . '://' . pathinfo($archive->getFilename(), PATHINFO_FILENAME);
        $extract_dir = $this->fileSystem->getDestinationFilename($extract_dir, FileSystemInterface::EXISTS_RENAME);
        if (!$this->fileSystem->prepareDirectory($extract_dir, FileSystemInterface::MODIFY_PERMISSIONS | FileSystemInterface::CREATE_DIRECTORY)) {
          throw new \Exception(t('Unable to prepare, the directory %dir for extraction.', array('%dir' => $extract_dir)));
        }
        $archiver->extract($extract_dir);
        $pattern = '/' . $form_state->getValue('pattern') . '/';
        if ($files = $this->fileSystem->scanDirectory($extract_dir, $pattern)) {
          foreach ($files as $file) {
            $file = File::create([
              'uri' => $file->uri,
              'filename' => $file->filename,
              'status' => FILE_STATUS_PERMANENT,
            ]);
            $file->save();
          }
          $all_files = $this->fileSystem->scanDirectory($extract_dir, '/.*/');
          // Get all files that don't match the pattern so we can remove them.
          $remainig_files = array_diff_key($all_files, $files);
          foreach ($remainig_files as $file) {
            $this->fileSystem->unlink($file->uri);
          }
        }
        $this->messenger->addMessage($this->t('Extracted %file and added @count new files.', array('%file' => $archive->getFilename(), '@count' => count($files))));
        if ($form_state->getValue('remove_archive')) {
          $this->messenger->addMessage($this->t('Archive %name was removed from the system.', array('%name' => $archive->getFilename())));
          $archive->delete();
        }
        else {
          $archive->setPermanent();
          $archive->save();
        }
      }
      else {
        $form_state->setErrorByName('', $this->t('Cannot extract %file, not a valid archive.', array('%file' => $archive->getFileUri())));
      }
    }
    $this->redirect('entity.file.collection')->send();
  }

}
