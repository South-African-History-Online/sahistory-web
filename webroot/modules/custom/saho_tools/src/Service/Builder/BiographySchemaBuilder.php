<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Schema\SchemaDates;

/**
 * Builds Schema.org ProfilePage structured data for Biography nodes.
 *
 * Top-level @type is ProfilePage (Google's 2024+ rich-result type for
 * person pages) with the Person nested under mainEntity. ProfilePage IS
 * reported in GSC's enhancement reports; bare Person is not.
 */
class BiographySchemaBuilder extends SchemaBuilderBase {

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

    $url = $this->canonicalNodeUrl($node);
    $ref_url = $this->refUrl($node);

    $person = [
      '@type' => 'Person',
      '@id' => ($ref_url ?? $url) . '#person',
      'name' => $node->getTitle(),
      'url' => $url,
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

    // Dates: the structured datetime pair covers ~900 records; the legacy
    // free-text field_dob/field_dod pair covers ~2,200/2,600 more in shapes
    // SchemaDates can normalise ("19-September-1925", "1918"). Prose stays
    // out of Date properties.
    $birth = $this->firstDate($node, ['field_drupal_birth_date', 'field_dob']);
    if ($birth !== NULL) {
      $person['birthDate'] = $birth;
    }
    $death = $this->firstDate($node, ['field_drupal_death_date', 'field_dod']);
    if ($death !== NULL) {
      $person['deathDate'] = $death;
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

    // knowsAbout from tags plus people categories (the far better-populated
    // vocabulary: 11k+ records carry field_people_category, only ~80 tags).
    $topics = [];
    foreach (['field_tags', 'field_people_category'] as $topic_field) {
      if ($node->hasField($topic_field) && !$node->get($topic_field)->isEmpty()) {
        foreach ($node->get($topic_field) as $tag) {
          // @phpstan-ignore-next-line
          $term = $tag->entity;
          if ($term) {
            $topics[] = $term->getName();
          }
        }
      }
    }
    if (!empty($topics)) {
      $person['knowsAbout'] = array_values(array_unique($topics));
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
    // mainEntityOfPage never appears here: on a ProfilePage the page IS
    // the entity's page (mainEntity carries the relationship), and Search
    // Console flags the field as unrecognized on Profile page items - the
    // property is for content entities (Article etc.), not page types.
    $schema = [
      '@context' => 'https://schema.org',
      '@type' => 'ProfilePage',
      'dateCreated' => date('c', $node->getCreatedTime()),
      'dateModified' => date('c', $node->getChangedTime()),
      'mainEntity' => $person,
    ] + $this->identityProperties($node);
    unset($schema['mainEntityOfPage']);
    if (!empty($citations)) {
      $schema['citation'] = $citations;
    }
    return $schema;
  }

  /**
   * Returns the first schema-safe date found across the given fields.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The biography node.
   * @param string[] $fields
   *   Field names in order of preference.
   *
   * @return string|null
   *   A normalised ISO (partial) date, or NULL when none normalises.
   */
  protected function firstDate(NodeInterface $node, array $fields): ?string {
    foreach ($fields as $field) {
      if ($node->hasField($field) && !$node->get($field)->isEmpty()) {
        $date = SchemaDates::normalize((string) $node->get($field)->value);
        if ($date !== NULL) {
          return $date;
        }
      }
    }
    return NULL;
  }

}
