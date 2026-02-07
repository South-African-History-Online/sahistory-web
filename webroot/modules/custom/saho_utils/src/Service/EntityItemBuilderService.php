<?php

declare(strict_types=1);

namespace Drupal\saho_utils\Service;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Service for building standardized entity item arrays.
 *
 * Provides consistent methods for building entity item arrays across all
 * SAHO custom blocks, eliminating duplicate code and ensuring uniform output.
 */
class EntityItemBuilderService {

  /**
   * The image extractor service.
   *
   * @var \Drupal\saho_utils\Service\ImageExtractorService
   */
  protected ImageExtractorService $imageExtractor;

  /**
   * The content extractor service.
   *
   * @var \Drupal\saho_utils\Service\ContentExtractorService
   */
  protected ContentExtractorService $contentExtractor;

  /**
   * Constructs an EntityItemBuilderService object.
   *
   * @param \Drupal\saho_utils\Service\ImageExtractorService $image_extractor
   *   The image extractor service.
   * @param \Drupal\saho_utils\Service\ContentExtractorService $content_extractor
   *   The content extractor service.
   */
  public function __construct(
    ImageExtractorService $image_extractor,
    ContentExtractorService $content_extractor,
  ) {
    $this->imageExtractor = $image_extractor;
    $this->contentExtractor = $content_extractor;
  }

  /**
   * Build a basic entity item with ID, title, and URL.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to build the item from.
   *
   * @return array
   *   Array with 'id', 'title', and 'url' keys.
   */
  public function buildBasicItem(ContentEntityInterface $entity): array {
    return [
      'id' => $entity->id(),
      'title' => $entity->label(),
      'url' => $entity->toUrl()->toString(),
    ];
  }

  /**
   * Build an entity item with image.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to build the item from.
   * @param string|null $image_field
   *   Optional specific image field name. If NULL, auto-detects.
   * @param string|null $image_style
   *   Optional image style name.
   *
   * @return array
   *   Array with basic fields plus 'image_url'.
   */
  public function buildItemWithImage(
    ContentEntityInterface $entity,
    ?string $image_field = NULL,
    ?string $image_style = NULL,
  ): array {
    $item = $this->buildBasicItem($entity);

    // Extract image URL.
    $image_url = $image_style
      ? $this->imageExtractor->extractImageWithDerivatives($entity, $image_style, $image_field)
      : $this->imageExtractor->extractImageUrl($entity, $image_field);

    $item['image_url'] = $image_url ?? '';

    return $item;
  }

  /**
   * Build an entity item with image and teaser.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to build the item from.
   * @param int $teaser_length
   *   Maximum length for teaser text. Default is 150.
   * @param string|null $image_field
   *   Optional specific image field name. If NULL, auto-detects.
   * @param string|null $image_style
   *   Optional image style name.
   *
   * @return array
   *   Array with basic fields plus 'image_url' and 'teaser'.
   */
  public function buildItemWithTeaser(
    ContentEntityInterface $entity,
    int $teaser_length = 150,
    ?string $image_field = NULL,
    ?string $image_style = NULL,
  ): array {
    $item = $this->buildItemWithImage($entity, $image_field, $image_style);

    // Extract teaser text.
    $item['teaser'] = $this->contentExtractor->extractTeaser($entity, $teaser_length);

    return $item;
  }

