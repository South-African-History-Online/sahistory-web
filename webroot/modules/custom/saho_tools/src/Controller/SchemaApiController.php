<?php

namespace Drupal\saho_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for Schema.org API endpoint.
 *
 * Provides programmatic access to Schema.org JSON-LD structured data
 * for any SAHO node. Enables external systems to retrieve machine-readable
 * metadata for integration and citation purposes.
 */
class SchemaApiController extends ControllerBase {

  /**
   * The Schema.org service.
   *
   * @var \Drupal\saho_tools\Service\SchemaOrgService
   */
  protected SchemaOrgService $schemaOrgService;

  /**
   * Constructs a SchemaApiController.
   *
   * @param \Drupal\saho_tools\Service\SchemaOrgService $schemaOrgService
   *   The Schema.org service.
   */
  public function __construct(SchemaOrgService $schemaOrgService) {
    $this->schemaOrgService = $schemaOrgService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saho_tools.schema_org_service')
    );
  }

  /**
   * Generate Schema.org JSON-LD for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with Schema.org data.
   */
  public function getNodeSchema(NodeInterface $node, Request $request): JsonResponse {
    // Check if node is published.
    if (!$node->isPublished()) {
      throw new NotFoundHttpException('Node not found or not published.');
    }

    // Generate Schema.org structured data.
    $schema = $this->schemaOrgService->generateSchemaForNode($node);

    if (empty($schema)) {
      return new JsonResponse([
        'error' => 'Schema.org data not available for this content type.',
        'node_type' => $node->getType(),
        'node_id' => $node->id(),
      ], 404);
    }

    // Add metadata wrapper.
    $response_data = [
      'schema' => $schema,
      'meta' => [
        'node_id' => $node->id(),
        'node_type' => $node->getType(),
        'title' => $node->getTitle(),
        'url' => $node->toUrl()->setAbsolute()->toString(),
        'generated_at' => date('c'),
        'api_version' => '1.0',
      ],
    ];

    $response = new JsonResponse($response_data, 200, [
      'Content-Type' => 'application/ld+json; charset=UTF-8',
    ]);

    // Add CORS headers for external access.
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

    // Add cache headers.
    $response->setPublic();
    $response->setMaxAge(3600);
    $response->headers->set('X-Robots-Tag', 'noindex');

    return $response;
  }

  /**
   * Get Schema.org type for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with Schema.org type information.
   */
  public function getNodeSchemaType(NodeInterface $node, Request $request): JsonResponse {
    if (!$node->isPublished()) {
      throw new NotFoundHttpException('Node not found or not published.');
    }

    $schema = $this->schemaOrgService->generateSchemaForNode($node);

    if (empty($schema)) {
      return new JsonResponse([
        'error' => 'Schema.org data not available for this content type.',
        'node_type' => $node->getType(),
      ], 404);
    }

    // Extract just the @type information.
    $type_info = [
      '@context' => $schema['@context'] ?? 'https://schema.org',
      '@type' => $schema['@type'] ?? 'Thing',
      'node_type' => $node->getType(),
      'node_id' => $node->id(),
      'url' => $node->toUrl()->setAbsolute()->toString(),
    ];

    $response = new JsonResponse($type_info);
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->setPublic();
    $response->setMaxAge(86400);

    return $response;
  }

  /**
   * List all available Schema.org types.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with available types.
   */
  public function listSchemaTypes(Request $request): JsonResponse {
    $types = [
      'article' => 'ScholarlyArticle',
      'biography' => 'Person',
      'event' => 'Event',
      'archive' => 'ArchiveComponent',
      'place' => 'Place',
      'product' => 'Book',
      'image' => 'ImageObject',
      'gallery_image' => 'ImageObject',
    ];

    // Get content counts.
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $counts = [];

    foreach (array_keys($types) as $type) {
      $query = $node_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $type)
        ->condition('status', 1);
      $counts[$type] = $query->count()->execute();
    }

    $response_data = [
      'schema_types' => $types,
      'content_counts' => $counts,
      'total_nodes' => array_sum($counts),
      'api_endpoints' => [
        'node_schema' => '/api/schema/{node_id}',
        'node_type' => '/api/schema/{node_id}/type',
        'list_types' => '/api/schema/types',
      ],
      'documentation' => [
        'llm_txt' => '/llm.txt',
        'citation_api' => '/api/citation/{node_id}',
      ],
    ];

    $response = new JsonResponse($response_data);
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->setPublic();
    $response->setMaxAge(3600);

    return $response;
  }

}
