<?php

namespace Drupal\saho_tools\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
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
    $payload = ['total' => 0, 'suggestions' => []];

    if (mb_strlen($keyword) >= 2 && $this->moduleHandler()->moduleExists('search_api')) {
      $index_storage = $this->entityTypeManager()->getStorage('search_api_index');
      /** @var \Drupal\search_api\IndexInterface|null $index */
      $index = $index_storage->load('saho_content');
      if ($index) {
        $query = $index->query()
          ->keys($keyword)
          ->range(0, self::LIMIT)
          ->sort('search_api_relevance', 'DESC');
        $results = $query->execute();
        $payload['total'] = (int) $results->getResultCount();
        $refs = \Drupal::hasService('saho_refs.display_ref')
          ? \Drupal::service('saho_refs.display_ref')
          : NULL;
        foreach ($results as $item) {
          $entity = $item->getOriginalObject()->getValue();
          if (!$entity || !$entity->access('view')) {
            continue;
          }
          $payload['suggestions'][] = [
            'title' => (string) $entity->label(),
            'url' => $entity->toUrl()->toString(),
            'type' => $entity->bundle(),
            'ref' => $refs ? $refs->getRef($entity) : '',
          ];
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
