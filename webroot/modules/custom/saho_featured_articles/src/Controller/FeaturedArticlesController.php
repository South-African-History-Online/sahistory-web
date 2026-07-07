<?php

namespace Drupal\saho_featured_articles\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Image\ImageFactory;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_featured_articles\Service\FeaturedContentService;
use Drupal\saho_featured_articles\Service\StatisticsService;
use Drupal\saho_frontpage\CurrentFeatureService;
use Drupal\saho_refs\DisplayRefService;
use Drupal\saho_utils\Service\ImageExtractorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The /featured landing: the Editorial Register (Open Record).
 *
 * Anatomy: the current feature as a wf07 hero (same engine as the front
 * page), a mono status strip, the staff-picks card grid, the most-read
 * ledger, and the paged full register with GET section/sort filters. No
 * AJAX tabs, no client-side sorting - plain URLs, honestly cacheable.
 */
class FeaturedArticlesController extends ControllerBase {

  /**
   * Image fields checked for a card/hero image, in priority order.
   */
  protected const IMAGE_FIELDS = [
    'field_article_image',
    'field_bio_pic',
    'field_place_image',
    'field_event_image',
    'field_upcomingevent_image',
    'field_archive_image',
    'field_tdih_image',
    'field_feature_banner',
    'field_image',
  ];

  /**
   * Register page size.
   */
  protected const PAGE_SIZE = 24;

  /**
   * Resolution floor for the hero slot (R3 X-1.3).
   *
   * 60% of the ~1200px hero rendering width: a source narrower than this
   * would be upscaled into a blur, so the hero falls back to the
   * typographic ink panel instead.
   */
  protected const HERO_MIN_WIDTH = 720;

