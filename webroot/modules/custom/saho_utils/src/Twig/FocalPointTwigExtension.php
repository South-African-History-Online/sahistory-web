<?php

declare(strict_types=1);

namespace Drupal\saho_utils\Twig;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\saho_utils\Service\FocalPointPositionService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes the editorial focal point to templates that build img tags.
 *
 * Saho_focal_position(uri, width, height) and
 * saho_focal_position_entity(entity, field) return a CSS object-position
 * such as "30% 25%", or null when no focal point is set.
 */
final class FocalPointTwigExtension extends AbstractExtension {

  /**
   * Constructs the extension.
   *
   * @param \Drupal\saho_utils\Service\FocalPointPositionService $focalPoint
   *   The focal point position resolver.
   */
  public function __construct(
    private readonly FocalPointPositionService $focalPoint,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('saho_focal_position', [$this, 'position']),
      new TwigFunction('saho_focal_position_entity', [$this, 'positionForEntity']),
    ];
  }

  /**
   * Resolves a file URI to an object-position; tolerant of template nulls.
   *
   * @param mixed $uri
   *   The original file URI.
   * @param mixed $width
   *   Original width when known.
   * @param mixed $height
   *   Original height when known.
   *
   * @return string|null
   *   The object-position, or NULL.
   */
  public function position(mixed $uri, mixed $width = NULL, mixed $height = NULL): ?string {
    if (!is_string($uri) || $uri === '') {
      return NULL;
    }
    return $this->focalPoint->objectPosition(
      $uri,
      is_numeric($width) ? (int) $width : NULL,
      is_numeric($height) ? (int) $height : NULL,
    );
  }

  /**
   * Resolves an entity's image field to an object-position.
   *
   * @param mixed $entity
   *   The content entity.
   * @param mixed $field_name
   *   The image field, or NULL to detect it.
   *
   * @return string|null
   *   The object-position, or NULL.
   */
  public function positionForEntity(mixed $entity, mixed $field_name = NULL): ?string {
    if (!$entity instanceof ContentEntityInterface) {
      return NULL;
    }
    return $this->focalPoint->forEntity($entity, is_string($field_name) && $field_name !== '' ? $field_name : NULL);
  }

}