  /**
   * Build a full entity item with all common fields.
   *
   * Includes ID, title, URL, image, teaser, dates, content type, and bundle.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to build the item from.
   * @param array $options
   *   Optional configuration:
   *   - 'teaser_length': int (default 150)
   *   - 'image_field': string|null
   *   - 'image_style': string|null
   *   - 'include_dates': bool (default TRUE)
   *   - 'include_type': bool (default TRUE).
   *
   * @return array
   *   Complete entity item array.
   */
  public function buildFullItem(ContentEntityInterface $entity, array $options = []): array {
    // Set defaults.
    $options += [
      'teaser_length' => 150,
      'image_field' => NULL,
      'image_style' => NULL,
      'include_dates' => TRUE,
      'include_type' => TRUE,
    ];

    // Start with item including image and teaser.
    $item = $this->buildItemWithTeaser(
      $entity,
      $options['teaser_length'],
      $options['image_field'],
      $options['image_style']
    );

    // Add content type and bundle if requested.
    if ($options['include_type']) {
      $item['content_type'] = $entity->getEntityTypeId();
      $item['bundle'] = $entity->bundle();
    }

    // Add date information if requested.
    if ($options['include_dates']) {
      $item['created'] = '';
      $item['changed'] = '';
      $item['published_date'] = '';

      // Created date.
      if ($entity->hasField('created') && !$entity->get('created')->isEmpty()) {
        $created_timestamp = $entity->get('created')->value;
        if ($created_timestamp) {
          $item['created'] = date('Y-m-d', (int) $created_timestamp);
        }
      }

      // Changed date.
      if ($entity->hasField('changed') && !$entity->get('changed')->isEmpty()) {
        $changed_timestamp = $entity->get('changed')->value;
        if ($changed_timestamp) {
          $item['changed'] = date('Y-m-d', (int) $changed_timestamp);
        }
      }

      // Published date (for nodes).
      if ($entity->hasField('field_published_date') && !$entity->get('field_published_date')->isEmpty()) {
        $published_date = $entity->get('field_published_date')->value;
        if ($published_date) {
          $item['published_date'] = $published_date;
        }
      }
    }

    return $item;
  }

  /**
   * Build a custom entity item with specified fields.
   *
   * Provides flexible field selection for specialized use cases.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to build the item from.
   * @param array $fields
   *   Array of field names to include. Special values:
   *   - '_basic': Include ID, title, URL
   *   - '_image': Include image_url
   *   - '_teaser': Include teaser
   *   - '_dates': Include created, changed, published_date
   *   - Any field name: Include that field's value.
   *
   * @return array
   *   Custom entity item array with requested fields.
   */
  public function buildCustomItem(ContentEntityInterface $entity, array $fields): array {
    $item = [];

    foreach ($fields as $field) {
      switch ($field) {
        case '_basic':
          $item += $this->buildBasicItem($entity);
          break;

        case '_image':
          $image_url = $this->imageExtractor->extractImageUrl($entity);
          $item['image_url'] = $image_url ?? '';
          break;

        case '_teaser':
          $item['teaser'] = $this->contentExtractor->extractTeaser($entity);
          break;

        case '_dates':
          if ($entity->hasField('created') && !$entity->get('created')->isEmpty()) {
            $item['created'] = date('Y-m-d', (int) $entity->get('created')->value);
          }
          if ($entity->hasField('changed') && !$entity->get('changed')->isEmpty()) {
            $item['changed'] = date('Y-m-d', (int) $entity->get('changed')->value);
          }
          if ($entity->hasField('field_published_date') && !$entity->get('field_published_date')->isEmpty()) {
            $item['published_date'] = $entity->get('field_published_date')->value;
          }
          break;

        default:
          // Include specific field value.
          if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
            $field_value = $entity->get($field)->first();
            if ($field_value) {
              $value = $field_value->getValue();
              // Use the value key if it exists, otherwise use the whole array.
              $item[$field] = $value['value'] ?? $value;
            }
          }
          break;
      }
    }

    return $item;
  }

  /**
   * Build multiple entity items from an array of entities.
   *
   * @param array $entities
   *   Array of ContentEntityInterface objects.
   * @param string $method
   *   Method to use: 'basic', 'image', 'teaser', 'full', or 'custom'.
   * @param array $options
   *   Options to pass to the builder method.
   *
   * @return array
   *   Array of entity item arrays.
   */
  public function buildMultipleItems(array $entities, string $method = 'full', array $options = []): array {
    $items = [];

    foreach ($entities as $entity) {
      if (!$entity instanceof ContentEntityInterface) {
        continue;
      }

      switch ($method) {
        case 'basic':
          $items[] = $this->buildBasicItem($entity);
          break;

        case 'image':
          $items[] = $this->buildItemWithImage(
            $entity,
            $options['image_field'] ?? NULL,
            $options['image_style'] ?? NULL
          );
          break;

        case 'teaser':
          $items[] = $this->buildItemWithTeaser(
            $entity,
            $options['teaser_length'] ?? 150,
            $options['image_field'] ?? NULL,
            $options['image_style'] ?? NULL
          );
          break;

        case 'custom':
          if (isset($options['fields'])) {
            $items[] = $this->buildCustomItem($entity, $options['fields']);
          }
          break;

        case 'full':
        default:
          $items[] = $this->buildFullItem($entity, $options);
          break;
      }
    }

    return $items;
  }

}
