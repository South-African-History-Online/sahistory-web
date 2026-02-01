<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org ScholarlyArticle structured data for Article nodes.
 *
 * Maps SAHO article content to Schema.org ScholarlyArticle vocabulary
 * for optimal discovery by search engines and AI systems.
 */
class ArticleSchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * Constructs an ArticleSchemaBuilder.
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
    return $node_type === 'article';
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
      '@type' => 'ScholarlyArticle',
      'headline' => $node->getTitle(),
      'url' => $node->toUrl()->setAbsolute()->toString(),
      'datePublished' => date('c', $node->getCreatedTime()),
      'dateModified' => date('c', $node->getChangedTime()),
    ];

    // Add abstract/synopsis.
    if ($node->hasField('field_synopsis') && !$node->get('field_synopsis')->isEmpty()) {
      $schema['abstract'] = $node->get('field_synopsis')->value;
    }

    // Add article body.
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $node->get('body')->value;
      // Strip HTML tags for articleBody.
      $schema['articleBody'] = strip_tags($body);
    }

    // Add contributors (authors).
    if ($node->hasField('field_article_author') && !$node->get('field_article_author')->isEmpty()) {
      $authors = [];
      foreach ($node->get('field_article_author') as $author) {
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

    // Add editors.
    if ($node->hasField('field_article_editors') && !$node->get('field_article_editors')->isEmpty()) {
      $editors = [];
      foreach ($node->get('field_article_editors') as $editor) {
        if (!empty($editor->value)) {
          $editors[] = [
            '@type' => 'Person',
            'name' => $editor->value,
          ];
        }
      }
      if (!empty($editors)) {
        $schema['editor'] = count($editors) === 1 ? $editors[0] : $editors;
      }
    }

    // Add main image.
    $image_url = $this->getImageUrl($node, ['field_main_image', 'field_article_image', 'field_image']);
    if ($image_url) {
      $schema['image'] = [
        '@type' => 'ImageObject',
        'url' => $image_url,
        'contentUrl' => $image_url,
      ];
    }

    // Add keywords/tags.
    if ($node->hasField('field_tags') && !$node->get('field_tags')->isEmpty()) {
      $keywords = [];
      foreach ($node->get('field_tags') as $tag) {
        if ($tag->entity) {
          $keywords[] = $tag->entity->getName();
        }
      }
      if (!empty($keywords)) {
        $schema['keywords'] = implode(', ', $keywords);
      }
    }

    // Add spatial coverage (African countries).
    if ($node->hasField('field_african_country') && !$node->get('field_african_country')->isEmpty()) {
      $places = [];
      foreach ($node->get('field_african_country') as $country) {
        if ($country->entity) {
          $places[] = [
            '@type' => 'Place',
            'name' => $country->entity->getName(),
          ];
        }
      }
      if (!empty($places)) {
        $schema['spatialCoverage'] = count($places) === 1 ? $places[0] : $places;
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

    // Add publisher (SAHO organization).
    $schema['publisher'] = $this->getPublisherSchema();

    // Add license and educational properties.
    $schema['license'] = 'https://creativecommons.org/licenses/by-nc-sa/4.0/';
    $schema['isAccessibleForFree'] = TRUE;
    $schema['educationalUse'] = 'research';

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
   * Get SAHO publisher schema.
   *
   * @return array
   *   Publisher organization schema.
   */
  protected function getPublisherSchema(): array {
    $request = \Drupal::request();
    $base_url = $request->getSchemeAndHttpHost();

    return [
      '@type' => 'Organization',
      'name' => 'South African History Online',
      'url' => $base_url,
      'logo' => [
        '@type' => 'ImageObject',
        'url' => $base_url . '/themes/custom/saho/logo.png',
      ],
    ];
  }

}
