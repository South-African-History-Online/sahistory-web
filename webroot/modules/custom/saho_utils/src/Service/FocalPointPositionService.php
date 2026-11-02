<?php

declare(strict_types=1);

namespace Drupal\saho_utils\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\file\FileInterface;

/**
 * Resolves an image's editorial focal point to a CSS object-position.
 *
 * Most crops on SAHO happen in the stylesheet (object-fit: cover), not in
 * image styles, so the focus an editor sets in the Focal Point widget has
 * to travel to the img element as an object-position value. Without a
 * focal point, or without the crop module, the result is NULL and the
 * stylesheet's default position stands: existing images render as before.
 */
class FocalPointPositionService {

  /**
   * The crop type the Focal Point module writes.
   */
  public const CROP_TYPE = 'focal_point';

  /**
   * Resolved positions per file URI for the request.
   *
   * @var array<string, string|null>
   */
  private array $positions = [];

  /**
   * Constructs the service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager (crop storage, when the module is on).
   * @param \Drupal\saho_utils\Service\ImageExtractorService $imageExtractor
   *   Resolves an entity's image field to its file and dimensions.
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   Reads dimensions when the field does not carry them.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ImageExtractorService $imageExtractor,
    private readonly ImageFactory $imageFactory,
  ) {}

  /**
   * Returns the object-position for a file URI, or NULL without a focus.
   *
   * @param string $uri
   *   The original file URI (public://...). Derivative URLs never match.
   * @param int|null $width
   *   Original width when known (image field items carry it).
   * @param int|null $height
   *   Original height when known.
   *
   * @return string|null
   *   A value such as "30% 25%", or NULL.
   */
  public function objectPosition(string $uri, ?int $width = NULL, ?int $height = NULL): ?string {
    if ($uri === '' || preg_match('#^https?://#i', $uri) || !$this->entityTypeManager->hasDefinition('crop')) {
      return NULL;
    }
    if (array_key_exists($uri, $this->positions)) {
      return $this->positions[$uri];
    }

    $storage = $this->entityTypeManager->getStorage('crop');
    $crop = method_exists($storage, 'getCrop') ? $storage->getCrop($uri, self::CROP_TYPE) : NULL;
    if (!$crop) {
      return $this->positions[$uri] = NULL;
    }

    if (!$width || !$height) {
      $image = $this->imageFactory->get($uri);
      if (!$image->isValid()) {
        return $this->positions[$uri] = NULL;
      }
      $width = $image->getWidth();
      $height = $image->getHeight();
    }

    $anchor = $crop->anchor();
    return $this->positions[$uri] = self::format(
      (int) ($anchor['x'] ?? 0),
      (int) ($anchor['y'] ?? 0),
      (int) $width,
      (int) $height,
    );
  }

  /**
   * Returns the object-position for a file entity.
   *
   * @param \Drupal\file\FileInterface $file
   *   The image file.
   * @param int|null $width
   *   Original width when known.
   * @param int|null $height
   *   Original height when known.
   *
   * @return string|null
   *   A value such as "30% 25%", or NULL.
   */
  public function forFile(FileInterface $file, ?int $width = NULL, ?int $height = NULL): ?string {
    return $this->objectPosition((string) $file->getFileUri(), $width, $height);
  }

  /**
   * Returns the object-position for an entity's image field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity (node, media, block content).
   * @param string|null $field_name
   *   The image, file or media field; detected when NULL.
   *
   * @return string|null
   *   A value such as "30% 25%", or NULL.
   */
  public function forEntity(ContentEntityInterface $entity, ?string $field_name = NULL): ?string {
    $field_name ??= $this->imageExtractor->findImageFieldForEntity($entity);
    if ($field_name === NULL) {
      return NULL;
    }
    $file = $this->imageExtractor->resolveFile($entity, $field_name);
    if (!$file instanceof FileInterface) {
      return NULL;
    }
    $dimensions = $this->imageExtractor->extractImageDimensions($entity, $field_name);
    return $this->forFile($file, $dimensions['width'] ?? NULL, $dimensions['height'] ?? NULL);
  }

  /**
   * Formats an absolute anchor as a clamped, rounded object-position.
   *
   * @param int $x
   *   Anchor x in source pixels.
   * @param int $y
   *   Anchor y in source pixels.
   * @param int $width
   *   Source width.
   * @param int $height
   *   Source height.
   *
   * @return string|null
   *   "X% Y%" with integer percentages, or NULL for unusable dimensions.
   */
  public static function format(int $x, int $y, int $width, int $height): ?string {
    if ($width <= 0 || $height <= 0) {
      return NULL;
    }
    $px = (int) round(max(0, min(100, $x / $width * 100)));
    $py = (int) round(max(0, min(100, $y / $height * 100)));
    return $px . '% ' . $py . '%';
  }

}
