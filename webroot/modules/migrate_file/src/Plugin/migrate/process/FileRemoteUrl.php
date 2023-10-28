<?php

namespace Drupal\migrate_file\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\file\Entity\File;

/**
 * Create a file entity with a remote url without downloading the file.
 *
 * It is assumed if you're using this process plugin that you have something in
 * place to properly handle the external uri on the file object (e.g. the Remote
 * Stream Wrapper module).
 *
 * Note that if you are using the filefield_paths module for the target file
 * field, the remote url will be downloaded when the parent entity is saved.
 * This is functionality built into filefield_paths. To avoid the download just
 * disable the filefield_paths option on the field settings.
 *
 * @see https://www.drupal.org/project/remote_stream_wrapper
 *
 * Available configuration keys:
 * - uid: (optional) The uid to attribute the file entity to. Defaults to 0.
 *
 * Example:
 *
 * @code
 * destination:
 *   plugin: entity:node
 * process:
 *   field_image:
 *     plugin: file_remote_url
 *     source: https://www.drupal.org/files/drupal_logo-blue.png
 *
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "file_remote_url"
 * )
 */
class FileRemoteUrl extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    $configuration += [
      'uid' => 0,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // If we're stubbing a file entity, return a URI of NULL so it will get
    // stubbed by the general process.
    if (!$value) {
      return NULL;
    }

    // Create a file entity.
    $file = File::create([
      'uri' => $value,
      'uid' => $this->configuration['uid'],
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $file->save();

    return $file;
  }

}