  public function __construct(
    protected FeaturedContentService $featuredContentService,
    protected StatisticsService $statisticsService,
    protected CurrentFeatureService $currentFeature,
    protected DisplayRefService $displayRef,
    protected ImageExtractorService $imageExtractor,
    protected RequestStack $requestStack,
    protected ImageFactory $imageFactory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saho_featured_articles.content_service'),
      $container->get('saho_featured_articles.statistics_service'),
      $container->get('saho_frontpage.current_feature'),
      $container->get('saho_refs.display_ref'),
      $container->get('saho_utils.image_extractor'),
      $container->get('request_stack'),
      $container->get('image.factory'),
    );
  }

  /**
   * Builds the Editorial Register page.
   *
   * @return array
   *   A render array for the /featured page.
   */
  public function page() {
    $request = $this->requestStack->getCurrentRequest();
    $section = (string) $request->query->get('section', 'all');
    $sort = (string) $request->query->get('sort', 'changed');
    $sections = array_keys($this->featuredContentService->getFieldMappings());
    if (!in_array($section, $sections, TRUE)) {
      $section = 'all';
    }
    if (!in_array($sort, ['changed', 'title', 'type'], TRUE)) {
      $sort = 'changed';
    }

    return [
      '#theme' => 'saho_featured_articles',
      '#hero' => $this->heroProps(),
      '#stats' => $this->stats(),
      '#staff_picks' => $this->staffPicks(),
      '#most_read' => $this->mostRead(),
      '#register_items' => $this->registerItems($section, $sort),
      '#register_sections' => $this->sectionChips($section),
      '#current_section' => $section,
      '#current_sort' => $sort,
      '#pager' => ['#type' => 'pager'],
      '#attached' => [
        'library' => [
          'saho_featured_articles/featured-register',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list'],
        'contexts' => [
          'user.permissions',
          'url.query_args:section',
          'url.query_args:sort',
          'url.query_args:page',
        ],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Builds the wf07 hero props for the current feature.
   *
   * @return array|null
   *   Props for saho:saho-hero-banner (or an ink-panel fallback set when
   *   the feature has no usable image), or NULL with no feature at all.
   */
  protected function heroProps(): ?array {
    $node = $this->currentFeature->node();
    if (!$node instanceof NodeInterface) {
      return NULL;
    }
    $ref = $this->displayRef->getRef($node);
    $image = $this->nodeImage($node, 'saho_hero', self::HERO_MIN_WIDTH);
    if ($image === NULL) {
      // The hero contract requires an image; without one the template
      // falls back to the ink home-feature panel.
      return [
        'fallback' => TRUE,
        'title' => (string) $node->label(),
        'standfirst' => $this->currentFeature->standfirst($node),
        'href' => $node->toUrl()->toString(),
        'meta' => 'Editorial register · ' . $ref,
      ];
    }
    $type = $this->cardType($node);
    return [
      'kicker' => 'Current feature · ' . $type . ' · ' . $ref,
      'title' => (string) $node->label(),
      'subtitle' => $this->currentFeature->standfirst($node, 200),
      'accent' => $type,
      'heading_level' => 'h2',
      'background_image' => $image,
      'background_image_mobile' => $this->nodeImage($node, 'saho_hero_mobile') ?? $image,
      'button_text' => 'Open the feature',
      'button_url' => $node->toUrl()->toString(),
      'button2_text' => 'View chronology',
      'button2_url' => '/timelines',
    ];
  }

  /**
   * Builds the mono status-strip counts.
   */
  protected function stats(): array {
    $labels = [
      'home-features' => 'Features',
      'staff-picks' => 'Staff picks',
      'most-read' => 'Most read',
      'africa-section' => 'Africa',
      'politics-society' => 'Politics & society',
      'timelines' => 'Timelines',
    ];
    $stats = [];
    foreach ($labels as $section => $label) {
      $count = $this->featuredContentService->getSectionCount($section);
      if ($count > 0) {
        $stats[] = [
          'label' => $label,
          'value' => number_format($count),
        ];
      }
    }
    return $stats;
  }

  /**
   * Builds archive-card props for the staff-picks grid.
   */
  protected function staffPicks(int $limit = 6): array {
    $cards = [];
    foreach ($this->featuredContentService->getSectionContent('staff-picks', $limit) as $node) {
      $cards[] = $this->cardProps($node);
    }
    return $cards;
  }

  /**
   * Builds the most-read ledger rows, ordered by recorded views.
   */
  protected function mostRead(int $limit = 10): array {
    $nodes = $this->featuredContentService->getSectionContent('most-read');
    if ($nodes === []) {
      return [];
    }
    $by_id = [];
    foreach ($nodes as $node) {
      $by_id[$node->id()] = $node;
    }
    $ordered = $this->statisticsService->getMostReadFeatured(array_keys($by_id), $limit);
    $rows = [];
    $rank = 0;
    foreach ($ordered as $nid) {
      $node = $by_id[$nid] ?? NULL;
      if (!$node instanceof NodeInterface) {
        continue;
      }
      $views = (int) $this->statisticsService->getNodeViewCount($nid);
      $rows[] = [
        'rank' => str_pad((string) ++$rank, 2, '0', STR_PAD_LEFT),
        'title' => (string) $node->label(),
        'href' => $node->toUrl()->toString(),
        'ref' => $this->displayRef->getRef($node),
        'views' => $views > 0 ? number_format($views) . ' views' : '',
      ];
    }
    return $rows;
  }

  /**
   * Builds the paged register grid for a section + sort.
   */
  protected function registerItems(string $section, string $sort): array {
    $mappings = $this->featuredContentService->getFieldMappings();
    $query = $this->entityTypeManager()->getStorage('node')->getQuery()
      ->condition('status', 1)
      ->accessCheck(TRUE);

    if ($section !== 'all' && isset($mappings[$section])) {
      $query->condition($mappings[$section], 1);
    }
    else {
      $group = $query->orConditionGroup();
      foreach ($mappings as $field) {
        $group->condition($field, 1);
      }
      $query->condition($group);
    }

    match ($sort) {
      'title' => $query->sort('title', 'ASC'),
      'type' => $query->sort('type', 'ASC')->sort('changed', 'DESC'),
      default => $query->sort('changed', 'DESC'),
    };

    $nids = $query->pager(self::PAGE_SIZE)->execute();
    $cards = [];
    foreach ($this->entityTypeManager()->getStorage('node')->loadMultiple($nids) as $node) {
      $cards[] = $this->cardProps($node);
    }
    return $cards;
  }

  /**
   * Builds the section filter chips.
   */
  protected function sectionChips(string $current): array {
    $labels = [
      'all' => 'All',
      'staff-picks' => 'Staff picks',
      'most-read' => 'Most read',
      'home-features' => 'Home features',
      'africa-section' => 'Africa',
      'politics-society' => 'Politics & society',
      'timelines' => 'Timelines',
    ];
    $chips = [];
    foreach ($labels as $key => $label) {
      $chips[] = [
        'key' => $key,
        'label' => $label,
        'href' => '/featured' . ($key === 'all' ? '' : '?section=' . $key),
        'active' => $key === $current,
      ];
    }
    return $chips;
  }

  /**
   * Builds saho:saho-archive-card props for a node.
   */
  protected function cardProps(NodeInterface $node): array {
    // 11.4 SDC prop validation rejects NULL for string props - omit the
    // image keys entirely when the node has no usable file.
    $props = [
      'type' => $this->cardType($node),
      'title' => (string) $node->label(),
      'href' => $node->toUrl()->toString(),
      'dates' => $this->displayRef->getRef($node),
    ];
    $image = $this->nodeImage($node, 'max_650x650');
    if ($image !== NULL) {
      $props['image'] = $image;
      $props['image_alt'] = (string) $node->label();
    }
    return $props;
  }

  /**
   * Maps a bundle onto the archive-card / hero accent type enum.
   */
  protected function cardType(NodeInterface $node): string {
    $bundle = $node->bundle();
    $map = [
      'upcomingevent' => 'event',
      'book' => 'archive',
      'image' => 'archive',
    ];
    $type = $map[$bundle] ?? $bundle;
    $allowed = ['article', 'biography', 'place', 'archive', 'event', 'topic'];
    return in_array($type, $allowed, TRUE) ? $type : 'article';
  }

  /**
   * Resolves a styled image URL from the node's image fields.
   *
   * Files missing on disk are skipped so the register never publishes a
   * knowingly broken figure (same guard as the picture archive).
   */
  protected function nodeImage(NodeInterface $node, string $style, int $min_width = 0): ?string {
    foreach (self::IMAGE_FIELDS as $field_name) {
      if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
        continue;
      }
      $file = $node->get($field_name)->first()->entity ?? NULL;
      if (!$file instanceof FileInterface || !file_exists($file->getFileUri())) {
        continue;
      }
      // Resolution floor: a source narrower than the slot demands would be
      // upscaled into a blur - skip it so the caller can fall back to the
      // typographic treatment (R3 X-1.3).
      if ($min_width > 0) {
        $image = $this->imageFactory->get($file->getFileUri());
        if (!$image->isValid() || $image->getWidth() < $min_width) {
          continue;
        }
      }
      $url = $this->imageExtractor->extractImageWithDerivatives($node, $style, $field_name);
      if ($url) {
        return $url;
      }
    }
    return NULL;
  }

}
