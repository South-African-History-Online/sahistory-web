<?php

declare(strict_types=1);

namespace Drupal\saho_webform_bulk;

use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposes the results page's own filter query for counting and batching.
 *
 * Extends the list builder so the search/state filtering is Webform's, not a
 * copy that drifts. The request-driven initialize() is bypassed: callers pass
 * the webform and filter explicitly, which also makes it usable from a batch
 * where there is no webform route parameter.
 */
final class FilteredSubmissionQuery extends WebformSubmissionListBuilder {

  /**
   * Builds a query object for one webform and filter.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform whose submissions are targeted.
   * @param string|null $keys
   *   The results page "search" keyword, if any.
   * @param string|null $state
   *   The results page "state" filter (starred, locked, completed...), if any.
   */
  public static function forFilter(ContainerInterface $container, WebformInterface $webform, ?string $keys, ?string $state): self {
    $entity_type = $container->get('entity_type.manager')->getDefinition('webform_submission');
    /** @var self $instance */
    $instance = static::createInstance($container, $entity_type);
    $instance->webform = $webform;
    $instance->sourceEntity = NULL;
    $instance->sourceEntityTypeId = NULL;
    $instance->account = NULL;
    $instance->keys = $keys !== NULL && $keys !== '' ? $keys : '';
    $instance->state = $state !== NULL && $state !== '' ? $state : '';
    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * The parent reads the webform, filter and pager from the current request
   * and route; ::forFilter() sets those explicitly instead.
   */
  protected function initialize() {
  }

  /**
   * The search keyword this query filters on ('' when none).
   */
  public function keys(): string {
    return (string) $this->keys;
  }

  /**
   * The state this query filters on ('' when none).
   */
  public function state(): string {
    return (string) $this->state;
  }

  /**
   * Number of submissions matching the filter.
   */
  public function total(): int {
    return (int) $this->getTotal($this->keys, $this->state, $this->sourceEntityTypeId);
  }

  /**
   * The lowest submission ids matching the filter, at most $limit of them.
   *
   * Sorted by sid so a batch that deletes what it loads always advances.
   *
   * @return int[]
   *   Submission ids.
   */
  public function ids(int $limit): array {
    $ids = $this->getQuery($this->keys, $this->state, $this->sourceEntityTypeId)
      ->accessCheck(FALSE)
      ->sort('sid')
      ->range(0, $limit)
      ->execute();
    return array_map('intval', array_values($ids));
  }

}
