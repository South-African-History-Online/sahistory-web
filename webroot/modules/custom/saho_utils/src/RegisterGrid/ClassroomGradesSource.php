<?php

declare(strict_types=1);

namespace Drupal\saho_utils\RegisterGrid;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Classroom grades: one card per "History Classroom Grade N" term.
 *
 * Grades come from the live `classroom` vocabulary; counts and the featured
 * topic are articles tagged through field_classroom. Replaces the former
 * history_classroom_block.
 */
final class ClassroomGradesSource implements RegisterSourceInterface {

  use StringTranslationTrait;

  /**
   * Subset => grade numbers.
   */
  private const SUBSETS = [
    'all' => [4, 5, 6, 7, 8, 9, 10, 11, 12],
    'primary' => [4, 5, 6, 7],
    'secondary' => [8, 9, 10, 11, 12],
    'high_school' => [10, 11, 12],
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return 'classroom_grades';
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'History by Grade';
  }

  /**
   * {@inheritdoc}
   */
  public function subsetOptions(): array {
    return [
      'all' => (string) $this->t('All grades (4-12)'),
      'primary' => (string) $this->t('Intermediate phase (4-7)'),
      'secondary' => (string) $this->t('Senior and FET phase (8-12)'),
      'high_school' => (string) $this->t('FET phase (10-12)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function emptyText(): string {
    return (string) $this->t('No classroom content available.');
  }

  /**
   * {@inheritdoc}
   */
  public function cacheTags(): array {
    return ['node_list:article', 'taxonomy_term_list:classroom'];
  }

  /**
   * {@inheritdoc}
   */
  public function items(string $subset, bool $show_count, bool $show_featured): array {
    $wanted = self::SUBSETS[$subset] ?? self::SUBSETS['all'];
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'classroom']);
    $items = [];
    foreach ($terms as $term) {
      $grade = self::gradeNumber($term->getName());
      if (!in_array($grade, $wanted, TRUE)) {
        continue;
      }
      $tid = (int) $term->id();
      $count = $show_count ? $this->countArticles($tid) : 0;
      $featured = $show_featured ? $this->featuredArticle($tid) : NULL;
      $label = (string) $this->t('Grade @number', ['@number' => $grade]);
      $items[] = [
        'sort' => $grade,
        'type' => 'archive',
        'badge_label' => $label,
        'kicker' => strtoupper(self::phase($grade)),
        'title' => $featured ?? (string) $this->t('@grade resources', ['@grade' => $label]),
        'excerpt' => '',
        'meta' => $show_count ? $this->formatCount($count, 'RESOURCE') : '',
        'href' => $term->toUrl()->toString(),
        'image' => '',
        'image_alt' => '',
      ];
    }
    usort($items, static fn(array $a, array $b): int => $a['sort'] <=> $b['sort']);
    return $items;
  }

  /**
   * Parses the grade number out of a term name ("... Grade Eight" => 8).
   */
  public static function gradeNumber(string $term_name): int {
    $words = [
      'Four' => 4, 'Five' => 5, 'Six' => 6, 'Seven' => 7, 'Eight' => 8,
      'Nine' => 9, 'Ten' => 10, 'Eleven' => 11, 'Twelve' => 12,
    ];
    foreach ($words as $word => $number) {
      if (stripos($term_name, $word) !== FALSE) {
        return $number;
      }
    }
    return 0;
  }

  /**
   * CAPS phase label for a grade number.
   */
  public static function phase(int $grade): string {
    return $grade <= 6 ? 'Intermediate Phase' : ($grade <= 9 ? 'Senior Phase' : 'FET Phase');
  }

  /**
   * Formats "N RESOURCE(S)".
   */
  private function formatCount(int $count, string $noun): string {
    return $count . ' ' . $noun . ($count === 1 ? '' : 'S');
  }

  /**
   * Counts published articles tagged with the grade term.
   */
  private function countArticles(int $tid): int {
    return (int) $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_classroom', $tid)
      ->accessCheck(TRUE)
      ->count()
      ->execute();
  }

  /**
   * Newest published article title for the grade term.
   */
  private function featuredArticle(int $tid): ?string {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_classroom', $tid)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();
    if ($nids === []) {
      return NULL;
    }
    $node = $this->entityTypeManager->getStorage('node')->load(reset($nids));
    return $node ? (string) $node->label() : NULL;
  }

}
