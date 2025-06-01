<?php

namespace Drupal\entity_overview\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;

/**
 * Controller for the Entity Overview AJAX endpoint.
 */
class EntityOverviewController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a new EntityOverviewController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    BlockManagerInterface $block_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Handles AJAX requests to load entities.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the rendered entities.
   */
  public function ajaxLoad(Request $request) {
    // Get the request parameters.
    $blockId = $request->request->get('blockId');
    $page = (int) $request->request->get('page', 0);
    $filter = $request->request->get('filter', '');
    $sort = $request->request->get('sort', 'latest');

    // Load the block configuration.
    $block_config = $this->getBlockConfig($blockId);
    if (!$block_config) {
      return new JsonResponse(['error' => 'Invalid block ID'], 400);
    }

    // Get the entity type and content type from the block configuration.
    $entity_type = $block_config['entity_type'] ?? 'node';
    $content_type = $block_config['content_type'] ?? 'article';
    $limit = $block_config['limit'] ?? 5;
    $offset = $page * $limit;

    // Load the entities.
    $entities = $this->loadEntities($entity_type, $content_type, $filter, $sort, $limit, $offset);
    // Check if there are more entities.
    $total_count = $this->getEntityCount($entity_type, $content_type, $filter);
    $has_more = ($offset + $limit) < $total_count;

    // Render the entities.
    $items = [];
    foreach ($entities as $entity) {
      $items[] = $this->renderEntity($entity, $block_config);
    }

    // Return the response.
    return new JsonResponse([
      'items' => $items,
      'hasMore' => $has_more,
      'totalCount' => $total_count,
    ]);
  }

  /**
   * Gets the block configuration.
   *
   * @param string $block_id
   *   The block ID.
   *
   * @return array|null
   *   The block configuration, or NULL if not found.
   */
  protected function getBlockConfig($block_id) {
    // In a real implementation, this would load the block configuration from
    // the block storage. For simplicity, we'll return a default configuration.
    return [
      'entity_type' => 'node',
      'content_type' => 'article',
      'limit' => 5,
      'filter_field' => 'field_tags',
    ];
  }

  /**
   * Loads entities based on the given parameters.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $content_type
   *   The content type.
   * @param string $filter
   *   The filter value.
   * @param string $sort
   *   The sort order.
   * @param int $limit
   *   The number of entities to load.
   * @param int $offset
   *   The offset.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities.
   */
  protected function loadEntities($entity_type, $content_type, $filter, $sort, $limit, $offset) {
    // Create a query for the entity type.
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery()
      ->accessCheck(TRUE)
      ->range($offset, $limit);

    // Add conditions based on the entity type.
    if ($entity_type === 'node') {
      $query->condition('type', $content_type)
        ->condition('status', 1);
    }

    // Add filter condition if provided.
    if (!empty($filter)) {
      $query->condition('field_tags.target_id', $filter);
    }

    // Add sort condition.
    if ($sort === 'latest') {
      $query->sort('created', 'DESC');
    }
    elseif ($sort === 'oldest') {
      $query->sort('created', 'ASC');
    }
    elseif ($sort === 'title') {
      $query->sort('title', 'ASC');
    }

    // Execute the query and load the entities.
    $ids = $query->execute();
    return $this->entityTypeManager->getStorage($entity_type)->loadMultiple($ids);
  }

  /**
   * Gets the total count of entities matching the filter.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $content_type
   *   The content type.
   * @param string $filter
   *   The filter value.
   *
   * @return int
   *   The total count.
   */
  protected function getEntityCount($entity_type, $content_type, $filter) {
    // Create a query for the entity type.
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery()
      ->accessCheck(TRUE);

    // Add conditions based on the entity type.
    if ($entity_type === 'node') {
      $query->condition('type', $content_type)
        ->condition('status', 1);
    }

    // Add filter condition if provided.
    if (!empty($filter)) {
      $query->condition('field_tags.target_id', $filter);
    }

    // Execute the query and return the count.
    return $query->count()->execute();
  }

  /**
   * Renders an entity as HTML.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to render.
   * @param array $block_config
   *   The block configuration.
   *
   * @return string
   *   The rendered entity HTML.
   */
  protected function renderEntity(EntityInterface $entity, array $block_config) {
    // Build the entity item.
    $item = [
      'id' => $entity->id(),
      'title' => $entity->label(),
      'url' => $entity->toUrl()->toString(),
      'created' => $entity instanceof NodeInterface ? $entity->getCreatedTime() : NULL,
    ];

    // Add image if available.
    if ($entity->getEntityTypeId() === 'node' && $entity instanceof NodeInterface) {
      if ($entity->hasField('field_article_image') && !$entity->get('field_article_image')->isEmpty()) {
        $file = $entity->get('field_article_image')->entity;
        if ($file) {
          $item['image'] = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        }
      }
    }

    // Render the entity item.
    $build = [
      '#theme' => 'entity_overview_item',
      '#item' => $item,
    ];

    return $this->renderer->render($build);
  }

}