<?php

declare(strict_types=1);

namespace Drupal\saho_connections;

use Drupal\node\NodeInterface;

/**
 * Builds a record's cross-reference data: rail tabs, counts, full lists.
 *
 * One merged/deduped spec backs every surface (rail tabs, toolbar count,
 * hub page chips and lists), so the numbers can never drift apart.
 */
interface ConnectionsBuilderInterface {

  /**
   * Builds saho:saho-related-tabs props for the record rail.
   *
   * Output is the exact shape the rail rendered before extraction:
   * ['title' => 'Cross-references', 'tabs' => [...]] or [] when the record
   * has no relations. High-degree tabs carry a bounded item sample plus a
   * 'summary' (count line, optional CTA).
   *
   * @param \Drupal\node\NodeInterface $node
   *   The record node.
   *
   * @return array
   *   Component props, or [] when there are no relations.
   */
  public function tabs(NodeInterface $node): array;

  /**
   * Counts-only inventory of the record's tabs, in rail order.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The record node.
   *
   * @return array
   *   [tab_id => ['label' => string, 'count' => int]], consistent with the
   *   counts tabs() reports.
   */
  public function inventory(NodeInterface $node): array;

  /**
   * One tab's items, paged over the full list.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The record node.
   * @param string $tab_id
   *   A tab id from inventory().
   * @param int $page
   *   Zero-based page number.
   * @param int $page_size
   *   Items per page.
   *
   * @return array
   *   ['label' => string, 'total' => int, 'items' => array] where items are
   *   ['label', 'href', 'note', 'type'] rows; empty items past the end.
   *   ['total' => 0, 'label' => '', 'items' => []] for an unknown tab.
   */
  public function items(NodeInterface $node, string $tab_id, int $page, int $page_size): array;

  /**
   * Sum of all tab counts - the toolbar "Connections · N" figure.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The record node.
   *
   * @return int
   *   Total connected records.
   */
  public function totalCount(NodeInterface $node): int;

  /**
   * List cache tags the connections data depends on.
   *
   * @return string[]
   *   node_list:<bundle> tags for the bundles that can appear as children.
   */
  public function cacheTags(): array;

  /**
   * Clears the per-request memo (kernel tests re-running fixtures).
   */
  public function resetCache(): void;

}
