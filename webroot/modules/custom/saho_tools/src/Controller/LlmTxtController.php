<?php

namespace Drupal\saho_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for generating llm.txt for AI/LLM discovery.
 *
 * Implements the llm.txt specification (2026) to provide LLMs and AI systems
 * with comprehensive information about SAHO's content, APIs, and structure.
 */
class LlmTxtController extends ControllerBase {

  /**
   * Generate llm.txt content.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Plain text response with llm.txt content.
   */
  public function generate(): Response {
    // Get content counts for each type.
    $counts = $this->getContentCounts();

    // Get base URL.
    $base_url = \Drupal::request()->getSchemeAndHttpHost();

    // Build llm.txt content using Twig template.
    $build = [
      '#theme' => 'llm_txt',
      '#base_url' => $base_url,
      '#counts' => $counts,
      '#last_updated' => date('Y-m-d'),
    ];

    $renderer = \Drupal::service('renderer');
    $content = $renderer->renderInIsolation($build);

    // Return as plain text response.
    $response = new Response($content);
    $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
    $response->headers->set('X-Robots-Tag', 'noindex');

    return $response;
  }

  /**
   * Get content counts for each content type.
   *
   * @return array
   *   Array of content type counts.
   */
  protected function getContentCounts(): array {
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

    return $counts;
  }

}
