<?php

namespace Drupal\saho_featured_articles\Service;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
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
   * Constructs a RenderService object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator service.
   */
  public function __construct(
    RendererInterface $renderer,
    DateFormatterInterface $date_formatter,
    UrlGeneratorInterface $url_generator,
  ) {
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
    $this->urlGenerator = $url_generator;
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

      // Get view count if statistics service is available.
      $view_count = '';
      if (!empty($options['show_stats'])) {
        $stats_service = \Drupal::service('saho_featured_articles.statistics_service');
        $count = $stats_service->getNodeViewCount($node->id());
        if ($count > 0) {
          $view_count = '<div class="text-success small"><i class="fas fa-eye me-1" aria-hidden="true"></i>' . number_format($count) . ' views</div>';
        }
      }

      // Get badges.
      $badges = $this->getBadges($node);

      $html = '<div class="col-lg-6 mb-4">';
      $html .= '<div class="saho-grid-item">';
      $html .= '<div class="card h-100">';
      $html .= '<div class="card-body">';
      $html .= '<h3 class="card-title h5">';
      $html .= '<a href="' . $node_url . '" class="text-decoration-none">' . htmlspecialchars($node_title) . '</a>';
      $html .= '</h3>';
      $html .= '<div class="text-muted small mb-2">' . ucfirst(str_replace('_', ' ', $node_type)) . ' • Updated ' . $updated_date . '</div>';

      if ($view_count) {
        $html .= $view_count;
      }

      if ($badges) {
        $html .= '<div class="mt-auto">' . $badges . '</div>';
      }

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

    return empty($badges) ? '' : '<div class="btn-group">' . implode('', $badges) . '</div>';
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
