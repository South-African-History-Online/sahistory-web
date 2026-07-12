<?php

namespace Drupal\saho_tools\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\saho_refs\DisplayRefService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Typeahead suggestions for the global search overlay (R3 #478).
 *
 * Queries the saho_content Solr index for the top matches and returns a
 * compact JSON payload: title, url, bundle and display reference per row,
 * plus the total so the overlay can offer "search all N results".
 */
class SearchSuggestController extends ControllerBase {

  /**
   * Suggestion page size.
   */
  protected const LIMIT = 5;

  /**
   * Maximum accepted keyword length.
   *
   * The endpoint is unauthenticated and Solr-backed, so cap the keyword to a
   * sane length before it reaches the index.
   */
  protected const MAX_KEYWORD_LENGTH = 100;

  /**
   * The optional display reference service (saho_refs).
   *
   * @var \Drupal\saho_refs\DisplayRefService|null
   */
  protected $displayRef;

  /**
   * Constructs a SearchSuggestController object.
   *
   * @param \Drupal\saho_refs\DisplayRefService|null $display_ref
   *   The display reference service, or NULL when saho_refs is not installed.
   */
  public function __construct(?DisplayRefService $display_ref = NULL) {
    $this->displayRef = $display_ref;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->has('saho_refs.display_ref') ? $container->get('saho_refs.display_ref') : NULL
    );
  }

  /**
   * Returns typeahead suggestions for a keyword.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request; ?q= carries the keyword.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   Suggestions payload: {total, suggestions: [{title,url,type,ref}]}.
   */
  public function suggest(Request $request): CacheableJsonResponse {
    $keyword = trim((string) $request->query->get('q', ''));
    // Cap the keyword length before it reaches Solr.
    $keyword = mb_substr($keyword, 0, self::MAX_KEYWORD_LENGTH);
    $payload = ['total' => 0, 'suggestions' => []];

    if (mb_strlen($keyword) >= 2 && $this->moduleHandler()->moduleExists('search_api')) {
      $index_storage = $this->entityTypeManager()->getStorage('search_api_index');
      /** @var \Drupal\search_api\IndexInterface|null $index */
      $index = $index_storage->load('saho_content');
      if ($index) {
        try {
          $query = $index->query()
            ->keys($keyword)
            ->range(0, self::LIMIT)
            ->sort('search_api_relevance', 'DESC');
          $results = $query->execute();
          $payload['total'] = (int) $results->getResultCount();
          foreach ($results as $item) {
            $entity = $item->getOriginalObject()->getValue();
            if (!$entity || !$entity->access('view')) {
              continue;
            }
            $payload['suggestions'][] = [
              'title' => (string) $entity->label(),
              'url' => $entity->toUrl()->toString(),
              'type' => $entity->bundle(),
              'ref' => $this->displayRef ? $this->displayRef->getRef($entity) : '',
            ];
          }
        }
        catch (\Exception $e) {
          // A backend/Solr fault must never 500 the typeahead: log and return
          // the empty payload the client already handles.
          $this->getLogger('saho_tools')->warning('Search suggest query failed: @message', ['@message' => $e->getMessage()]);
          $payload = ['total' => 0, 'suggestions' => []];
        }
      }
    }

    $response = new CacheableJsonResponse($payload);
    $meta = new CacheableMetadata();
    $meta->addCacheContexts(['url.query_args:q']);
    $meta->addCacheTags(['node_list']);
    $meta->setCacheMaxAge(300);
    $response->addCacheableDependency($meta);
    return $response;
  }

}
