<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;

/**
 * Builds Schema.org Event structured data for Upcoming Event nodes.
 *
 * Maps SAHO upcoming events (exhibitions, conferences, museum openings)
 * to Schema.org Event vocabulary for optimal discovery by search engines.
 */
class UpcomingEventSchemaBuilder extends SchemaBuilderBase {

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
    ] + $this->identityProperties($node);

    // Dates as ISO 8601 with the South African offset. The storage value is
    // a bare UTC string; emitted as-is, consumers read it as local time and
    // the event lands two hours early.
    $start_date = $this->localIsoDate($node, 'field_start_date')
      ?? date('c', $node->getCreatedTime());
    $schema['startDate'] = $start_date;
    $schema['endDate'] = $this->localIsoDate($node, 'field_end_date') ?? $start_date;

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

    // Location (venue). Always emit a Place (Google rejects VirtualLocation
    // for Event rich results). When no venue is set, fall back to a generic
    // South Africa Place rather than a virtual URL placeholder.
    if ($node->hasField('field_upcoming_venue') && !$node->get('field_upcoming_venue')->isEmpty()) {
      $venue = trim(strip_tags($node->get('field_upcoming_venue')->value));
      if (!empty($venue)) {
        $schema['location'] = [
          '@type' => 'Place',
          'name' => $venue,
          'address' => [
            '@type' => 'PostalAddress',
            'addressCountry' => 'ZA',
          ],
        ];
      }
    }
    if (empty($schema['location'])) {
      $schema['location'] = [
        '@type' => 'Place',
        'name' => 'South Africa',
        'address' => [
          '@type' => 'PostalAddress',
          'addressCountry' => 'ZA',
        ],
      ];
    }

    // Add organizer + performer (SAHO hosts these gatherings).
    $schema['organizer'] = $this->getOrganizationSchema();
    $schema['performer'] = $this->getOrganizationSchema();

    // Add offers (free event).
    $schema['offers'] = [
      '@type' => 'Offer',
      'price' => '0',
      'priceCurrency' => 'ZAR',
      'availability' => 'https://schema.org/InStock',
      'url' => $this->canonicalNodeUrl($node),
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
   * Returns the @id-linked sitewide organization stub.
   *
   * @return array
   *   Organization stub for organizer/performer.
   */
  protected function getOrganizationSchema(): array {
    return $this->organizationRef();
  }

  /**
   * A datetime field as ISO 8601 in Africa/Johannesburg, or NULL if empty.
   */
  private function localIsoDate(NodeInterface $node, string $field): ?string {
    if (!$node->hasField($field) || $node->get($field)->isEmpty()) {
      return NULL;
    }
    // @phpstan-ignore-next-line
    $date = $node->get($field)->date;
    if (!$date) {
      return NULL;
    }
    $local = clone $date->getPhpDateTime();
    $local->setTimezone(new \DateTimeZone('Africa/Johannesburg'));
    return $local->format('Y-m-d\TH:i:sP');
  }

}
