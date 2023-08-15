<?php

namespace Drupal\imce\Plugin\ImcePlugin;

use Drupal\file\Entity\FileInterface;
use Drupal\imce\ImcePluginBase;
use Drupal\imce\ImceFM;

/**
 * Defines Imce Upload plugin.
 *
 * @ImcePlugin(
 *   id = "upload",
 *   label = "Upload",
 *   weight = -10,
 *   operations = {
 *     "upload" = "opUpload"
 *   }
 * )
 */
class Upload extends ImcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function permissionInfo() {
    return [
      'upload_files' => $this->t('Upload files'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPage(array &$page, ImceFM $fm) {
    if ($fm->hasPermission('upload_files')) {
      $page['#attached']['library'][] = 'imce/drupal.imce.upload';
    }
  }

  /**
   * Operation handler: upload.
   */
  public function opUpload(ImceFM $fm) {
    $folder = $fm->activeFolder;
    if (!$folder || !$folder->getPermission('upload_files')) {
      return;
    }
    // Prepare save options
    $destination = $folder->getUri();
    $replace = $fm->getConf('replace', FILE_EXISTS_RENAME);
    $validators = [];
    // Extension validator
    $exts = $fm->getConf('extensions', '');
    $validators['file_validate_extensions'] = [$exts === '*' ? NULL : $exts];
    // File size and user quota validator
    $validators['file_validate_size'] = [$fm->getConf('maxsize'), $fm->getConf('quota')];
    // Image resolution validator.
    $width = $fm->getConf('maxwidth');
    $height = $fm->getConf('maxheight');
    if ($width || $height) {
      $validators['file_validate_image_resolution'] = [($width ? $width : 10000) . 'x' . ($height ? $height : 10000)];
    }
    // Name validator
    $validators[get_class($this) . '::validateFileName'] = [$fm];
    // Save files
    if ($files = file_save_upload('imce', $validators, $destination, NULL, $replace)) {
      $fs = \Drupal::service('file_system');
      foreach (array_filter($files) as $file) {
        // Set status and save
        $file->setPermanent();
        $file->save();
        // Add to the folder and to js response.
        $name = $fs->basename($file->getFileUri());
        $folder->addFile($name)->addToJs();
      }
    }
  }

  /**
   * Validates the name of a file object.
   */
  public static function validateFileName(FileInterface $file, ImceFM $fm) {
    $errors = [];
    if (!$fm->validateFileName($file->getFileName(), TRUE)) {
      $errors[] = t('%filename contains invalid characters.', ['%filename' => $filename]);
    }
    return $errors;
  }

}
