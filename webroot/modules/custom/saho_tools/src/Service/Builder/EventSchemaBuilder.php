<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;

/**
 * Builds Schema.org Article structured data for historical Event nodes.
 *
 * "This Day in History" entries describe historical events; they are not
 * attendable events. The page is therefore modeled as a Schema.org Article
 * (a GSC-eligible type) with the historical event nested under `about` as
 * a Schema.org Event with additionalType=HistoricalEvent.
 */
class EventSchemaBuilder extends SchemaBuilderBase {

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
      '@type' => 'Article',
      'additionalType' => 'https://schema.org/ScholarlyArticle',
      'headline' => $node->getTitle(),
      'datePublished' => date('c', $node->getCreatedTime()),
      'dateModified' => date('c', $node->getChangedTime()),
    ] + $this->identityProperties($node);

    // Event date drives the historical event's startDate.
    $event_date = NULL;
    if ($node->hasField('field_event_date') && !$node->get('field_event_date')->isEmpty()) {
      $event_date = $node->get('field_event_date')->value;
    }

    // Body becomes description + articleBody.
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $node->get('body')->value;
      $plain = strip_tags($body);
      $description = strlen($plain) > 500 ? substr($plain, 0, 497) . '...' : $plain;
      $schema['description'] = $description;
      $schema['articleBody'] = $plain;
      $schema['wordCount'] = str_word_count($plain);
    }
    else {
      $schema['description'] = 'Historical event from South African History Online archives.';
    }

    // Article image.
    $image_data = $this->getImageData($node, ['field_tdih_image', 'field_event_image', 'field_image']);
    if ($image_data) {
      $schema['image'] = $image_data;
    }

    // Keywords from event type taxonomy.
    if ($node->hasField('field_event_type') && !$node->get('field_event_type')->isEmpty()) {
      $event_types = [];
      foreach ($node->get('field_event_type') as $event_type) {
        // @phpstan-ignore-next-line
        $term = $event_type->entity;
        if ($term) {
          $event_types[] = $term->getName();
        }
      }
      if (!empty($event_types)) {
        $schema['keywords'] = implode(', ', $event_types);
        $schema['articleSection'] = $event_types[0];
      }
    }

    // Spatial coverage from African country.
    $places = $this->getPlaces($node);
    if (!empty($places)) {
      $schema['spatialCoverage'] = count($places) === 1 ? $places[0] : $places;
    }

    // Citations.
    if ($node->hasField('field_ref_str') && !$node->get('field_ref_str')->isEmpty()) {
      $ref_str = $node->get('field_ref_str')->value;
      if (!empty($ref_str)) {
        $citations = array_filter(array_map('trim', explode('|', $ref_str)));
        if (!empty($citations)) {
          $schema['citation'] = array_values($citations);
        }
      }
    }

    // Temporal coverage for the article itself (when did this describe).
    if ($event_date) {
      $schema['temporalCoverage'] = $event_date;
    }

    // Nested Event under `about` — the actual historical event the article
    // describes. Uses Place (never VirtualLocation) so it validates cleanly.
    $about_event = [
      '@type' => 'Event',
      'additionalType' => 'https://schema.org/HistoricalEvent',
      'name' => $node->getTitle(),
    ];
    if ($event_date) {
      $about_event['startDate'] = $event_date;
      $about_event['endDate'] = $event_date;
    }
    if (!empty($places)) {
      $about_event['location'] = count($places) === 1 ? $places[0] : $places;
    }
    else {
      $about_event['location'] = [
        '@type' => 'Place',
        'name' => 'South Africa',
        'address' => [
          '@type' => 'PostalAddress',
          'addressCountry' => 'ZA',
        ],
      ];
    }
    $schema['about'] = $about_event;

    // Publisher, license, language.
    $schema['publisher'] = $this->getPublisherSchema();
    $schema['license'] = 'https://creativecommons.org/licenses/by-nc-sa/4.0/';
    $schema['isAccessibleForFree'] = TRUE;
    $schema['educationalUse'] = 'research';
    $schema['inLanguage'] = 'en-ZA';

    return $schema;
  }

  /**
   * Build Place entities from field_african_country.
   */
  protected function getPlaces(NodeInterface $node): array {
    $places = [];
    if ($node->hasField('field_african_country') && !$node->get('field_african_country')->isEmpty()) {
      foreach ($node->get('field_african_country') as $country) {
        // @phpstan-ignore-next-line
        $term = $country->entity;
        if ($term) {
          $places[] = [
            '@type' => 'Place',
            'name' => $term->getName(),
          ];
        }
      }
    }
    return $places;
  }

  /**
   * Build an ImageObject (with width/height when available).
   */
  protected function getImageData(NodeInterface $node, array $field_names): ?array {
    foreach ($field_names as $field_name) {
      if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
        continue;
      }
      $field_item = $node->get($field_name)->first();
      if (!$field_item) {
        continue;
      }
      // @phpstan-ignore-next-line
      $entity = $field_item->entity;

      // Direct file field.
      if ($entity instanceof File) {
        $image = [
          '@type' => 'ImageObject',
          'url' => $this->fileUrlGenerator->generateAbsoluteString($entity->getFileUri()),
        ];
        $image['contentUrl'] = $image['url'];
        $w = $field_item->get('width')->getValue();
        $h = $field_item->get('height')->getValue();
        if ($w) {
          $image['width'] = (int) $w;
        }
        if ($h) {
          $image['height'] = (int) $h;
        }
        return $image;
      }

      // Media reference.
      if ($entity instanceof ContentEntityInterface && $entity->hasField('field_media_image')) {
        if (!$entity->get('field_media_image')->isEmpty()) {
          $media_item = $entity->get('field_media_image')->first();
          // @phpstan-ignore-next-line
          $file = $media_item ? $media_item->entity : NULL;
          if ($file instanceof File) {
            $image = [
              '@type' => 'ImageObject',
              'url' => $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()),
            ];
            $image['contentUrl'] = $image['url'];
            $w = $media_item->get('width')->getValue();
            $h = $media_item->get('height')->getValue();
            if ($w) {
              $image['width'] = (int) $w;
            }
            if ($h) {
              $image['height'] = (int) $h;
            }
            return $image;
          }
        }
      }
    }
    return NULL;
  }

  /**
   * Returns the @id-linked sitewide organization stub as publisher.
   */
  protected function getPublisherSchema(): array {
    return $this->organizationRef();
  }

}
