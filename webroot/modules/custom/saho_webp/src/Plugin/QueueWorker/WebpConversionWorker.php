<?php

namespace Drupal\saho_webp\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes WebP conversion queue items.
 *
 * @QueueWorker(
 *   id = "saho_webp_conversion",
 *   title = @Translation("SAHO WebP Conversion"),
 *   cron = {"time" = 60}
 * )
 */
class WebpConversionWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a WebpConversionWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('file_system'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!isset($data['file_id'])) {
      return;
    }

    $file = $this->entityTypeManager->getStorage('file')->load($data['file_id']);
    if (!$file) {
      return;
    }

    $this->convertFileToWebp($file);
  }

  /**
   * Convert a file to WebP format safely.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to convert.
   */
  protected function convertFileToWebp(FileInterface $file) {
    // Only process image files.
    $mime_type = $file->getMimeType();
    if (!in_array($mime_type, ['image/jpeg', 'image/png'])) {
      return;
    }

    // Get file path.
    $source_path = $this->fileSystem->realpath($file->getFileUri());
    if (!$source_path || !file_exists($source_path)) {
      return;
    }

    // Wait a moment to ensure file is fully written.
    // 0.5 seconds.
    usleep(500000);

    // Create WebP path.
    $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source_path);

    // Skip if WebP already exists.
    if (file_exists($webp_path)) {
      return;
    }

    // Validate image file.
    $image_info = @getimagesize($source_path);
    if (!$image_info) {
      return;
    }

    // Check for HTML content (fake images).
    $handle = fopen($source_path, 'r');
    if ($handle) {
      $first_bytes = fread($handle, 50);
      fclose($handle);

      if (strpos($first_bytes, '<html') !== FALSE || strpos($first_bytes, '<!DOCTYPE') !== FALSE) {
        return;
      }
    }

    // Convert to WebP.
    try {
      $source_image = NULL;
      switch ($mime_type) {
        case 'image/jpeg':
          $source_image = @imagecreatefromjpeg($source_path);
          break;

        case 'image/png':
          $source_image = @imagecreatefrompng($source_path);
          if ($source_image !== FALSE) {
            imagealphablending($source_image, FALSE);
            imagesavealpha($source_image, TRUE);
          }
          break;
      }

      if ($source_image === FALSE) {
        throw new \Exception('Failed to create image resource');
      }

      // Convert with 85% quality for better results.
      $success = @imagewebp($source_image, $webp_path, 85);
      imagedestroy($source_image);

      if ($success) {
        // Set permissions to match source.
        chmod($webp_path, fileperms($source_path));

      }
      else {
        throw new \Exception('imagewebp() returned FALSE');
      }
    }
    catch (\Exception $e) {
    }
  }

}
