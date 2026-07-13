<?php

namespace Drupal\saho_tools\EventSubscriber;

use Drupal\search_api_solr\Event\PostExtractResultsEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Captures Solr spellcheck collations for "Did you mean?" suggestions.
 *
 * The hook_search_api_solr_search_results_alter() hook was removed in
 * search_api_solr:4.3.0, so the spellcheck-capture logic now runs as an
 * event subscriber on the PostExtractResultsEvent. When a saho_content
 * search returns zero results, the best collation is stored on the request
 * attributes so saho_tools_preprocess_views_view() can surface it in the
 * global search template.
 *
 * @see saho_tools_search_api_solr_query_alter()
 * @see saho_tools_preprocess_views_view()
 */
class SolrSpellcheckSubscriber implements EventSubscriberInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a SolrSpellcheckSubscriber object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiSolrEvents::POST_EXTRACT_RESULTS => 'onPostExtractResults',
    ];
  }

  /**
   * Stores the best spellcheck collation for zero-result saho_content queries.
   *
   * @param \Drupal\search_api_solr\Event\PostExtractResultsEvent $event
   *   The event object.
   */
  public function onPostExtractResults(PostExtractResultsEvent $event): void {
    $result_set = $event->getSearchApiResultSet();

    if ($result_set->getQuery()->getIndex()->id() !== 'saho_content') {
      return;
    }

    // Only suggest alternatives when there are no results.
    if ($result_set->getResultCount() > 0) {
      return;
    }

    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return;
    }

    try {
      $solarium_result = $event->getSolariumResult();
      if (!method_exists($solarium_result, 'getSpellcheck')) {
        return;
      }
      $spellcheck = $solarium_result->getSpellcheck();
      if (!$spellcheck) {
        return;
      }
      $collations = $spellcheck->getCollations();
      if (!empty($collations)) {
        $first_collation = reset($collations);
        $suggestion = $first_collation->getQuery();
        if (!empty($suggestion)) {
          $request->attributes->set('saho_spellcheck_suggestion', $suggestion);
        }
      }
    }
    catch (\Exception $e) {
      // Spellcheck is non-critical - silently ignore errors.
    }
  }

}
