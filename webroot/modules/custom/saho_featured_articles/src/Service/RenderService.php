<?php

namespace Drupal\saho_featured_articles\Service;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Service for rendering featured content items.
 */
class RenderService {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The statistics service.
   *
   * @var \Drupal\saho_featured_articles\Service\StatisticsService
   */
  protected $statisticsService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RenderService object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   * @param \Drupal\saho_featured_articles\Service\StatisticsService $statistics_service
   *   The statistics service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    RendererInterface $renderer,
    DateFormatterInterface $date_formatter,
    UrlGeneratorInterface $url_generator,
    FileUrlGeneratorInterface $file_url_generator,
    StatisticsService $statistics_service,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
    $this->urlGenerator = $url_generator;
    $this->fileUrlGenerator = $file_url_generator;
    $this->statisticsService = $statistics_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Render HTML for featured content items.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   Array of node entities to render.
   * @param array $options
   *   Additional rendering options.
   *
   * @return string
   *   Rendered HTML string.
   */
  public function renderContentItems(array $nodes, array $options = []) {

    if (empty($nodes)) {
      return '<div class="col-12"><div class="alert alert-info">No content available.</div></div>';
    }

    $html = '';
    foreach ($nodes as $node) {
      $html .= $this->renderSingleItem($node, $options);
    }

    return $html;
  }

  /**
   * Render a single content item.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity to render.
   * @param array $options
   *   Additional rendering options.
   *
   * @return string
   *   Rendered HTML for the item.
   */
  protected function renderSingleItem(NodeInterface $node, array $options = []) {
    try {
      $node_url = $node->toUrl()->toString();
      $node_title = $node->getTitle();
      $node_type = $node->bundle();
      $updated_date = $this->dateFormatter->format($node->getChangedTime(), 'custom', 'j M Y');
      $type_label = ucfirst(str_replace('_', ' ', $node_type));

      // Get image URL.
      $image_url = $this->getNodeImageUrl($node);

      // Get view count if statistics service is available.
      $view_count = '';
      if (!empty($options['show_stats'])) {
        $count = $this->statisticsService->getNodeViewCount($node->id());
        if ($count > 0) {
          $view_count = '<div class="text-success small"><i class="fas fa-eye me-1" aria-hidden="true"></i>' . number_format($count) . ' views</div>';
        }
      }

      // Get badges.
      $badges = $this->getBadges($node);

      // Get summary.
      $summary = $this->getNodeSummary($node);

      $html = '<div class="col-lg-6 mb-4">';
      $html .= '<div class="saho-grid-item">';
      $html .= '<div class="card h-100">';

      // Image section.
      if ($image_url) {
        $html .= '<div class="card-img-top-wrapper position-relative overflow-hidden" style="height: 200px;">';
        $html .= '<img src="' . htmlspecialchars($image_url) . '" alt="' . htmlspecialchars($node_title) . '" class="card-img-top w-100 h-100" style="object-fit: cover;">';
        $html .= '<div class="position-absolute top-0 end-0 m-2">';
        $html .= '<span class="badge bg-primary">' . $type_label . '</span>';
        $html .= '</div>';
        $html .= '</div>';
      }
      else {
        // Placeholder with icon.
        $icon = $this->getPlaceholderIcon($node_type);
        $html .= '<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px; position: relative;">';
        $html .= '<i class="' . $icon . ' fa-3x text-muted"></i>';
        $html .= '<div class="position-absolute top-0 end-0 m-2">';
        $html .= '<span class="badge bg-primary">' . $type_label . '</span>';
        $html .= '</div>';
        $html .= '</div>';
      }

      $html .= '<div class="card-body d-flex flex-column">';
      $html .= '<h3 class="card-title h5 saho-text-primary">';
      $html .= '<a href="' . $node_url . '" class="text-decoration-none stretched-link">' . htmlspecialchars($node_title) . '</a>';
      $html .= '</h3>';
      $html .= '<div class="text-muted small mb-2">Updated ' . $updated_date . '</div>';

      if ($summary) {
        $html .= '<p class="card-text text-muted mb-3">' . htmlspecialchars($summary) . '</p>';
      }

      if ($view_count) {
        $html .= $view_count;
      }

      $html .= '<div class="mt-auto">';
      $html .= '<div class="d-flex justify-content-between align-items-center">';
      $html .= '<small class="text-muted">' . $type_label . '</small>';
      if ($badges) {
        $html .= '<div class="btn-group">' . $badges . '</div>';
      }
      $html .= '</div>';
      $html .= '</div>';

      $html .= '</div>';
      $html .= '</div>';
      $html .= '</div>';
      $html .= '</div>';

      return $html;
    }
    catch (\Exception $e) {
      return '';
    }
  }

