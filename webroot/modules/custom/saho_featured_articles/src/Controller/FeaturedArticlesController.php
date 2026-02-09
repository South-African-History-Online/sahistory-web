<?php

namespace Drupal\saho_featured_articles\Controller;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\saho_featured_articles\Service\FeaturedContentService;
use Drupal\saho_featured_articles\Service\StatisticsService;
use Drupal\saho_featured_articles\Service\RenderService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for the featured articles functionality.
 */
class FeaturedArticlesController extends ControllerBase {

  /**
   * The featured content service.
   *
   * @var \Drupal\saho_featured_articles\Service\FeaturedContentService
   */
  protected $featuredContentService;

  /**
   * The statistics service.
   *
   * @var \Drupal\saho_featured_articles\Service\StatisticsService
   */
  protected $statisticsService;

  /**
   * The render service.
   *
   * @var \Drupal\saho_featured_articles\Service\RenderService
   */
  protected $renderService;

  /**
   * Constructs a FeaturedArticlesController object.
   *
   * @param \Drupal\saho_featured_articles\Service\FeaturedContentService $featured_content_service
   *   The featured content service.
   * @param \Drupal\saho_featured_articles\Service\StatisticsService $statistics_service
   *   The statistics service.
   * @param \Drupal\saho_featured_articles\Service\RenderService $render_service
   *   The render service.
   */
  public function __construct(
    FeaturedContentService $featured_content_service,
    StatisticsService $statistics_service,
    RenderService $render_service,
  ) {
    $this->featuredContentService = $featured_content_service;
    $this->statisticsService = $statistics_service;
    $this->renderService = $render_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saho_featured_articles.content_service'),
      $container->get('saho_featured_articles.statistics_service'),
      $container->get('saho_featured_articles.render_service')
    );
  }

  /**
   * Builds the featured articles page.
   *
   * @return array
   *   A render array for the featured articles page.
   */
  public function page() {
    try {
      // Load all featured content (no limit).
      $nodes = $this->featuredContentService->getAllFeaturedContent();

      // Build the render array.
      $build = [
        '#theme' => 'saho_featured_articles',
        '#nodes' => $nodes,
        '#attached' => [
          'library' => [
            'saho_featured_articles/featured-styles',
            'saho_featured_articles/featured-navigation',
            'saho/featured.content.modern',
          ],
        ],
        '#cache' => [
          'tags' => [
            'node_list',
            'config:saho_featured_articles.settings',
          ],
          'contexts' => ['user.permissions'],
          // 5 minutes
          'max-age' => 300,
        ],
      ];

      return $build;

    }
    catch (\Exception $e) {
      // Return error page.
      return [
        '#markup' => '<div class="alert alert-danger">Unable to load featured content. Please try again later.</div>',
      ];
    }
  }

  /**
   * AJAX endpoint for section content.
   *
   * @param string $section
   *   The section name.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with rendered section content.
   */
  public function sectionAjax($section) {
    try {
      // Load section content using the service (no limit - show all).
      $nodes = $this->featuredContentService->getSectionContent($section);

      if (empty($nodes)) {
        return new JsonResponse([
          'html' => '<div class="col-12"><div class="alert alert-info">No content available for this section.</div></div>',
          'count' => 0,
          'section' => $section,
          'debug' => 'No nodes found for section: ' . $section,
        ]);
      }

      // Render the content.
      $html = $this->renderService->renderContentItems($nodes);

      return new JsonResponse([
        'html' => $html,
        'count' => count($nodes),
        'section' => $section,
        'debug' => 'Successfully loaded ' . count($nodes) . ' items',
      ]);

    }
    catch (\Exception $e) {
      \Drupal::logger('saho_featured_articles')->error('Error loading section content: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'html' => '<div class="col-12"><div class="alert alert-danger">Error loading content. Please try again later.</div></div>',
        'count' => 0,
        'section' => $section,
        'error' => 'An unexpected error occurred.',
      ], 500);
    }
  }

  /**
   * AJAX endpoint for most read content.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with rendered most read content.
   */
  public function mostReadAjax() {
    try {
      // Get all featured content first.
      $all_featured = $this->featuredContentService->getAllFeaturedContent(100);
      $featured_nids = array_keys($all_featured);

      if (empty($featured_nids)) {
        return new JsonResponse([
          'html' => '<div class="col-12"><div class="alert alert-info">No most read featured content available yet.</div></div>',
          'count' => 0,
        ]);
      }

      // Get most read from statistics (no limit - show all).
      $most_read_nids = $this->statisticsService->getMostReadFeatured($featured_nids, 0);

      if (empty($most_read_nids)) {
        $most_read_nids = $featured_nids;
      }

      // Load the nodes.
      $nodes = [];
      foreach ($most_read_nids as $nid) {
        if (isset($all_featured[$nid])) {
          $nodes[$nid] = $all_featured[$nid];
        }
      }

      // Render with statistics.
      $html = $this->renderService->renderMostReadItems($nodes);

      return new JsonResponse([
        'html' => $html,
        'count' => count($nodes),
        'debug' => 'Most read content loaded with ' . (
          $this->statisticsService->isStatisticsAvailable() ? 'statistics' : 'fallback ordering'
        ),
      ]);

    }
    catch (\Exception $e) {
      \Drupal::logger('saho_featured_articles')->error('Error loading most read content: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'html' => '<div class="col-12"><div class="alert alert-danger">Error loading most read content. Please try again later.</div></div>',
        'count' => 0,
        'error' => 'An unexpected error occurred.',
      ], 500);
    }
  }

  /**
   * Debug endpoint to check service functionality.
   *
   * @return array
   *   Debug information render array.
   */
  public function debugServices() {
    if ($this->config('system.logging')->get('error_level') !== 'verbose') {
      throw new AccessDeniedHttpException();
    }

    $debug_info = [
      'content_service' => get_class($this->featuredContentService),
      'statistics_service' => get_class($this->statisticsService),
      'render_service' => get_class($this->renderService),
      'field_mappings' => $this->featuredContentService->getFieldMappings(),
      'statistics_available' => $this->statisticsService->isStatisticsAvailable(),
    ];

    // Test each section.
    foreach ($this->featuredContentService->getFieldMappings() as $section => $field) {
      $count = $this->featuredContentService->getSectionCount($section);
      $debug_info['section_counts'][$section] = $count;
    }

    return [
      '#markup' => '<pre>' . htmlspecialchars(json_encode($debug_info, JSON_PRETTY_PRINT)) . '</pre>',
    ];
  }

}
