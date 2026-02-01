<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org ArchiveComponent structured data for Archive nodes.
 *
 * Maps SAHO archive materials (documents, publications, media) to
 * Schema.org ArchiveComponent vocabulary for archival discovery.
 */
class ArchiveSchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * Constructs an ArchiveSchemaBuilder.
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
    return $node_type === 'archive';
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
      '@type' => 'ArchiveComponent',
      'name' => $node->getTitle(),
      'url' => $node->toUrl()->setAbsolute()->toString(),
      'dateModified' => date('c', $node->getChangedTime()),
    ];

    // Add publication title (if different from node title).
    if ($node->hasField('field_publication_title') && !$node->get('field_publication_title')->isEmpty()) {
      $schema['name'] = $node->get('field_publication_title')->value;
    }

    // Add author.
    if ($node->hasField('field_author') && !$node->get('field_author')->isEmpty()) {
      $authors = [];
      foreach ($node->get('field_author') as $author) {
        if (!empty($author->value)) {
          $authors[] = [
            '@type' => 'Person',
            'name' => $author->value,
          ];
        }
      }
      if (!empty($authors)) {
        $schema['author'] = count($authors) === 1 ? $authors[0] : $authors;
      }
    }

    // Add publication date.
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

    // Add description from body.
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $node->get('body')->value;
      $description = strip_tags($body);
      // Limit to 500 characters.
      if (strlen($description) > 500) {
        $description = substr($description, 0, 497) . '...';
      }
      $schema['description'] = $description;
    }

    // Add archive image.
    if ($node->hasField('field_archive_image') && !$node->get('field_archive_image')->isEmpty()) {
      $field_value = $node->get('field_archive_image');
      if ($field_value->entity instanceof File) {
        $image_url = $this->fileUrlGenerator->generateAbsoluteString($field_value->entity->getFileUri());
        $schema['image'] = [
          '@type' => 'ImageObject',
          'url' => $image_url,
          'contentUrl' => $image_url,
        ];
      }
    }

    // Add ISBN if available.
    if ($node->hasField('field_isbn') && !$node->get('field_isbn')->isEmpty()) {
      $schema['isbn'] = $node->get('field_isbn')->value;
    }

    // Add language.
    if ($node->hasField('field_language') && !$node->get('field_language')->isEmpty()) {
      $languages = [];
      foreach ($node->get('field_language') as $language) {
        if ($language->entity) {
          $languages[] = $language->entity->getName();
        }
      }
      if (!empty($languages)) {
        $schema['inLanguage'] = count($languages) === 1 ? $languages[0] : $languages;
      }
    }
    else {
      $schema['inLanguage'] = 'en-ZA';
    }

    // Add source/provider.
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
        $schema['provider'] = count($sources) === 1 ? $sources[0] : $sources;
      }
    }

    // Add file upload as associatedMedia.
    if ($node->hasField('field_file_upload') && !$node->get('field_file_upload')->isEmpty()) {
      $files = [];
      foreach ($node->get('field_file_upload') as $file_ref) {
        if ($file_ref->entity instanceof File) {
          $file_url = $this->fileUrlGenerator->generateAbsoluteString($file_ref->entity->getFileUri());
          $files[] = [
            '@type' => 'MediaObject',
            'contentUrl' => $file_url,
            'encodingFormat' => $file_ref->entity->getMimeType(),
            'name' => $file_ref->entity->getFilename(),
          ];
        }
      }
      if (!empty($files)) {
        $schema['associatedMedia'] = count($files) === 1 ? $files[0] : $files;
      }
    }

    // Add holdings as part of SAHO archive.
    $schema['holdingArchive'] = [
      '@type' => 'ArchiveOrganization',
      'name' => 'South African History Online',
      'url' => \Drupal::request()->getSchemeAndHttpHost(),
    ];

    // Add license.
    $schema['license'] = 'https://creativecommons.org/licenses/by-nc-sa/4.0/';
    $schema['isAccessibleForFree'] = TRUE;

    return $schema;
  }

}
