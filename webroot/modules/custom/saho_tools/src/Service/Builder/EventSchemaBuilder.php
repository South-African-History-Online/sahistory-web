<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org Event structured data for Event nodes.
 *
 * Maps SAHO "This Day in History" (TDIH) events to Schema.org Event
 * vocabulary for optimal discovery by search engines and AI systems.
 */
class EventSchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * Constructs an EventSchemaBuilder.
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
    return $node_type === 'event';
  }

  /**
   * {@inheritdoc}
   */
  public function build(NodeInterface $node): array {
    if (!$this->supports($node->getType())) {
      return [];
    }

    $schema = [
      '@context' => 'https://schema.org',
      '@type' => 'Event',
      'name' => $node->getTitle(),
      'url' => $node->toUrl()->setAbsolute()->toString(),
    ];

    // Add event date as startDate and temporalCoverage.
    if ($node->hasField('field_event_date') && !$node->get('field_event_date')->isEmpty()) {
      $event_date = $node->get('field_event_date')->value;
      if (!empty($event_date)) {
        $schema['startDate'] = $event_date;
        $schema['temporalCoverage'] = $event_date;
      }
    }

    // Add event description from body.
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $node->get('body')->value;
      $description = strip_tags($body);
      // Limit to 500 characters for description.
      if (strlen($description) > 500) {
        $description = substr($description, 0, 497) . '...';
      }
      $schema['description'] = $description;
    }

    // Add event image (field_tdih_image or field_event_image).
    $image_url = $this->getImageUrl($node, ['field_tdih_image', 'field_event_image', 'field_image']);
    if ($image_url) {
      $schema['image'] = [
        '@type' => 'ImageObject',
        'url' => $image_url,
        'contentUrl' => $image_url,
      ];
    }

    // Add event type as additionalType.
    if ($node->hasField('field_event_type') && !$node->get('field_event_type')->isEmpty()) {
      $event_types = [];
      foreach ($node->get('field_event_type') as $event_type) {
        if ($event_type->entity) {
          $event_types[] = $event_type->entity->getName();
        }
      }
      if (!empty($event_types)) {
        $schema['additionalType'] = count($event_types) === 1 ? $event_types[0] : $event_types;
      }
    }

    // Add location (African country).
    if ($node->hasField('field_african_country') && !$node->get('field_african_country')->isEmpty()) {
      $countries = [];
      foreach ($node->get('field_african_country') as $country) {
        if ($country->entity) {
          $countries[] = [
            '@type' => 'Place',
            'name' => $country->entity->getName(),
          ];
        }
      }
      if (!empty($countries)) {
        $schema['location'] = count($countries) === 1 ? $countries[0] : $countries;
      }
    }

    // Add citations from field_ref_str (pipe-delimited).
    if ($node->hasField('field_ref_str') && !$node->get('field_ref_str')->isEmpty()) {
      $ref_str = $node->get('field_ref_str')->value;
      if (!empty($ref_str)) {
        $citations = array_filter(explode('|', $ref_str));
        if (!empty($citations)) {
          $schema['citation'] = array_map('trim', $citations);
        }
      }
    }

    // Add organizer (SAHO).
    $schema['organizer'] = $this->getOrganizerSchema();

    // Add educational properties.
    $schema['isAccessibleForFree'] = TRUE;
    $schema['educationalUse'] = 'research';

    // Add inLanguage.
    $schema['inLanguage'] = 'en-ZA';

    // Add event status (completed historical events).
    $schema['eventStatus'] = 'https://schema.org/EventScheduled';
    $schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';

    return $schema;
  }

  /**
   * Get image URL from node image fields.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param array $field_names
   *   Array of field names to check in priority order.
   *
   * @return string|null
   *   The absolute image URL or NULL if not found.
   */
  protected function getImageUrl(NodeInterface $node, array $field_names): ?string {
    foreach ($field_names as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        $field_value = $node->get($field_name);

        // Handle file fields.
        if ($field_value->entity instanceof File) {
          return $this->fileUrlGenerator->generateAbsoluteString($field_value->entity->getFileUri());
        }

        // Handle media entity reference fields.
        if ($field_value->entity && $field_value->entity->hasField('field_media_image')) {
          $media_entity = $field_value->entity;
          if (!$media_entity->get('field_media_image')->isEmpty()) {
            $file_entity = $media_entity->get('field_media_image')->entity;
            if ($file_entity instanceof File) {
              return $this->fileUrlGenerator->generateAbsoluteString($file_entity->getFileUri());
            }
          }
        }
      }
    }

    return NULL;
  }

  /**
   * Get SAHO organizer schema.
   *
   * @return array
   *   Organizer organization schema.
   */
  protected function getOrganizerSchema(): array {
    $request = \Drupal::request();
    $base_url = $request->getSchemeAndHttpHost();

    return [
      '@type' => 'Organization',
      'name' => 'South African History Online',
      'url' => $base_url,
    ];
  }

}
