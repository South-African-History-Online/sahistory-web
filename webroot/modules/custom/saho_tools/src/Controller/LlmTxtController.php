<?php

namespace Drupal\saho_tools\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Site\Settings;
use Drupal\saho_tools\Service\Builder\WebSiteSchemaBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for generating llm.txt for AI/LLM discovery.
 *
 * Implements the llm.txt specification (2026) to provide LLMs and AI systems
 * with comprehensive information about SAHO's content, APIs, and structure.
 */
class LlmTxtController extends ControllerBase {

  /**
   * Cache id for the per-bundle content counts.
   */
  protected const COUNTS_CID = 'saho_tools:llm_txt:counts';

  /**
   * How long the generated file stays fresh, in seconds (one day).
   */
  protected const MAX_AGE = 86400;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The default cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs a LlmTxtController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The default cache backend.
   */
  public function __construct(RendererInterface $renderer, CacheBackendInterface $cache_backend) {
    $this->renderer = $renderer;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('cache.default')
    );
  }

  /**
   * Generate llm.txt content.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Plain text response with llm.txt content.
   */
  public function generate(): Response {
    // Get content counts for each type.
    $counts = $this->getContentCounts();

    // Always advertise the canonical production host: this file is read by
    // crawlers and cached copies, so it must never carry a local or staging
    // hostname. Same override knob as the schema builders.
    $base_url = rtrim(Settings::get('saho_canonical_url', WebSiteSchemaBuilder::CANONICAL_URL), '/');

    // Build llm.txt content using Twig template.
    $build = [
      '#theme' => 'llm_txt',
      '#base_url' => $base_url,
      '#counts' => $counts,
      '#last_updated' => date('Y-m-d'),
    ];

    $content = $this->renderer->renderInIsolation($build);

    // Return as plain text response. The file is host-canonical and identical
    // for every visitor, so it is safe to cache publicly for a day.
    $response = new Response($content);
    $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
    $response->headers->set('X-Robots-Tag', 'noindex');
    $response->setPublic();
    $response->setMaxAge(self::MAX_AGE);

    return $response;
  }

  /**
   * Get content counts for each content type.
   *
   * @return array
   *   Array of content type counts.
   */
  protected function getContentCounts(): array {
    // The counts drift only when content is published/unpublished, so cache
    // them for a day under node_list rather than running seven count queries
    // on every crawler hit.
    $cached = $this->cacheBackend->get(self::COUNTS_CID);
    if ($cached && is_array($cached->data)) {
      return $cached->data;
    }

    $node_storage = $this->entityTypeManager()->getStorage('node');
    $counts = [];

    $types = [
      'article',
      'biography',
      'event',
      'archive',
      'place',
      'product',
      'image',
    ];

    foreach ($types as $type) {
      $query = $node_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $type)
        ->condition('status', 1);
      $counts[$type] = $query->count()->execute();
    }

    $this->cacheBackend->set(
      self::COUNTS_CID,
      $counts,
      time() + self::MAX_AGE,
      ['node_list']
    );

    return $counts;
  }

}
