<?php

namespace Drupal\saho_statistics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
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

    // Query for all images (not just featured).
    $query = $node_storage->getQuery()
      ->condition('type', 'image')
      ->condition('status', 1)
      ->accessCheck(TRUE);

    // Only fetch nodes that have an image in field_image or field_archive_image.
    $image_condition = $query->orConditionGroup()
      ->exists('field_image')
      ->exists('field_archive_image');
    $query->condition($image_condition);

    // Apply sorting.
    switch ($sort) {
      case 'random':
        // For random, get a reasonable sample size.
        $query->range(0, 100);
        break;

      case 'oldest':
        $query->sort('created', 'ASC');
        $query->pager(24);
        break;

      case 'title_asc':
        $query->sort('title', 'ASC');
        $query->pager(24);
        break;

      case 'title_desc':
        $query->sort('title', 'DESC');
        $query->pager(24);
        break;

      case 'newest':
      default:
        $query->sort('created', 'DESC');
        $query->pager(24);
        break;
    }

    $nids = $query->execute();

    if (empty($nids)) {
      return [
        '#markup' => '<div class="container"><p class="text-center">' . $this->t('No featured images available at this time. Check back soon!') . '</p></div>',
      ];
    }

    $nodes = $node_storage->loadMultiple($nids);

    // Shuffle for random sort.
    if ($sort === 'random') {
      $nodes = array_values($nodes);
      shuffle($nodes);
    }

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
        'url' => Url::fromUserInput($current_path, ['query' => ['sort' => 'newest']]),
        'active' => $sort === 'newest',
      ],
      [
        'label' => $this->t('Oldest'),
        'url' => Url::fromUserInput($current_path, ['query' => ['sort' => 'oldest']]),
        'active' => $sort === 'oldest',
      ],
      [
        'label' => $this->t('A-Z'),
        'url' => Url::fromUserInput($current_path, ['query' => ['sort' => 'title_asc']]),
        'active' => $sort === 'title_asc',
      ],
      [
        'label' => $this->t('Z-A'),
        'url' => Url::fromUserInput($current_path, ['query' => ['sort' => 'title_desc']]),
        'active' => $sort === 'title_desc',
      ],
      [
        'label' => $this->t('Random'),
        'url' => Url::fromUserInput($current_path, ['query' => ['sort' => 'random']]),
        'active' => $sort === 'random',
      ],
    ];

    $build = [
      '#theme' => 'history_through_pictures_gallery',
      '#items' => $items,
      '#total_count' => count($items),
      '#sort_links' => $sort_links,
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

    // Add pager for non-random sorts.
    if ($sort !== 'random') {
      $build['pager'] = [
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
  protected function getNodeImageUrl($node) {
    $image_fields = ['field_image', 'field_archive_image'];

    foreach ($image_fields as $field_name) {
      if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
        continue;
      }

      $field_value = $node->get($field_name)->first();
      if (!$field_value) {
        continue;
      }

      // Fix: Access file entity properly using ->entity property.
      $file = $field_value->entity;
      if (!$file || !$file instanceof \Drupal\file\FileInterface) {
        continue;
      }

      $uri = $file->getFileUri();
      $path = str_replace('public://', '/sites/default/files/', $uri);
      return $path;
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
