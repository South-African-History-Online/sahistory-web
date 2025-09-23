<?php

namespace Drupal\saho_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\CitationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for citation functionality.
 */
class CitationController extends ControllerBase {
  /**
   * The citation service.
   *
   * @var \Drupal\saho_tools\Service\CitationService
   */
  protected $citationService;

  /**
   * Constructs a CitationController object.
   *
   * @param \Drupal\saho_tools\Service\CitationService $citation_service
   *   The citation service.
   */
  public function __construct(CitationService $citation_service) {
    $this->citationService = $citation_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('saho_tools.citation_service')
      );
  }

  /**
   * Generates citations for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to generate citations for.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the citations.
   */
  public function generateCitation(NodeInterface $node) {
    try {
      // Extract node data using the citation service.
      $nodeData = $this->citationService->extractNodeData($node);

      // Generate citations using the citation service.
      $citations = $this->citationService->generateCitations($node);

      // Return the citations and node data as a JSON response.
      return new JsonResponse([
        'success' => TRUE,
        'citations' => $citations,
        'image_info' => $nodeData['image_info'] ?? [],
        'content_type_info' => $nodeData['content_type_info'] ?? [],
        'node_type' => $node->getType(),
        'title' => $node->getTitle(),
      ]);
    }
    catch (\Exception $e) {
      // Return an error response.
      return new JsonResponse([
        'success' => FALSE,
        'error' => $this->t('An error occurred while generating the citation.'),
        'message' => $e->getMessage(),
      // Use 200 instead of 500 to ensure the response is properly
      // handled by JavaScript.
      ], 200);
    }
  }

}
