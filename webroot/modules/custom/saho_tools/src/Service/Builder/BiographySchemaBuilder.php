<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org Person structured data for Biography nodes.
 *
 * Maps SAHO biography content to Schema.org Person vocabulary
 * for optimal discovery by search engines and AI systems.
 */
class BiographySchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * Constructs a BiographySchemaBuilder.
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
    return $node_type === 'biography';
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
      '@type' => 'Person',
      'name' => $node->getTitle(),
      'url' => $node->toUrl()->setAbsolute()->toString(),
    ];

    // Build full name from components.
    $name_parts = [];
    if ($node->hasField('field_firstname') && !$node->get('field_firstname')->isEmpty()) {
      $name_parts[] = $node->get('field_firstname')->value;
      $schema['givenName'] = $node->get('field_firstname')->value;
    }
    if ($node->hasField('field_middlename') && !$node->get('field_middlename')->isEmpty()) {
      $name_parts[] = $node->get('field_middlename')->value;
      $schema['additionalName'] = $node->get('field_middlename')->value;
    }
    if ($node->hasField('field_lastnamebio') && !$node->get('field_lastnamebio')->isEmpty()) {
      $name_parts[] = $node->get('field_lastnamebio')->value;
      $schema['familyName'] = $node->get('field_lastnamebio')->value;
    }
    if (!empty($name_parts)) {
      $schema['name'] = implode(' ', $name_parts);
    }

    // Add birth date.
    if ($node->hasField('field_drupal_birth_date') && !$node->get('field_drupal_birth_date')->isEmpty()) {
      $birth_date = $node->get('field_drupal_birth_date')->value;
      if (!empty($birth_date)) {
        $schema['birthDate'] = $birth_date;
      }
    }

    // Add death date.
    if ($node->hasField('field_drupal_death_date') && !$node->get('field_drupal_death_date')->isEmpty()) {
      $death_date = $node->get('field_drupal_death_date')->value;
      if (!empty($death_date)) {
        $schema['deathDate'] = $death_date;
      }
    }

    // Add birth place.
    if ($node->hasField('field_birth_location') && !$node->get('field_birth_location')->isEmpty()) {
      $birth_location = $node->get('field_birth_location')->value;
      if (!empty($birth_location)) {
        $schema['birthPlace'] = [
          '@type' => 'Place',
          'name' => $birth_location,
        ];
      }
    }

    // Add death place.
    if ($node->hasField('field_death_location') && !$node->get('field_death_location')->isEmpty()) {
      $death_location = $node->get('field_death_location')->value;
      if (!empty($death_location)) {
        $schema['deathPlace'] = [
          '@type' => 'Place',
          'name' => $death_location,
        ];
      }
    }

    // Add biography image.
    if ($node->hasField('field_bio_pic') && !$node->get('field_bio_pic')->isEmpty()) {
      $field_value = $node->get('field_bio_pic');
      if ($field_value->entity instanceof File) {
        $image_url = $this->fileUrlGenerator->generateAbsoluteString($field_value->entity->getFileUri());
        $schema['image'] = [
          '@type' => 'ImageObject',
          'url' => $image_url,
          'contentUrl' => $image_url,
        ];
      }
    }

    // Add job title/position.
    if ($node->hasField('field_position') && !$node->get('field_position')->isEmpty()) {
      $positions = [];
      foreach ($node->get('field_position') as $position) {
        if (!empty($position->value)) {
          $positions[] = $position->value;
        }
      }
      if (!empty($positions)) {
        $schema['jobTitle'] = count($positions) === 1 ? $positions[0] : $positions;
      }
    }

    // Add affiliation.
    if ($node->hasField('field_affiliation') && !$node->get('field_affiliation')->isEmpty()) {
      $affiliations = [];
      foreach ($node->get('field_affiliation') as $affiliation) {
        if (!empty($affiliation->value)) {
          $affiliations[] = [
            '@type' => 'Organization',
            'name' => $affiliation->value,
          ];
        }
      }
      if (!empty($affiliations)) {
        $schema['affiliation'] = count($affiliations) === 1 ? $affiliations[0] : $affiliations;
      }
    }

    // Add nationality (from African country field).
    if ($node->hasField('field_african_country') && !$node->get('field_african_country')->isEmpty()) {
      $countries = [];
      foreach ($node->get('field_african_country') as $country) {
        if ($country->entity) {
          $countries[] = [
            '@type' => 'Country',
            'name' => $country->entity->getName(),
          ];
        }
      }
      if (!empty($countries)) {
        $schema['nationality'] = count($countries) === 1 ? $countries[0] : $countries;
      }
    }

    // Add description from body.
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $node->get('body')->value;
      // Get first 200 characters as description.
      $description = strip_tags($body);
      if (strlen($description) > 200) {
        $description = substr($description, 0, 197) . '...';
      }
      $schema['description'] = $description;
    }

    // Add sameAs for related URLs if available.
    if ($node->hasField('field_url') && !$node->get('field_url')->isEmpty()) {
      $urls = [];
      foreach ($node->get('field_url') as $url) {
        if (!empty($url->uri)) {
          $urls[] = $url->uri;
        }
      }
      if (!empty($urls)) {
        $schema['sameAs'] = $urls;
      }
    }

    return $schema;
  }

}
