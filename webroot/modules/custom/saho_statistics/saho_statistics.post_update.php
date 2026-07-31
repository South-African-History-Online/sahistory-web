<?php

/**
 * @file
 * Post update functions for the SAHO Statistics module.
 */

declare(strict_types=1);

/**
 * Adds a covering index for the popular-searches query and seeds the state.
 *
 * During the 2026-07-31 production incident the popular-searches query
 * (GROUP BY over saho_search_queries) showed up in the PHP-FPM slowlog as a
 * full table scan with a temporary table and filesort (~3-4s over 252k rows).
 * The 90-day window matches essentially the whole table (retention is also
 * 90 days), so selectivity cannot help; a covering index on
 * (timestamp, result_count, query_text) lets MySQL satisfy the query with an
 * index-only scan instead of reading full rows.
 *
 * The query itself now only runs from cron, which stores the result in the
 * saho_statistics.popular_searches state entry. Seed that entry here so the
 * search modal has live chips immediately after deploy instead of waiting up
 * to an hour for the first cron rebuild.
 */
function saho_statistics_post_update_popular_searches_covering_index(&$sandbox = NULL): string {
  $schema = \Drupal::database()->schema();
  $added = FALSE;

  if (!$schema->indexExists('saho_search_queries', 'popular_searches')) {
    \Drupal::moduleHandler()->loadInclude('saho_statistics', 'install');
    $spec = saho_statistics_schema()['saho_search_queries'];
    $schema->addIndex(
      'saho_search_queries',
      'popular_searches',
      ['timestamp', 'result_count', ['query_text', 191]],
      $spec
    );
    $added = TRUE;
  }

  \Drupal::state()->set('saho_statistics.popular_searches', _saho_statistics_get_popular_searches(8));
  \Drupal::state()->set('saho_statistics.popular_searches_last_build', \Drupal::time()->getRequestTime());

  return $added
    ? 'Added the popular_searches covering index and seeded the precomputed popular-searches list.'
    : 'The popular_searches index already existed; seeded the precomputed popular-searches list.';
}
