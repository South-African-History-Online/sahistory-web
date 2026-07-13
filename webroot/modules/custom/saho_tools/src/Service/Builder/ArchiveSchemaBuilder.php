<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org structured data for Archive nodes.
 *
 * Top-level @type is Book when an ISBN is present (matches GSC's Book
 * enhancement report); otherwise CreativeWork. ArchiveComponent is kept
 * as additionalType to preserve archival semantics. Bare ArchiveComponent
 * has no Google rich-result template, which is why ~30k archive nodes
 * have been invisible to GSC.
 */
class ArchiveSchemaBuilder implements SchemaOrgBuilderInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

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

    $url = $node->toUrl()->setAbsolute()->toString();
    $has_isbn = $node->hasField('field_isbn') && !$node->get('field_isbn')->isEmpty();

    $schema = [
      '@context' => 'https://schema.org',
      '@type' => $has_isbn ? 'Book' : 'CreativeWork',
      'additionalType' => 'https://schema.org/ArchiveComponent',
      'name' => $node->getTitle(),
      'url' => $url,
      'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $url,
      ],
      'dateModified' => date('c', $node->getChangedTime()),
    ];

    // Prefer the publication title when set.
    if ($node->hasField('field_publication_title') && !$node->get('field_publication_title')->isEmpty()) {
      $schema['name'] = $node->get('field_publication_title')->value;
    }

    // Authors (also surfaced as creator for GSC-friendly CreativeWork).
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
      $schema['creator'] = [
        '@type' => 'Organization',
        'name' => 'South African History Online',
        'url' => \Drupal::request()->getSchemeAndHttpHost(),
      ];
    }

    // Publication date.
    if ($node->hasField('field_archive_publication_date') && !$node->get('field_archive_publication_date')->isEmpty()) {
      $pub_date = $node->get('field_archive_publication_date')->value;
      if (!empty($pub_date)) {
        $schema['datePublished'] = $pub_date;
      }
    }
    elseif ($node->hasField('field_publication_date_archive') && !$node->get('field_publication_date_archive')->isEmpty()) {
      $pub_date = $node->get('field_publication_date_archive')->value;
      if (!empty($pub_date)) {
        $schema['datePublished'] = $pub_date;
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

    // Keywords from tags.
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

    // Holdings, publisher, license.
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $org = [
      '@type' => 'ArchiveOrganization',
      'name' => 'South African History Online',
      'url' => $base_url,
    ];
    $schema['holdingArchive'] = $org;

    // Real publisher (e.g. "Sanchar Publishing House") when the record carries
    // one - what a Book rich result wants; SAHO remains the holding archive and
    // the digital provider. Publication place rides as locationCreated.
    $publisher_name = '';
    if ($node->hasField('field_publishers') && !$node->get('field_publishers')->isEmpty()) {
      $publisher_name = trim(strip_tags((string) $node->get('field_publishers')->value));
    }
    if ($publisher_name !== '') {
      $schema['publisher'] = ['@type' => 'Organization', 'name' => $publisher_name];
      $schema['provider'] = [
        '@type' => 'Organization',
        'name' => 'South African History Online',
        'url' => $base_url,
      ];
      if ($node->hasField('field_publication_place') && !$node->get('field_publication_place')->isEmpty()) {
        $place = trim(strip_tags((string) $node->get('field_publication_place')->value));
        if ($place !== '') {
          $schema['locationCreated'] = ['@type' => 'Place', 'name' => $place];
        }
      }
    }
    else {
      $schema['publisher'] = [
        '@type' => 'Organization',
        'name' => 'South African History Online',
        'url' => $base_url,
        'logo' => [
          '@type' => 'ImageObject',
          'url' => $base_url . '/themes/custom/saho/logo.png',
          'width' => 600,
          'height' => 60,
        ],
      ];
    }
    $schema['copyrightHolder'] = [
      '@type' => 'Organization',
      'name' => 'South African History Online',
    ];
    $schema['license'] = 'https://creativecommons.org/licenses/by-nc-sa/4.0/';
    $schema['isAccessibleForFree'] = TRUE;

    return $schema;
  }

}
