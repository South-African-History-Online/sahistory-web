<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org Place structured data for Place nodes.
 *
 * Maps SAHO place/location content to Schema.org Place vocabulary
 * with geographic coordinates and place type specificity.
 */
class PlaceSchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * Constructs a PlaceSchemaBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function supports(string $node_type): bool {
    return $node_type === 'place';
  }

  /**
   * {@inheritdoc}
   */
  public function build(NodeInterface $node): array {
    if (!$this->supports($node->getType())) {
      return [];
    }

    // Determine specific place type.
    $place_type = 'Place';
    if ($node->hasField('field_place_type') && !$node->get('field_place_type')->isEmpty()) {
      $type_value = $node->get('field_place_type')->value;
      // Map to Schema.org subtypes.
      $type_map = [
        'museum' => 'Museum',
        'monument' => 'LandmarksOrHistoricalBuildings',
        'heritage_site' => 'LandmarksOrHistoricalBuildings',
        'building' => 'LandmarksOrHistoricalBuildings',
      ];
      if ($type_value && isset($type_map[strtolower($type_value)])) {
        $place_type = $type_map[strtolower($type_value)];
      }
    }

    $schema = [
      '@context' => 'https://schema.org',
      '@type' => $place_type,
      'name' => $node->getTitle(),
      'url' => $node->toUrl()->setAbsolute()->toString(),
    ];

    // Add description.
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $node->get('body')->value;
      $description = strip_tags($body);
      if (strlen($description) > 500) {
        $description = substr($description, 0, 497) . '...';
      }
      $schema['description'] = $description;
    }

    // Add geographic coordinates.
    if ($node->hasField('field_geofield') && !$node->get('field_geofield')->isEmpty()) {
      $geo = $node->get('field_geofield')->first();
      if ($geo) {
        $schema['geo'] = [
          '@type' => 'GeoCoordinates',
          'latitude' => $geo->get('lat')->getValue(),
          'longitude' => $geo->get('lon')->getValue(),
        ];
      }
    }
    elseif ($node->hasField('field_geolocation') && !$node->get('field_geolocation')->isEmpty()) {
      $geo = $node->get('field_geolocation')->first();
      if ($geo) {
        $schema['geo'] = [
          '@type' => 'GeoCoordinates',
          'latitude' => $geo->get('lat')->getValue(),
          'longitude' => $geo->get('lng')->getValue(),
        ];
      }
    }

    // Add address information.
    if ($node->hasField('field_african_country') && !$node->get('field_african_country')->isEmpty()) {
      /** @var \Drupal\taxonomy\Entity\Term|null $country */
      $country = $node->get('field_african_country')->entity;
      if ($country) {
        $schema['address'] = [
          '@type' => 'PostalAddress',
          'addressCountry' => $country->getName(),
        ];
      }
    }

    // Add place image.
    if ($node->hasField('field_place_image') && !$node->get('field_place_image')->isEmpty()) {
      $field_value = $node->get('field_place_image');
      if ($field_value->entity instanceof File) {
        $image_url = $this->fileUrlGenerator->generateAbsoluteString($field_value->entity->getFileUri());
        $schema['image'] = [
          '@type' => 'ImageObject',
          'url' => $image_url,
          'contentUrl' => $image_url,
        ];
      }
    }

    // Add isAccessibleForFree.
    $schema['isAccessibleForFree'] = TRUE;

    return $schema;
  }

}
