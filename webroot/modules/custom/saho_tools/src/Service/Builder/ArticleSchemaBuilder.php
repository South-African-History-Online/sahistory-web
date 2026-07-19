<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;

/**
 * Builds Schema.org Article structured data for Article nodes.
 *
 * Top-level @type is Article (Google Search Console's "Articles" report
 * only counts Article/NewsArticle/BlogPosting; ScholarlyArticle is kept
 * as additionalType to preserve semantics without sacrificing GSC
 * visibility).
 */
class ArticleSchemaBuilder extends SchemaBuilderBase {

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
      '@type' => 'Article',
      'additionalType' => 'https://schema.org/ScholarlyArticle',
      'headline' => $node->getTitle(),
      'datePublished' => date('c', $node->getCreatedTime()),
      'dateModified' => date('c', $node->getChangedTime()),
    ] + $this->identityProperties($node);

    // Synopsis -> abstract; body -> articleBody + description + wordCount.
    if ($node->hasField('field_synopsis') && !$node->get('field_synopsis')->isEmpty()) {
      $schema['abstract'] = $node->get('field_synopsis')->value;
    }
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $node->get('body')->value;
      $plain = strip_tags($body);
      $schema['articleBody'] = $plain;
      $schema['wordCount'] = str_word_count($plain);
      // Description: prefer synopsis if present, else first 500 chars of body.
      if (isset($schema['abstract'])) {
        $schema['description'] = $schema['abstract'];
      }
      else {
        $schema['description'] = strlen($plain) > 500 ? substr($plain, 0, 497) . '...' : $plain;
      }
    }

    // Authors.
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

    // Editors.
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

    // Main image with dimensions when available (Google: >=1200px for rich).
    $image_data = $this->getImageData($node, ['field_main_image', 'field_article_image', 'field_image']);
    if ($image_data) {
      $schema['image'] = $image_data;
    }

    // Keywords + articleSection from tags.
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
        $schema['articleSection'] = $keywords[0];
      }
    }

    // Spatial coverage.
    if ($node->hasField('field_african_country') && !$node->get('field_african_country')->isEmpty()) {
      $places = [];
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
      if (!empty($places)) {
        $schema['spatialCoverage'] = count($places) === 1 ? $places[0] : $places;
      }
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

    $schema['publisher'] = $this->getPublisherSchema();
    $schema['license'] = 'https://creativecommons.org/licenses/by-nc-sa/4.0/';
    $schema['isAccessibleForFree'] = TRUE;
    $schema['educationalUse'] = 'research';
    $schema['inLanguage'] = 'en-ZA';

    return $schema;
  }

  /**
   * Build an ImageObject with width/height when available.
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

      if ($entity instanceof File) {
        $url = $this->fileUrlGenerator->generateAbsoluteString($entity->getFileUri());
        $image = [
          '@type' => 'ImageObject',
          'url' => $url,
          'contentUrl' => $url,
        ];
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

      if ($entity instanceof ContentEntityInterface && $entity->hasField('field_media_image')) {
        if (!$entity->get('field_media_image')->isEmpty()) {
          $media_item = $entity->get('field_media_image')->first();
          // @phpstan-ignore-next-line
          $file = $media_item ? $media_item->entity : NULL;
          if ($file instanceof File) {
            $url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
            $image = [
              '@type' => 'ImageObject',
              'url' => $url,
              'contentUrl' => $url,
            ];
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
