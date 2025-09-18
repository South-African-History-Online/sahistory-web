<?php

namespace Drupal\saho_featured_articles\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\saho_featured_articles\Service\FeaturedContentService;
use Drupal\saho_featured_articles\Service\StatisticsService;
use Drupal\saho_featured_articles\Service\RenderService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

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
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a FeaturedArticlesController object.
   *
   * @param \Drupal\saho_featured_articles\Service\FeaturedContentService $featured_content_service
   *   The featured content service.
   * @param \Drupal\saho_featured_articles\Service\StatisticsService $statistics_service
   *   The statistics service.
   * @param \Drupal\saho_featured_articles\Service\RenderService $render_service
   *   The render service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    FeaturedContentService $featured_content_service,
    StatisticsService $statistics_service,
    RenderService $render_service,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->featuredContentService = $featured_content_service;
    $this->statisticsService = $statistics_service;
    $this->renderService = $render_service;
    $this->logger = $logger_factory->get('saho_featured_articles');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saho_featured_articles.content_service'),
      $container->get('saho_featured_articles.statistics_service'),
      $container->get('saho_featured_articles.render_service'),
      $container->get('logger.factory')
    );
  }

  /**
   * Builds the featured articles page.
   *
   * @return array
   *   A render array for the featured articles page.
   */
  public function page() {
    $this->logger->info('Loading featured articles page');

    // Set a breakpoint here for Xdebug debugging
    $debug_point = "Featured page controller entry";

    try {
      // Load all featured content
      $nodes = $this->featuredContentService->getAllFeaturedContent(50);
      
      $this->logger->info('Loaded @count featured nodes for main page', [
        '@count' => count($nodes),
      ]);

      // Build the render array
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
          'max-age' => 300, // 5 minutes
        ],
      ];

      $this->logger->info('Featured articles page build array created successfully');
      return $build;

    }
    catch (\Exception $e) {
      $this->logger->error('Error building featured articles page: @error', [
        '@error' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);

      // Return error page
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
    // Set breakpoint for Xdebug debugging
    $debug_section = $section;
    
    $this->logger->info('AJAX request for section: @section', ['@section' => $section]);

    try {
      // Load section content using the service
      $nodes = $this->featuredContentService->getSectionContent($section, 8);

      if (empty($nodes)) {
        $this->logger->info('No content found for section: @section', ['@section' => $section]);
        
        return new JsonResponse([
          'html' => '<div class="col-12"><div class="alert alert-info">No content available for this section.</div></div>',
          'count' => 0,
          'section' => $section,
          'debug' => 'No nodes found for section: ' . $section,
        ]);
      }

      // Render the content
      $html = $this->renderService->renderContentItems($nodes);
      
      $this->logger->info('Rendered @count items for section @section', [
        '@count' => count($nodes),
        '@section' => $section,
      ]);

      return new JsonResponse([
        'html' => $html,
        'count' => count($nodes),
        'section' => $section,
        'debug' => 'Successfully loaded ' . count($nodes) . ' items',
      ]);

    }
    catch (\Exception $e) {
      $this->logger->error('Error in sectionAjax for @section: @error', [
        '@section' => $section,
        '@error' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);

      return new JsonResponse([
        'html' => '<div class="col-12"><div class="alert alert-danger">Error loading content: ' . $e->getMessage() . '</div></div>',
        'count' => 0,
        'section' => $section,
        'error' => $e->getMessage(),
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
    // Set breakpoint for Xdebug debugging
    $debug_point = "Most read AJAX endpoint";
    
    $this->logger->info('AJAX request for most read content');

    try {
      // Get all featured content first
      $all_featured = $this->featuredContentService->getAllFeaturedContent(100);
      $featured_nids = array_keys($all_featured);

      if (empty($featured_nids)) {
        $this->logger->warning('No featured content available for most read calculation');
        
        return new JsonResponse([
          'html' => '<div class="col-12"><div class="alert alert-info">No most read featured content available yet.</div></div>',
          'count' => 0,
        ]);
      }

      // Get most read from statistics
      $most_read_nids = $this->statisticsService->getMostReadFeatured($featured_nids, 8);
      
      if (empty($most_read_nids)) {
        $this->logger->info('No statistics available, using recent featured content');
        $most_read_nids = array_slice($featured_nids, 0, 8);
      }

      // Load the nodes
      $nodes = [];
      foreach ($most_read_nids as $nid) {
        if (isset($all_featured[$nid])) {
          $nodes[$nid] = $all_featured[$nid];
        }
      }

      // Render with statistics
      $html = $this->renderService->renderMostReadItems($nodes);

      $this->logger->info('Rendered @count most read items', ['@count' => count($nodes)]);

      return new JsonResponse([
        'html' => $html,
        'count' => count($nodes),
        'debug' => 'Most read content loaded with ' . (
          $this->statisticsService->isStatisticsAvailable() ? 'statistics' : 'fallback ordering'
        ),
      ]);

    }
    catch (\Exception $e) {
      $this->logger->error('Error in mostReadAjax: @error', [
        '@error' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);

      return new JsonResponse([
        'html' => '<div class="col-12"><div class="alert alert-danger">Error loading most read content: ' . $e->getMessage() . '</div></div>',
        'count' => 0,
        'error' => $e->getMessage(),
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
    if (!\Drupal::config('system.logging')->get('error_level') === 'verbose') {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    $debug_info = [
      'content_service' => get_class($this->featuredContentService),
      'statistics_service' => get_class($this->statisticsService),
      'render_service' => get_class($this->renderService),
      'field_mappings' => $this->featuredContentService->getFieldMappings(),
      'statistics_available' => $this->statisticsService->isStatisticsAvailable(),
    ];

    // Test each section
    foreach ($this->featuredContentService->getFieldMappings() as $section => $field) {
      $count = $this->featuredContentService->getSectionCount($section);
      $debug_info['section_counts'][$section] = $count;
    }

    return [
      '#markup' => '<pre>' . print_r($debug_info, TRUE) . '</pre>',
    ];
  }

}