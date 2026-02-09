<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org Event structured data for Upcoming Event nodes.
 *
 * Maps SAHO upcoming events (exhibitions, conferences, museum openings)
 * to Schema.org Event vocabulary for optimal discovery by search engines.
 */
class UpcomingEventSchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * Constructs an UpcomingEventSchemaBuilder.
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
    return $node_type === 'upcomingevent';
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

    // Add start date.
    $start_date = NULL;
    if ($node->hasField('field_start_date') && !$node->get('field_start_date')->isEmpty()) {
      $start_date = $node->get('field_start_date')->value;
    }

    // Fallback: Use node created date if start date is missing.
    if (empty($start_date)) {
      $start_date = date('c', $node->getCreatedTime());
    }

    $schema['startDate'] = $start_date;

    // Add end date.
    if ($node->hasField('field_end_date') && !$node->get('field_end_date')->isEmpty()) {
      $schema['endDate'] = $node->get('field_end_date')->value;
    }
    else {
      // Default to start date if no end date specified.
      $schema['endDate'] = $start_date;
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
    else {
      // Fallback: Use generic description if body is missing.
      $schema['description'] = 'Upcoming event from South African History Online.';
    }

    // Add event image.
    $image_url = $this->getImageUrl($node, ['field_upcomingevent_image', 'field_image']);
    if ($image_url) {
      $schema['image'] = [
        '@type' => 'ImageObject',
        'url' => $image_url,
        'contentUrl' => $image_url,
      ];
    }

    // Add event type as additionalType.
    if ($node->hasField('field_type_of_event') && !$node->get('field_type_of_event')->isEmpty()) {
      $event_type = $node->get('field_type_of_event')->value;
      if (!empty($event_type)) {
        $schema['additionalType'] = $event_type;
      }
    }

    // Add location (venue).
    if ($node->hasField('field_upcoming_venue') && !$node->get('field_upcoming_venue')->isEmpty()) {
      $venue = $node->get('field_upcoming_venue')->value;
      if (!empty($venue)) {
        $schema['location'] = [
          '@type' => 'Place',
          'name' => strip_tags($venue),
        ];
      }
    }

    // Fallback: Use VirtualLocation if no physical location.
    if (empty($schema['location'])) {
      $schema['location'] = [
        '@type' => 'VirtualLocation',
        'url' => $node->toUrl()->setAbsolute()->toString(),
      ];
    }

    // Add organizer (SAHO).
    $schema['organizer'] = $this->getOrganizationSchema();

    // Add offers (free event).
    $schema['offers'] = [
      '@type' => 'Offer',
      'price' => '0',
      'priceCurrency' => 'ZAR',
      'availability' => 'https://schema.org/InStock',
      'url' => $node->toUrl()->setAbsolute()->toString(),
    ];

    // Add event status and attendance mode.
    $schema['eventStatus'] = 'https://schema.org/EventScheduled';
    $schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';

    // Add educational properties.
    $schema['isAccessibleForFree'] = TRUE;

    // Add inLanguage.
    $schema['inLanguage'] = 'en-ZA';

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
        if ($field_value->entity instanceof ContentEntityInterface && $field_value->entity->hasField('field_media_image')) {
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
   * Get SAHO organization schema.
   *
   * @return array
   *   Organization schema for organizer.
   */
  protected function getOrganizationSchema(): array {
    $request = \Drupal::request();
    $base_url = $request->getSchemeAndHttpHost();

    return [
      '@type' => 'Organization',
      'name' => 'South African History Online',
      'url' => $base_url,
    ];
  }

}
