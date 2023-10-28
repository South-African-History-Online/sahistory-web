<?php

namespace Drupal\responsive_favicons\Form;

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Site\Settings;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ResponsiveFaviconsAdmin.
 *
 * @package Drupal\responsive_favicons\Form
 */
class ResponsiveFaviconsAdmin extends ConfigFormBase {

  use MessengerTrait;
  use StringTranslationTrait;

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
    $instance = parent::create($container);
    $instance->fileSystem = $container->get('file_system');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'responsive_favicons_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'responsive_favicons.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('responsive_favicons.settings');
    $form['path_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Favicons location'),
      '#description' => $this->t('Upload favicons using zip file from realfavicongenerator.net or provide path with location of the files.'),
      '#options' => [
        'upload' => $this->t('Upload zip file'),
        'path' => $this->t('Use internal path'),
      ],
      '#default_value' => $config->get('path_type'),
    ];
    $form['upload_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to responsive favicon files'),
      '#description' => $this->t('A local file system path where favicon files will be stored. This directory must exist and be writable by Drupal. An attempt will be made to create this directory if it does not already exist.'),
      '#field_prefix' => file_create_url('public://'),
      '#default_value' => $config->get('path'),
      '#states' => [
        'visible' => [
          ':input[name="path_type"]' => ['value' => 'upload'],
        ],
      ],
    ];
    $form['local_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to responsive favicon files'),
      '#description' => $this->t('A local file system path where favicon files are stored (e.g. <code>/themes/custom/your-theme/favicons</code>). This directory must exist, relative to your Drupal root and contain all required files.'),
      '#default_value' => $config->get('path'),
      '#field_prefix' => DRUPAL_ROOT,
      '#states' => [
        'visible' => [
          ':input[name="path_type"]' => ['value' => 'path'],
        ],
      ],
    ];
    $form['tags'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Favicon tags'),
      '#description' => $this->t('Paste the code provided by <a href="http://realfavicongenerator.net/" target="_blank">http://realfavicongenerator.net/</a>. Make sure each link is on a separate line. It is fine to paste links with paths like <code>/apple-touch-icon-57x57.png</code> as these will be converted to the correct paths automatically.'),
      '#default_value' => implode(PHP_EOL, $config->get('tags')),
      '#rows' => 16,
    ];
    $form['upload'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload a zip file from realfavicongenerator.net to install'),
      '#description' => $this->t('For example: %filename from your local computer. This only needs to be done once.', ['%filename' => 'favicons.zip']),
      '#states' => [
        'visible' => [
          ':input[name="path_type"]' => ['value' => 'upload'],
        ],
      ],
    ];
    $form['remove_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove default favicon from Drupal'),
      '#description' => $this->t('It is recommended to remove default favicon as it can cause issues'),
      '#default_value' => $config->get('remove_default'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $path_type = $form_state->getValue('path_type');
    if ($path_type === 'path') {
      $path = rtrim($form_state->getValue('local_path'));
      if (!is_dir(DRUPAL_ROOT . $path)) {
        $form_state->setErrorByName('local_path', $this->t('The directory %dir does not exist.', ['%dir' => $path]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('responsive_favicons.settings');

    // We want to save tags as an array.
    $tags = explode(PHP_EOL, $form_state->getValue('tags'));
    $tags = array_map('trim', $tags);
    $tags = array_filter($tags);
    $config->set('tags', $tags);

    // Get the favicons location type.
    $path_type = $form_state->getValue('path_type');
    $config->set('path_type', $path_type);

    // Local path.
    if ($path_type === 'path') {
      $path = rtrim($form_state->getValue('local_path'));
      $config->set('path', $path);
    }

    // Checkbox.
    $config->set('remove_default', $form_state->getValue('remove_default'));

    // If path type is upload handle uploaded zip file.
    if ($path_type === 'upload') {
      $path = rtrim($form_state->getValue('upload_path'));
      $config->set('path', $path);

      // Attempt the upload and extraction of the zip file. This code is largely
      // based on the code in Drupal core.
      //
      // @see UpdateManagerInstall->submitForm().
      $local_cache = NULL;
      if (!empty($_FILES['files']['name']['upload'])) {
        $validators = ['file_validate_extensions' => ['zip']];
        if (!($finfo = file_save_upload('upload', $validators, NULL, 0, FileSystemInterface::EXISTS_REPLACE))) {
          // Failed to upload the file. file_save_upload() calls
          // \Drupal\Core\Messenger\MessengerInterface::addError() on failure.
          return;
        }
        $local_cache = $finfo->getFileUri();
      }

      // Only execute the below if a file was uploaded.
      if (isset($local_cache)) {
        $directory = $this->extractDirectory();
        try {
          $archive = $this->archiveExtract($local_cache, $directory);
        }
        catch (\Exception $e) {
          $this->messenger()->addError($e->getMessage());
          return;
        }

        $files = $archive->listContents();
        if (!$files) {
          $form_state->setError($field, $this->t('Provided archive contains no files.'));
          return;
        }

        $destination = 'public://' . $path;
        $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);

        // Copy the files to the correct location.
        $success_count = 0;
        foreach ($files as $file) {
          // Handle exceptions when copy does not happen correctly.
          try {
            $success = $this->fileSystem->copy($directory . '/' . $file, $destination, FileSystemInterface::EXISTS_REPLACE);
          }
          catch (FileException $e) {
            $success = FALSE;
          }
          $uri = $destination . '/' . $file;
          if ($success) {
            $success_count++;
            // Handle exceptions when file contents are not saved correctly into
            // destination.
            try {
              // Rewrite the paths of the JSON files.
              if (preg_match('/\.json$/', $file)) {
                $file_contents = file_get_contents($this->fileSystem->realpath($uri));
                $find = preg_quote('"\/android-chrome', '/');
                $replace = '"' . str_replace('/', '\/', _responsive_favicons_normalise_path('/android-chrome'));
                $file_contents = preg_replace('/' . $find . '/', $replace, $file_contents);
                $this->fileSystem->saveData($file_contents, $uri, FileSystemInterface::EXISTS_REPLACE);
              }
              // Rewrite the paths of the XML files.
              elseif (preg_match('/\.xml$/', $file)) {
                $file_contents = file_get_contents($this->fileSystem->realpath($uri));
                $find = preg_quote('"/mstile', '/');
                $replace = '"' . _responsive_favicons_normalise_path('/mstile');
                $file_contents = preg_replace('/' . $find . '/', $replace, $file_contents);
                $this->fileSystem->saveData($file_contents, $uri, FileSystemInterface::EXISTS_REPLACE);
              }
              // Rewrite the paths of the WEBMANIFEST files.
              elseif (preg_match('/\.webmanifest$/', $file)) {
                $file_contents = file_get_contents($this->fileSystem->realpath($uri));
                $find = preg_quote('"/android-chrome', '/');
                $replace = '"' . _responsive_favicons_normalise_path('/android-chrome');
                $file_contents = preg_replace('/' . $find . '/', $replace, $file_contents);
                $this->fileSystem->saveData($file_contents, $uri, FileSystemInterface::EXISTS_REPLACE);
              }
            }
            catch (FileWriteException $e) {
              $this->messenger()->addError($this->t('The file could not be created.'));
            }
            catch (FileException $e) {
              $this->messenger()->addError($e->getMessage());
            }
          }
        }

        if ($success_count > 0) {
          $this->messenger()->addStatus($this->formatPlural($success_count, 'Uploaded 1 favicon file successfully.', 'Uploaded @count favicon files successfully.'));
        }
      }
    }

    // Save the settings.
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Returns a short unique identifier for this Drupal installation.
   *
   * @return string
   *   An eight character string uniquely identifying this Drupal installation.
   */
  private function uniqueIdentifier() {
    $id = &drupal_static(__FUNCTION__, '');
    if (empty($id)) {
      $id = substr(hash('sha256', Settings::getHashSalt()), 0, 8);
    }
    return $id;
  }

  /**
   * Gets the directory where responsive favicons zip files should be extracted.
   *
   * @param bool $create
   *   (optional) Whether to attempt to create the directory if it does not
   *   already exist. Defaults to TRUE.
   *
   * @return string
   *   The full path to the temporary directory where responsive favicons fil
   *   archives should be extracted.
   */
  private function extractDirectory($create = TRUE) {
    $directory = &drupal_static(__FUNCTION__, '');
    if (empty($directory)) {
      $directory = 'temporary://responsive-favicons-' . $this->uniqueIdentifier();
      if ($create && !file_exists($directory)) {
        mkdir($directory);
      }
    }
    return $directory;
  }

  /**
   * Unpacks a downloaded archive file.
   *
   * @param string $file
   *   The filename of the archive you wish to extract.
   * @param string $directory
   *   The directory you wish to extract the archive into.
   *
   * @return \Drupal\Core\Archiver\ArchiverInterface
   *   The Archiver object used to extract the archive.
   *
   * @throws \Exception
   */
  private function archiveExtract($file, $directory) {
    $archiver = \Drupal::service('plugin.manager.archiver')->getInstance(['filepath' => $file]);
    if (!$archiver) {
      throw new \Exception($this->t('Cannot extract %file, not a valid archive.', ['%file' => $file]));
    }

    if (file_exists($directory)) {
      $this->fileSystem->deleteRecursive($directory);
    }

    $archiver->extract($directory);
    return $archiver;
  }

}
