<?php

declare(strict_types=1);

namespace Drupal\saho_utils\RegisterGrid;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\saho_utils\Service\TaxonomyCounterService;

/**
 * African regions: one card per region, counting records by country term.
 *
 * Replaces the former africa_regions_block. Counts every country of a
 * region in one query (countMultipleTerms) instead of one query per country
 * twice over, which the old block did.
 */
final class AfricaRegionsSource implements RegisterSourceInterface {

  use StringTranslationTrait;

  /**
   * Region => label, description, landing URL, country term names.
   */
  private const REGIONS = [
    'north' => [
      'label' => 'Northern Africa',
      'description' => 'View the histories of Northern African nations',
      'url' => '/africa/northern-africa',
      'countries' => ['Algeria', 'Egypt', 'Libya', 'Morocco', 'Sudan', 'Tunisia'],
    ],
    'east' => [
      'label' => 'Eastern Africa',
      'description' => 'Discover stories from the Eastern African region',
      'url' => '/africa/eastern-africa',
      'countries' => ['Burundi', 'Djibouti', 'Eritrea', 'Ethiopia', 'Kenya', 'Rwanda', 'Somalia', 'Tanzania', 'Uganda'],
    ],
    'southern' => [
      'label' => 'Southern Africa',
      'description' => 'Rich histories from Southern African countries',
      'url' => '/africa/southern-africa',
      'countries' => [
        'Botswana', 'Lesotho', 'Malawi', 'Mozambique', 'Namibia', 'South Africa', 'Eswatini', 'Zambia', 'Zimbabwe',
      ],
    ],
    'central' => [
      'label' => 'Central Africa',
      'description' => 'Central African nations and their heritage',
      'url' => '/africa/central-africa',
      'countries' => [
        'Angola', 'Cameroon', 'Central African Republic', 'Chad', 'Congo/Congo-Brazzaville',
        'Democratic Republic of Congo/Congo-Kinshasha', 'Equatorial Guinea', 'Gabon',
      ],
    ],
    'west' => [
      'label' => 'Western Africa',
      'description' => 'Western African stories and historical narratives',
      'url' => '/africa/western-africa',
      'countries' => [
        'Benin', 'Burkina Faso', 'Cabo Verde/Cape Verde', "Côte d'Ivoire/Ivory Coast", 'Gambia', 'Ghana',
        'Guinea', 'Guinea-Bissau', 'Liberia', 'Mali', 'Mauritania', 'Niger', 'Nigeria', 'Senegal',
        'Sierra Leone', 'Togo',
      ],
    ],
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
    return 'africa_regions';
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Africa';
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
    return (string) $this->t('No regional content available.');
  }

  /**
   * {@inheritdoc}
   */
  public function cacheTags(): array {
    return ['node_list', 'taxonomy_term_list:african_country'];
  }

  /**
   * {@inheritdoc}
   */
  public function items(string $subset, bool $show_count, bool $show_featured): array {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $items = [];
    foreach (self::REGIONS as $region) {
      $terms = $storage->loadByProperties(['vid' => 'african_country', 'name' => $region['countries']]);
      $tids = array_map('intval', array_keys($terms));
      $counts = ($show_count || $show_featured) && $tids !== []
        ? $this->taxonomyCounter->countMultipleTerms($tids, 'node', [], ['field_african_country'])
        : [];
      $total = array_sum($counts);
      $featured = '';
      if ($show_featured && $counts !== [] && max($counts) > 0) {
        $top_tid = array_search(max($counts), $counts, TRUE);
        $featured = isset($terms[$top_tid]) ? (string) $terms[$top_tid]->label() : '';
      }
      $items[] = [
        'type' => 'place',
        'badge_label' => '',
        'kicker' => $show_count ? $total . ($total === 1 ? ' RECORD' : ' RECORDS') : '',
        'title' => $region['label'],
        'excerpt' => $region['description'],
        'meta' => $featured !== '' ? (string) $this->t('Most records: @country', ['@country' => $featured]) : '',
        'href' => $region['url'],
        'image' => '',
        'image_alt' => '',
      ];
    }
    return $items;
  }

}
