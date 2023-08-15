<?php

namespace Drupal\media_entity_bulk_upload\Services;

use Drupal\Core\Archiver\ArchiverManager;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Language\LanguageDefault;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * A service to unpack the .zip file and create media entities.
 */
class ZipUploadService {

  /**
   * The Drupal File System.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The Archive Manager to handle .zip files.
   *
   * @var \Drupal\Core\Archiver\ArchiverManager
   */
  protected $archiver;

  /**
   * The uploaded zip file from the form.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $zipFile;

  /**
   * The directory of the unzipped file.
   *
   * @var string
   */
  protected $unzipped;

  /**
   * This Drupals instance default language.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $language;

  /**
   * Constructor.
   */
  public function __construct(FileSystem $file_system, ArchiverManager $archiver, LanguageDefault $language) {
    $this->fileSystem = $file_system;
    $this->archiver = $archiver;
    $this->language = $language;
  }

  /**
   * Unzipping handler to extract images.
   */
  protected function unzip() {
    $zip = $this->archiver->getInstance(['filepath' => $this->zipFile]);
    $success = $zip->extract($this->unzipped);
  }

  /**
   * Upload handler.
   *
   * @param string $base_path
   *   The temp directory base path.
   * @param \Drupal\file\Entity\File $zipFile
   *   The uploaded .zip file.
   * @param string $bundle
   *   The media entities intended bundle.
   * @param string $field
   *   The media entities image field.
   */
  public function uploadMedia($base_path, File $zipFile, $bundle, $field) {
    $uploaded_files = [];
    $this->zipFile = $this->fileSystem->realpath($zipFile->getFileUri());
    $this->unzipped = $this->fileSystem->realpath($base_path . date('Y-m-d-H-m-s'));
    $this->unzip();
    $dir_r = new \DirectoryIterator($this->unzipped);
    foreach ($dir_r as $fileinfo) {
      if (!$fileinfo->isDot()) {
        $file_name = $fileinfo->getFilename();
        $handle = fopen($fileinfo->getPathname(), 'r');
        $file = file_save_data($handle, 'public://' . $file_name);
        fclose($handle);
        if ($file !== FALSE) {
          $image_media = Media::create([
            'bundle' => $bundle,
            'uid' => \Drupal::currentUser()->id(),
            'langcode' => $this->language->get()->getId(),
            'published' => TRUE,
            $field => [
              'target_id' => $file->id(),
              'alt' => $file_name,
            ],
          ]);
          $image_media->save();
          array_push($uploaded_files, $image_media);
        }
      }
    }
    file_unmanaged_delete_recursive($base_path);
    return $uploaded_files;
  }

}
