<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org ProfilePage structured data for Biography nodes.
 *
 * Top-level @type is ProfilePage (Google's 2024+ rich-result type for
 * person pages) with the Person nested under mainEntity. ProfilePage IS
 * reported in GSC's enhancement reports; bare Person is not.
 */
class BiographySchemaBuilder implements SchemaOrgBuilderInterface {

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

    $url = $node->toUrl()->setAbsolute()->toString();

    $person = [
      '@type' => 'Person',
      'name' => $node->getTitle(),
      'url' => $url,
      'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $url,
      ],
    ];

    // Build full name from components.
    $name_parts = [];
    if ($node->hasField('field_firstname') && !$node->get('field_firstname')->isEmpty()) {
      $name_parts[] = $node->get('field_firstname')->value;
      $person['givenName'] = $node->get('field_firstname')->value;
    }
    if ($node->hasField('field_middlename') && !$node->get('field_middlename')->isEmpty()) {
      $name_parts[] = $node->get('field_middlename')->value;
      $person['additionalName'] = $node->get('field_middlename')->value;
    }
    if ($node->hasField('field_lastnamebio') && !$node->get('field_lastnamebio')->isEmpty()) {
      $name_parts[] = $node->get('field_lastnamebio')->value;
      $person['familyName'] = $node->get('field_lastnamebio')->value;
    }
    if (!empty($name_parts)) {
      $person['name'] = implode(' ', $name_parts);
    }

    // Dates.
    if ($node->hasField('field_drupal_birth_date') && !$node->get('field_drupal_birth_date')->isEmpty()) {
      $birth_date = $node->get('field_drupal_birth_date')->value;
      if (!empty($birth_date)) {
        $person['birthDate'] = $birth_date;
      }
    }
    if ($node->hasField('field_drupal_death_date') && !$node->get('field_drupal_death_date')->isEmpty()) {
      $death_date = $node->get('field_drupal_death_date')->value;
      if (!empty($death_date)) {
        $person['deathDate'] = $death_date;
      }
    }

    // Birth/death places.
    if ($node->hasField('field_birth_location') && !$node->get('field_birth_location')->isEmpty()) {
      $birth_location = $node->get('field_birth_location')->value;
      if (!empty($birth_location)) {
        $person['birthPlace'] = [
          '@type' => 'Place',
          'name' => $birth_location,
        ];
      }
    }
    if ($node->hasField('field_death_location') && !$node->get('field_death_location')->isEmpty()) {
      $death_location = $node->get('field_death_location')->value;
      if (!empty($death_location)) {
        $person['deathPlace'] = [
          '@type' => 'Place',
          'name' => $death_location,
        ];
      }
    }

    // Image (with dimensions when available).
    if ($node->hasField('field_bio_pic') && !$node->get('field_bio_pic')->isEmpty()) {
      $field_item = $node->get('field_bio_pic')->first();
      // @phpstan-ignore-next-line
      $file = $field_item ? $field_item->entity : NULL;
      if ($file instanceof File) {
        $image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        $image = [
          '@type' => 'ImageObject',
          'url' => $image_url,
          'contentUrl' => $image_url,
        ];
        $w = $field_item->get('width')->getValue();
        $h = $field_item->get('height')->getValue();
        if ($w) {
          $image['width'] = (int) $w;
        }
        if ($h) {
          $image['height'] = (int) $h;
        }
        $person['image'] = $image;
      }
    }

    // Job title.
    if ($node->hasField('field_position') && !$node->get('field_position')->isEmpty()) {
      $positions = [];
      foreach ($node->get('field_position') as $position) {
        if (!empty($position->value)) {
          $positions[] = $position->value;
        }
      }
      if (!empty($positions)) {
        $person['jobTitle'] = count($positions) === 1 ? $positions[0] : $positions;
      }
    }

    // Affiliation.
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
        $person['affiliation'] = count($affiliations) === 1 ? $affiliations[0] : $affiliations;
      }
    }

    // Nationality.
    if ($node->hasField('field_african_country') && !$node->get('field_african_country')->isEmpty()) {
      $countries = [];
      foreach ($node->get('field_african_country') as $country) {
        // @phpstan-ignore-next-line
        $term = $country->entity;
        if ($term) {
          $countries[] = [
            '@type' => 'Country',
            'name' => $term->getName(),
          ];
        }
      }
      if (!empty($countries)) {
        $person['nationality'] = count($countries) === 1 ? $countries[0] : $countries;
      }
    }

    // knowsAbout from tags.
    if ($node->hasField('field_tags') && !$node->get('field_tags')->isEmpty()) {
      $topics = [];
      foreach ($node->get('field_tags') as $tag) {
        // @phpstan-ignore-next-line
        $term = $tag->entity;
        if ($term) {
          $topics[] = $term->getName();
        }
      }
      if (!empty($topics)) {
        $person['knowsAbout'] = $topics;
      }
    }

    // Description (up to 300 chars; Google accommodates the longer cap).
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $node->get('body')->value;
      $plain = strip_tags($body);
      $description = strlen($plain) > 300 ? substr($plain, 0, 297) . '...' : $plain;
      $person['description'] = $description;
    }

    // External profile URLs.
    if ($node->hasField('field_url') && !$node->get('field_url')->isEmpty()) {
      $urls = [];
      foreach ($node->get('field_url') as $url_item) {
        if (!empty($url_item->uri)) {
          $urls[] = $url_item->uri;
        }
      }
      if (!empty($urls)) {
        $person['sameAs'] = $urls;
      }
    }

    // Citations from the reference list (pipe-delimited field_ref_str), matching
    // the article/event builders so every sourced record exposes its sources.
    $citations = [];
    if ($node->hasField('field_ref_str') && !$node->get('field_ref_str')->isEmpty()) {
      $ref_str = (string) $node->get('field_ref_str')->value;
      if ($ref_str !== '') {
        $citations = array_values(array_filter(array_map('trim', explode('|', $ref_str))));
      }
    }

    // Wrap Person in ProfilePage (Google rich-result type for profiles).
    $schema = [
      '@context' => 'https://schema.org',
      '@type' => 'ProfilePage',
      'url' => $url,
      'dateCreated' => date('c', $node->getCreatedTime()),
      'dateModified' => date('c', $node->getChangedTime()),
      'mainEntity' => $person,
    ];
    if (!empty($citations)) {
      $schema['citation'] = $citations;
    }
    return $schema;
  }

}
