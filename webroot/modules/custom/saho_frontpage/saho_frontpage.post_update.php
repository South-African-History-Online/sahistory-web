<?php

/**
 * @file
 * Post update functions for saho_frontpage.
 */

declare(strict_types=1);

/**
 * Rebuilds the front page (node 144647) as the Open Record catalogue home.
 *
 * The home layout is a Layout Builder DB override (not config), so this
 * rewrite runs here rather than through config import. The canonical layout
 * lives in \Drupal\saho_frontpage\HomeLayoutRebuilder; re-apply it any time
 * with `drush saho:frontpage-rebuild` (idempotent). The previous layout is
 * snapshotted once into the state key saho_frontpage.node_144647_layout_backup
 * and can be restored by uninstalling saho_frontpage, or manually:
 *
 * @code
 * drush php:eval '
 *   $sections = array_map([\Drupal\layout_builder\Section::class, "fromArray"],
 *     \Drupal::state()->get("saho_frontpage.node_144647_layout_backup"));
 *   \Drupal\node\Entity\Node::load(144647)
 *     ->set("layout_builder__layout", $sections)->save();'
 * @endcode
 */
function saho_frontpage_post_update_rebuild_node_144647_home(&$sandbox = NULL): string {
  return \Drupal::service('saho_frontpage.home_layout')->rebuild();
}
