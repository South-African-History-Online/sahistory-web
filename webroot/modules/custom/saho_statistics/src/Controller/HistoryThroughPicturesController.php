<?php

namespace Drupal\saho_statistics\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\saho_utils\Service\ImageExtractorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for History Through Pictures gallery page.
 */
class HistoryThroughPicturesController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The image extractor service.
   *
   * @var \Drupal\saho_utils\Service\ImageExtractorService
   */
  protected $imageExtractor;

  /**
   * Constructs a HistoryThroughPicturesController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\saho_utils\Service\ImageExtractorService $image_extractor
   *   The image extractor service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ImageExtractorService $image_extractor,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->imageExtractor = $image_extractor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('saho_utils.image_extractor')
    );
  }

  /**
   * Displays the History Through Pictures gallery page.
   *
   * @return array
   *   Render array for the gallery page.
   */
  public function gallery() {
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Get sort parameter from URL.
    $sort = \Drupal::request()->query->get('sort', 'newest');
    if (!in_array($sort, ['newest', 'oldest', 'title_asc', 'title_desc', 'random'], TRUE)) {
      $sort = 'newest';
    }

    // Page over the cached valid-file index so the pager never counts
    // images whose files are missing on disk (#453) - the newest batches
    // reference thousands of never-landed files.
    $index = $this->getValidImageIndex($sort === 'random' ? 'newest' : $sort);
    $total_count = count($index);

    if ($sort === 'random') {
      shuffle($index);
      $nids = array_slice($index, 0, 24);
    }
    else {
      $pager = \Drupal::service('pager.manager')->createPager($total_count, 24);
      $nids = array_slice($index, $pager->getCurrentPage() * 24, 24);
    }

    if (empty($nids)) {
      return [
        '#markup' => '<div class="container"><p class="text-center">' . $this->t('No featured images available at this time. Check back soon!') . '</p></div>',
      ];
    }

    $nodes = $node_storage->loadMultiple($nids);

    // Prepare items for the template.
    $items = [];
    foreach ($nodes as $node) {
      $image_url = $this->getNodeImageUrl($node);

      if (!$image_url) {
        continue;
      }

      // Get the feature link if available.
      $target_url = $this->getFeatureLink($node);

      $item = [
        'nid' => $node->id(),
        'title' => $node->getTitle(),
        'url' => $target_url,
        'image' => $image_url,
        'image_large' => $this->getNodeImageUrl($node, 'max_1300x1300'),
        'has_feature_link' => $this->hasFeatureLink($node),
      ];

      // Add caption from field_source.
      if ($node->hasField('field_source') && !$node->get('field_source')->isEmpty()) {
        $caption = $node->get('field_source')->value;
        $caption = strip_tags($caption);
        $caption = html_entity_decode($caption, ENT_QUOTES | ENT_HTML5);
        if (!empty(trim($caption))) {
          $item['caption'] = $caption;
        }
      }

      $items[] = $item;
    }

    if (empty($items)) {
      return [
        '#markup' => '<div class="container"><p class="text-center">' . $this->t('No images with valid image files found.') . '</p></div>',
      ];
    }

    // Build sort links.
    $current_path = '/history-through-pictures';
    $sort_links = [
      [
        'label' => $this->t('Newest'),
        'key' => 'newest',
        'url' => Url::fromUserInput($current_path, ['query' => ['sort' => 'newest']]),
        'active' => $sort === 'newest',
      ],
      [
        'label' => $this->t('Oldest'),
        'key' => 'oldest',
        'url' => Url::fromUserInput($current_path, ['query' => ['sort' => 'oldest']]),
        'active' => $sort === 'oldest',
      ],
      [
        'label' => $this->t('A-Z'),
        'key' => 'title_asc',
        'url' => Url::fromUserInput($current_path, ['query' => ['sort' => 'title_asc']]),
        'active' => $sort === 'title_asc',
      ],
      [
        'label' => $this->t('Z-A'),
        'key' => 'title_desc',
        'url' => Url::fromUserInput($current_path, ['query' => ['sort' => 'title_desc']]),
        'active' => $sort === 'title_desc',
      ],
      [
        'label' => $this->t('Random'),
        'key' => 'random',
        'url' => Url::fromUserInput($current_path, ['query' => ['sort' => 'random']]),
        'active' => $sort === 'random',
      ],
    ];

    $build = [
      '#theme' => 'history_through_pictures_gallery',
      '#items' => $items,
      '#total_count' => $total_count,
      '#sort_links' => $sort_links,
      '#is_first_page' => ((int) \Drupal::request()->query->get('page', 0)) === 0,
      '#current_sort' => $sort,
      '#attached' => [
        'library' => [
          'saho_statistics/history-through-pictures',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.query_args:sort', 'url.query_args:page'],
        'tags' => ['node_list:image'],
        'max-age' => $sort === 'random' ? 300 : 3600,
      ],
    ];

    // Add pager for non-random sorts ('#pager' feeds the theme-hook
    // variable; a bare render-array child never reaches the template).
    if ($sort !== 'random') {
      $build['#pager'] = [
        '#type' => 'pager',
      ];
    }

    return $build;
  }

  /**
   * Gets the image URL for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string|null
   *   The relative image URL or NULL if not available.
   */

  /**
   * Builds the sorted list of image-node ids whose files exist on disk.
   *
   * A plain SQL join (no entity loads) plus one file_exists() per row,
   * cached permanently and invalidated with node_list:image. Roughly a
   * third of the 18k image nodes reference files that never landed on
   * disk; the pager must not count them.
   *
   * @param string $sort
   *   One of newest | oldest | title_asc | title_desc.
   *
   * @return int[]
   *   Node ids in sort order.
   */
  protected function getValidImageIndex(string $sort): array {
    $cid = 'saho_statistics:picture_index:' . $sort;
    $cache_backend = \Drupal::cache();
    if ($cache = $cache_backend->get($cid)) {
      return $cache->data;
    }

    $order = match ($sort) {
      'oldest' => 'n.created ASC',
      'title_asc' => 'n.title ASC',
      'title_desc' => 'n.title DESC',
      default => 'n.created DESC',
    };
    $result = \Drupal::database()->query(
      'SELECT n.nid, COALESCE(f1.uri, f2.uri) AS uri
       FROM {node_field_data} n
       LEFT JOIN {node__field_image} i1 ON i1.entity_id = n.nid AND i1.deleted = 0 AND i1.delta = 0
       LEFT JOIN {file_managed} f1 ON f1.fid = i1.field_image_target_id
       LEFT JOIN {node__field_archive_image} i2 ON i2.entity_id = n.nid AND i2.deleted = 0 AND i2.delta = 0
       LEFT JOIN {file_managed} f2 ON f2.fid = i2.field_archive_image_target_id
       WHERE n.type = :type AND n.status = 1 AND COALESCE(f1.uri, f2.uri) IS NOT NULL
       ORDER BY ' . $order,
      [':type' => 'image']
    );

    $nids = [];
    foreach ($result as $row) {
      if ($row->uri && file_exists($row->uri)) {
        $nids[] = (int) $row->nid;
      }
    }

    $cache_backend->set($cid, $nids, CacheBackendInterface::CACHE_PERMANENT, ['node_list:image']);
    return $nids;
  }

  /**
   * Builds a styled image URL for a picture node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The image node.
   * @param string $style
   *   The image style to derive (grid default; max_1300x1300 for lightbox).
   *
   * @return string|null
   *   The derivative URL, or NULL when no valid file exists on disk.
   */
  protected function getNodeImageUrl($node, string $style = 'max_650x650') {
    // Serve WebP image-style derivatives, not raw originals (#453 perf).
    // Files missing on disk (the pre-2019 loss class) are skipped entirely
    // so the index never publishes a knowingly broken figure.
    foreach (['field_image', 'field_archive_image'] as $field_name) {
      if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
        continue;
      }
      $file = $node->get($field_name)->first()->entity ?? NULL;
      if (!$file instanceof FileInterface || !file_exists($file->getFileUri())) {
        continue;
      }
      $url = $this->imageExtractor->extractImageWithDerivatives($node, $style, $field_name);
      if ($url) {
        return $url;
      }
    }

    return NULL;
  }

  /**
   * Gets the feature link URL for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string
   *   The URL to link to.
   */
  protected function getFeatureLink($node) {
    if ($node->hasField('field_feature_link') && !$node->get('field_feature_link')->isEmpty()) {
      $referenced_entity = $node->get('field_feature_link')->entity;
      if ($referenced_entity) {
        return Url::fromRoute('entity.node.canonical', [
          'node' => $referenced_entity->id(),
        ])->toString();
      }
    }

    return Url::fromRoute('entity.node.canonical', [
      'node' => $node->id(),
    ])->toString();
  }

  /**
   * Checks if the node has a feature link.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return bool
   *   TRUE if the node has a feature link.
   */
  protected function hasFeatureLink($node) {
    return $node->hasField('field_feature_link') && !$node->get('field_feature_link')->isEmpty();
  }

}
