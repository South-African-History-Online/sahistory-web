<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Schema\ProvenanceIds;
use Drupal\saho_tools\Schema\SchemaDates;

/**
 * Builds Schema.org structured data for Archive nodes.
 *
 * Top-level @type is Book when an ISBN is present (matches GSC's Book
 * enhancement report); otherwise CreativeWork. ArchiveComponent is kept
 * as additionalType to preserve archival semantics. Bare ArchiveComponent
 * has no Google rich-result template, which is why ~30k archive nodes
 * have been invisible to GSC.
 *
 * Carries the Catalyst provenance fields (#494): original creators with
 * their roles, creation date, provider source pages (sameAs), persistent
 * identifiers (ARK/DOI/Handle as PropertyValue identifiers) and the
 * provenance note as creditText.
 */
class ArchiveSchemaBuilder extends SchemaBuilderBase {

  /**
   * Creator roles that describe an organisation rather than a person.
   */
  private const ORG_ROLES = ['institution' => TRUE, 'publisher' => TRUE];

  /**
   * {@inheritdoc}
   */
  public function supports(string $node_type): bool {
    return $node_type === 'archive';
  }

  /**
   * {@inheritdoc}
   */
  public function build(NodeInterface $node): array {
    if (!$this->supports($node->getType())) {
      return [];
    }

    $has_isbn = $node->hasField('field_isbn') && !$node->get('field_isbn')->isEmpty();

    $schema = [
      '@context' => 'https://schema.org',
      '@type' => $has_isbn ? 'Book' : 'CreativeWork',
      'additionalType' => 'https://schema.org/ArchiveComponent',
      'name' => $node->getTitle(),
      'dateModified' => date('c', $node->getChangedTime()),
    ] + $this->identityProperties($node);

    // Prefer the publication title when set.
    if ($node->hasField('field_publication_title') && !$node->get('field_publication_title')->isEmpty()) {
      $schema['name'] = $node->get('field_publication_title')->value;
    }

    // Statement of responsibility: original creators (with roles) beat the
    // legacy author string, which beats the institutional fallback.
    $creators = $this->buildCreators($node);
    if ($creators) {
      $schema['creator'] = count($creators) === 1 ? $creators[0] : $creators;
      // Only flat Person/Organization entries make sense as "author";
      // Role wrappers stay on creator.
      $schema['author'] = $schema['creator'];
    }
    else {
      $authors = [];
      if ($node->hasField('field_author') && !$node->get('field_author')->isEmpty()) {
        foreach ($node->get('field_author') as $author) {
          if (!empty($author->value)) {
            $authors[] = [
              '@type' => 'Person',
              'name' => $author->value,
            ];
          }
        }
      }
      if (!empty($authors)) {
        $schema['author'] = count($authors) === 1 ? $authors[0] : $authors;
        $schema['creator'] = $schema['author'];
      }
      else {
        // Fall back to SAHO as institutional creator so the field is never
        // empty (Google flags missing creator on CreativeWork-like items).
        $schema['creator'] = $this->organizationRef();
      }
    }

    // When the work itself predates its publication (a 1976 photograph
    // published by an archive in 2019), say so.
    if ($node->hasField('field_original_created_date') && !$node->get('field_original_created_date')->isEmpty()) {
      $created = SchemaDates::normalize((string) $node->get('field_original_created_date')->value);
      if ($created !== NULL) {
        $schema['dateCreated'] = $created;
      }
    }

    // Publication date: the structured datetime slot wins; the 21k free-text
    // legacy values only pass through when they normalise to a real ISO
    // (partial) date - prose like "circa 1918" is omitted, not emitted.
    $published = NULL;
    if ($node->hasField('field_archive_publication_date') && !$node->get('field_archive_publication_date')->isEmpty()) {
      $published = SchemaDates::normalize((string) $node->get('field_archive_publication_date')->value);
    }
    if ($published === NULL && $node->hasField('field_publication_date_archive') && !$node->get('field_publication_date_archive')->isEmpty()) {
      $published = SchemaDates::normalize((string) $node->get('field_publication_date_archive')->value);
    }
    if ($published !== NULL) {
      $schema['datePublished'] = $published;
    }

    // Original source links: provider pages become sameAs, persistent IDs
    // (ARK/DOI/Handle/PURL) become typed identifiers alongside the SAHO ref.
    $same_as = [];
    $persistent = [];
    if ($node->hasField('field_original_source_url') && !$node->get('field_original_source_url')->isEmpty()) {
      foreach ($node->get('field_original_source_url') as $link) {
        // @phpstan-ignore-next-line
        $uri = (string) $link->uri;
        if ($uri === '') {
          continue;
        }
        // @phpstan-ignore-next-line
        $title = (string) ($link->title ?? '');
        if (ProvenanceIds::isPersistent($uri, $title)) {
          $persistent[] = [
            '@type' => 'PropertyValue',
            'propertyID' => ProvenanceIds::propertyId($uri) ?? 'persistent',
            'value' => $uri,
          ];
        }
        else {
          $same_as[] = $uri;
        }
      }
    }
    if ($same_as) {
      $schema['sameAs'] = count($same_as) === 1 ? $same_as[0] : $same_as;
    }
    if ($persistent) {
      $identifiers = isset($schema['identifier']) ? [$schema['identifier']] : [];
      $identifiers = array_merge($identifiers, $persistent);
      $schema['identifier'] = count($identifiers) === 1 ? $identifiers[0] : $identifiers;
    }

    // Provenance note: the human acknowledgement of where the work came from.
    if ($node->hasField('field_provenance_note') && !$node->get('field_provenance_note')->isEmpty()) {
      $note = trim(strip_tags((string) $node->get('field_provenance_note')->value));
      $note = trim(preg_replace('/\[saho_import:[^\]]*\]/', '', $note) ?? $note);
      if ($note !== '') {
        $schema['creditText'] = $note;
      }
    }

    // Rights statement.
    if ($node->hasField('field_copyright') && !$node->get('field_copyright')->isEmpty()) {
      $rights = trim(strip_tags((string) $node->get('field_copyright')->value));
      if ($rights !== '') {
        $schema['copyrightNotice'] = $rights;
      }
    }

    // Description from body.
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $node->get('body')->value;
      $plain = strip_tags($body);
      $description = strlen($plain) > 500 ? substr($plain, 0, 497) . '...' : $plain;
      $schema['description'] = $description;
    }

