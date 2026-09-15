<?php

declare(strict_types=1);

namespace Drupal\saho_utils\RegisterGrid;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\saho_utils\Service\ImageExtractorService;

/**
 * South African provinces: one card per province term (field_places_level3).
 *
 * Replaces the former sa_provinces_block. Cards link into the /places
 * landing with the province register applied.
 */
final class SaProvincesSource implements RegisterSourceInterface {

  use StringTranslationTrait;

  /**
   * Province name => one-line description.
   */
  private const PROVINCES = [
    'Eastern Cape' => 'Birthplace of many anti-apartheid leaders',
    'Free State' => "Heart of South Africa's goldfields",
    'Gauteng' => 'Economic hub and Johannesburg',
    'KwaZulu-Natal' => 'Rich Zulu heritage and coastal beauty',
    'Limpopo' => 'Ancient kingdoms and Mapungubwe',
    'Mpumalanga' => 'Land of the rising sun',
    'North West' => 'Cradle of humankind region',
    'Northern Cape' => 'Diamond fields and vast landscapes',
    'Western Cape' => 'Cape of Good Hope and rich colonial history',
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ImageExtractorService $imageExtractor,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return 'sa_provinces';
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'South African Provinces';
  }

  /**
   * {@inheritdoc}
   */
  public function subsetOptions(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function emptyText(): string {
    return (string) $this->t('No province data available.');
  }

  /**
   * {@inheritdoc}
   */
  public function cacheTags(): array {
    return ['node_list:place', 'taxonomy_term_list:field_places_level3'];
  }

  /**
   * {@inheritdoc}
   */
  public function items(string $subset, bool $show_count, bool $show_featured): array {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $items = [];
    foreach (self::PROVINCES as $name => $description) {
      $terms = $storage->loadByProperties(['vid' => 'field_places_level3', 'name' => $name]);
      $term = $terms ? reset($terms) : NULL;
      if (!$term) {
        continue;
      }
      $tid = (int) $term->id();
      $recent = $this->recentPlaces($tid, 5);
      $count = $show_count ? $this->countPlaces($tid) : 0;
      $featured = ($show_featured && $recent !== []) ? (string) reset($recent)->label() : '';
      $image = '';
      foreach ($recent as $place) {
        $image = (string) ($this->imageExtractor->extractImageUrl($place) ?? '');
        if ($image !== '') {
          break;
        }
      }
      $items[] = [
        'type' => 'place',
        'badge_label' => '',
        'kicker' => $show_count ? $count . ($count === 1 ? ' PLACE' : ' PLACES') : '',
        'title' => $name,
        'excerpt' => $featured !== '' ? $featured : $description,
        'meta' => '',
        'href' => '/places?tid_2[' . $tid . ']=' . $tid,
        'image' => $image,
        'image_alt' => $image !== '' ? $name : '',
      ];
    }
    return $items;
  }

  /**
   * Counts published places in the province.
   */
  private function countPlaces(int $tid): int {
    return (int) $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'place')
      ->condition('status', 1)
      ->condition('field_places_level3', $tid)
      ->accessCheck(TRUE)
      ->count()
      ->execute();
  }

  /**
   * Newest published places in the province.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Newest first.
   */
  private function recentPlaces(int $tid, int $limit): array {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'place')
      ->condition('status', 1)
      ->condition('field_places_level3', $tid)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->execute();
    return $nids ? array_values($this->entityTypeManager->getStorage('node')->loadMultiple($nids)) : [];
  }

}