  /**
   * Get the image URL for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string|null
   *   The image URL or NULL if no image found.
   */
  protected function getNodeImageUrl(NodeInterface $node) {
    // Priority order of image fields to check.
    $image_fields = [
      'field_article_image',
      'field_bio_pic',
      'field_place_image',
      'field_event_image',
      'field_upcomingevent_image',
      'field_archive_image',
      'field_tdih_image',
      'field_feature_banner',
      'field_image',
      'field_spotlights',
    ];

    foreach ($image_fields as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        /** @var \Drupal\file\FileInterface|null $file_entity */
        $file_entity = $node->get($field_name)->entity;
        if ($file_entity && method_exists($file_entity, 'getFileUri')) {
          $uri = $file_entity->getFileUri();
          // Use large image style for better quality.
          /** @var \Drupal\image\ImageStyleInterface|null $style */
          $style = $this->entityTypeManager->getStorage('image_style')->load('large');
          if ($style) {
            $url = $style->buildUrl($uri);
            // Convert to relative URL for consistency.
            return $this->fileUrlGenerator->transformRelative($url);
          }
          $url = $this->fileUrlGenerator->generateAbsoluteString($uri);
          return $this->fileUrlGenerator->transformRelative($url);
        }
      }
    }

    return NULL;
  }

  /**
   * Get summary text for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string
   *   The summary text, truncated to 120 characters.
   */
  protected function getNodeSummary(NodeInterface $node) {
    $summary = '';

    if ($node->hasField('field_synopsis') && !$node->get('field_synopsis')->isEmpty()) {
      $summary = strip_tags($node->get('field_synopsis')->value);
    }
    elseif ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $summary = strip_tags($node->get('body')->value);
    }

    if (strlen($summary) > 120) {
      $summary = substr($summary, 0, 120) . '...';
    }

    return trim($summary);
  }

  /**
   * Get placeholder icon class based on node type.
   *
   * @param string $node_type
   *   The node bundle/type.
   *
   * @return string
   *   FontAwesome icon class.
   */
  protected function getPlaceholderIcon($node_type) {
    $icons = [
      'biography' => 'fas fa-user',
      'place' => 'fas fa-map-marker-alt',
      'event' => 'fas fa-calendar-alt',
      'article' => 'fas fa-file-alt',
      'archive' => 'fas fa-archive',
    ];

    return $icons[$node_type] ?? 'fas fa-file-alt';
  }

  /**
   * Get badges for a node based on its featured status.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string
   *   HTML for badges.
   */
  protected function getBadges(NodeInterface $node) {
    $badges = [];

    // Check for staff picks.
    if ($node->hasField('field_staff_picks') &&
        !$node->get('field_staff_picks')->isEmpty() &&
        $node->get('field_staff_picks')->value == 1) {
      $badges[] = '<span class="badge bg-warning text-dark me-1">Staff Pick</span>';
    }

    // Check for general featured.
    if ($node->hasField('field_home_page_feature') &&
        !$node->get('field_home_page_feature')->isEmpty() &&
        $node->get('field_home_page_feature')->value == 1) {
      $badges[] = '<span class="badge bg-info">Featured</span>';
    }

    return implode('', $badges);
  }

  /**
   * Render most read content with statistics.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   Array of node entities to render.
   *
   * @return string
   *   Rendered HTML string with statistics.
   */
  public function renderMostReadItems(array $nodes) {
    return $this->renderContentItems($nodes, ['show_stats' => TRUE]);
  }

}
