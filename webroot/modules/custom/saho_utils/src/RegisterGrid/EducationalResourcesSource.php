<?php

declare(strict_types=1);

namespace Drupal\saho_utils\RegisterGrid;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\saho_utils\Service\TaxonomyCounterService;

/**
 * Educational resource types: one card per curated term.
 *
 * Replaces the former educational_resources_block (the last of the four
 * register blocks still rendering pre-Open-Record markup and a carousel).
 */
final class EducationalResourcesSource implements RegisterSourceInterface {

  use StringTranslationTrait;

  /**
   * Type id => label, description, term name, vocabulary/field.
   */
  private const TYPES = [
    'caps' => [
      'label' => 'CAPS Documents',
      'description' => 'Curriculum Assessment Policy Statements',
      'term' => 'CAPS Document',
      'field' => 'field_media_library_type',
    ],
    'books' => [
      'label' => 'Life Orientation and School Books',
      'description' => 'Educational books and reading materials',
      'term' => 'School book',
      'field' => 'field_media_library_type',
    ],
    'aids' => [
      'label' => 'Aids & Resources',
      'description' => 'Educational aids, teaching tools and classroom resources',
      'term' => 'Aids & Resources',
      'field' => 'field_classroom_categories',
    ],
    'technical' => [
      'label' => 'Technical Skills',
      'description' => 'Technical and vocational education resources',
      'term' => 'Technical skills',
      'field' => 'field_classroom_categories',
    ],
    'teaching' => [
      'label' => 'Lecture Materials',
      'description' => 'Lectures and teaching presentations',
      'term' => 'Lecture',
      'field' => 'field_media_library_type',
    ],
    'policy' => [
      'label' => 'Policy Documents',
      'description' => 'Educational policies and guidelines',
      'term' => 'Official Document - Policy documents',
      'field' => 'field_media_library_type',
    ],
  ];

  /**
   * Subset => type ids.
   */
  private const SUBSETS = [
    'docs' => ['caps', 'policy'],
    'materials' => ['books', 'teaching'],
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TaxonomyCounterService $taxonomyCounter,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return 'educational_resources';
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Educational Resources';
  }

  /**
   * {@inheritdoc}
   */
  public function subsetOptions(): array {
    return [
      'all' => (string) $this->t('All resource types'),
      'docs' => (string) $this->t('Documents only (CAPS, policy)'),
      'materials' => (string) $this->t('Learning materials only (books, lectures)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function emptyText(): string {
    return (string) $this->t('No educational resources available.');
  }

  /**
   * {@inheritdoc}
   */
  public function cacheTags(): array {
    return ['node_list', 'taxonomy_term_list'];
  }

  /**
   * {@inheritdoc}
   */
  public function items(string $subset, bool $show_count, bool $show_featured): array {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $wanted = self::SUBSETS[$subset] ?? array_keys(self::TYPES);
    $items = [];
    foreach (self::TYPES as $type_id => $type) {
      if (!in_array($type_id, $wanted, TRUE)) {
        continue;
      }
      // The vocabulary carries the same machine name as the field.
      $terms = $storage->loadByProperties(['vid' => $type['field'], 'name' => $type['term']]);
      $term = $terms ? reset($terms) : NULL;
      if (!$term) {
        continue;
      }
      $tid = (int) $term->id();
      $count = $show_count ? $this->taxonomyCounter->countNodesByTerm($tid, [], [$type['field']]) : 0;
      $featured = '';
      if ($show_featured) {
        $entity = $this->taxonomyCounter->getRecentEntity($tid, 'node', [], [$type['field']]);
        $featured = $entity ? (string) $entity->label() : '';
      }
      $items[] = [
        'type' => 'archive',
        'badge_label' => '',
        'kicker' => $show_count ? $count . ($count === 1 ? ' RESOURCE' : ' RESOURCES') : '',
        'title' => $type['label'],
        'excerpt' => $type['description'],
        'meta' => $featured !== '' ? (string) $this->t('Latest: @title', ['@title' => $featured]) : '',
        'href' => $term->toUrl()->toString(),
        'image' => '',
        'image_alt' => '',
      ];
    }
    return $items;
  }

}
