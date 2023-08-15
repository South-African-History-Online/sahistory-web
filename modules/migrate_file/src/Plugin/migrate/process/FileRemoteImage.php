<?php

namespace Drupal\migrate_file\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\file\Entity\File;

/**
 * Create an image file entity with a remote url without downloading the file.
 *
 * This is almost the same as file_remote_url except it supports supplying a
 * width and height for the image for image fields. This will prevent the image
 * module from requesting the image file to measure the width and height which
 * can slow down migrations with lots of remote images.
 *
 * @see https://www.drupal.org/project/remote_stream_wrapper
 *
 * Available configuration keys:
 * - uid: (optional) The uid to attribute the file entity to. Defaults to 0.
 * - width: (optional) The width of the image(s)
 * - height: (optional) The height of the image(s)
 *
 * Example:
 *
 * @code
 * destination:
 *   plugin: entity:node
 * process:
 *   field_image:
 *     plugin: file_remote_image
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
class FileRemoteImage extends FileRemoteUrl {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    $configuration += [
      'uid' => 0,
      'width' => NULL,
      'height' => NULL,
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

    // Process the file
    $file = parent::transform($value, $migrate_executable, $row, $destination_property);

    if ($this->configuration['width'] && $this->configuration['height']) {
      return [
        'target_id' => $file->id(),
        'width' => $this->configuration['width'],
        'height' => $this->configuration['height'],
      ];
    }

    return $file;
  }

}