    // Archive image.
    if ($node->hasField('field_archive_image') && !$node->get('field_archive_image')->isEmpty()) {
      $field_item = $node->get('field_archive_image')->first();
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
        $schema['image'] = $image;
      }
    }

    if ($has_isbn) {
      $schema['isbn'] = $node->get('field_isbn')->value;
    }

    // Editors + contributors complete the statement of responsibility.
    $editors = [];
    if ($node->hasField('field_editors') && !$node->get('field_editors')->isEmpty()) {
      foreach ($node->get('field_editors') as $editor) {
        $name = trim(strip_tags($editor->getString()));
        if ($name !== '') {
          $editors[] = ['@type' => 'Person', 'name' => $name];
        }
      }
    }
    if (!empty($editors)) {
      $schema['editor'] = count($editors) === 1 ? $editors[0] : $editors;
    }
    $contributors = [];
    if ($node->hasField('field_contributor') && !$node->get('field_contributor')->isEmpty()) {
      foreach ($node->get('field_contributor') as $contributor) {
        $name = trim(strip_tags($contributor->getString()));
        if ($name !== '') {
          $contributors[] = ['@type' => 'Person', 'name' => $name];
        }
      }
    }
    if (!empty($contributors)) {
      $schema['contributor'] = count($contributors) === 1 ? $contributors[0] : $contributors;
    }

    // Keywords from tags; format taxonomy carries genre.
    if ($node->hasField('field_tags') && !$node->get('field_tags')->isEmpty()) {
      $keywords = [];
      foreach ($node->get('field_tags') as $tag) {
        // @phpstan-ignore-next-line
        $term = $tag->entity;
        if ($term) {
          $keywords[] = $term->getName();
        }
      }
      if (!empty($keywords)) {
        $schema['keywords'] = implode(', ', $keywords);
      }
    }
    if ($node->hasField('field_media_library_type') && !$node->get('field_media_library_type')->isEmpty()) {
      $genres = [];
      foreach ($node->get('field_media_library_type') as $format) {
        // @phpstan-ignore-next-line
        $term = $format->entity;
        if ($term) {
          $genres[] = $term->getName();
        }
      }
      if (!empty($genres)) {
        $schema['genre'] = count($genres) === 1 ? $genres[0] : $genres;
      }
    }

    // Language.
    if ($node->hasField('field_language') && !$node->get('field_language')->isEmpty()) {
      $languages = [];
      foreach ($node->get('field_language') as $language) {
        // @phpstan-ignore-next-line
        $term = $language->entity;
        if ($term) {
          $languages[] = $term->getName();
        }
      }
      if (!empty($languages)) {
        $schema['inLanguage'] = count($languages) === 1 ? $languages[0] : $languages;
      }
    }
    else {
      $schema['inLanguage'] = 'en-ZA';
    }

    // Original source (semantically a sourceOrganization, not a provider).
    if ($node->hasField('field_source') && !$node->get('field_source')->isEmpty()) {
      $sources = [];
      foreach ($node->get('field_source') as $source) {
        if (!empty($source->value)) {
          $sources[] = [
            '@type' => 'Organization',
            'name' => $source->value,
          ];
        }
      }
      if (!empty($sources)) {
        $schema['sourceOrganization'] = count($sources) === 1 ? $sources[0] : $sources;
      }
    }

    // File downloads as DataDownload (more specific than MediaObject).
    if ($node->hasField('field_file_upload') && !$node->get('field_file_upload')->isEmpty()) {
      $files = [];
      foreach ($node->get('field_file_upload') as $file_ref) {
        // @phpstan-ignore-next-line
        $file = $file_ref->entity;
        if ($file instanceof File) {
          $file_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
          $files[] = [
            '@type' => 'DataDownload',
            'contentUrl' => $file_url,
            'encodingFormat' => $file->getMimeType(),
            'name' => $file->getFilename(),
          ];
        }
      }
      if (!empty($files)) {
        $schema['associatedMedia'] = count($files) === 1 ? $files[0] : $files;
      }
    }

    // Holdings, publisher, license - all SAHO references collapse onto the
    // single sitewide organization entity via @id.
    $schema['holdingArchive'] = $this->organizationRef('ArchiveOrganization');

    // Real publisher (e.g. "Sanchar Publishing House") when the record carries
    // one - what a Book rich result wants; SAHO remains the holding archive and
    // the digital provider. Publication place rides as locationCreated.
    $publisher_name = '';
    if ($node->hasField('field_publishers') && !$node->get('field_publishers')->isEmpty()) {
      $publisher_name = trim(strip_tags((string) $node->get('field_publishers')->value));
    }
    if ($publisher_name !== '') {
      $schema['publisher'] = ['@type' => 'Organization', 'name' => $publisher_name];
      $schema['provider'] = $this->organizationRef();
      if ($node->hasField('field_publication_place') && !$node->get('field_publication_place')->isEmpty()) {
        $place = trim(strip_tags((string) $node->get('field_publication_place')->value));
        if ($place !== '') {
          $schema['locationCreated'] = ['@type' => 'Place', 'name' => $place];
        }
      }
    }
    else {
      $schema['publisher'] = $this->organizationRef();
    }
    $schema['copyrightHolder'] = $this->organizationRef();
    $schema['license'] = 'https://creativecommons.org/licenses/by-nc-sa/4.0/';
    $schema['isAccessibleForFree'] = TRUE;

    return $schema;
  }

  /**
   * Builds creator entries from the Catalyst provenance fields.
   *
   * Creators pair with roles by delta; a role wraps the entry in the
   * schema.org Role pattern, and organisational roles type the inner
   * entity as Organization.
   *
   * @return array
   *   Creator entries, empty when the provenance fields are unpopulated.
   */
  protected function buildCreators(NodeInterface $node): array {
    if (!$node->hasField('field_original_creator') || $node->get('field_original_creator')->isEmpty()) {
      return [];
    }
    $roles = [];
    if ($node->hasField('field_original_creator_role')) {
      foreach ($node->get('field_original_creator_role') as $delta => $item) {
        // @phpstan-ignore-next-line
        $roles[$delta] = (string) $item->value;
      }
    }
    $creators = [];
    foreach ($node->get('field_original_creator') as $delta => $item) {
      // @phpstan-ignore-next-line
      $name = trim((string) $item->value);
      if ($name === '') {
        continue;
      }
      $role = $roles[$delta] ?? '';
      $entity_type = isset(self::ORG_ROLES[$role]) ? 'Organization' : 'Person';
      $entity = ['@type' => $entity_type, 'name' => $name];
      if ($role !== '') {
        $creators[] = [
          '@type' => 'Role',
          'roleName' => $role,
          'creator' => $entity,
        ];
      }
      else {
        $creators[] = $entity;
      }
    }
    return $creators;
  }

}
