<?php

/**
 * @file
 * Post update functions for saho_cleanup.
 */

declare(strict_types=1);

/**
 * Removes stale system.schema entries for modules gone from the codebase.
 *
 * The statistics module (dropped for performance, see #327/#328) and dev-only
 * modules left orphaned schema rows behind on production, which makes every
 * updb/status report complain about missing modules.
 */
function saho_cleanup_post_update_remove_stale_module_schema(&$sandbox = NULL): string {
  $schema = \Drupal::keyValue('system.schema');
  $removed = [];
  foreach (['statistics', 'devel_kint', 'devel', 'kint'] as $module) {
    if ($schema->has($module) && !\Drupal::moduleHandler()->moduleExists($module)) {
      $schema->delete($module);
      $removed[] = $module;
    }
  }
  return $removed === []
    ? 'No stale system.schema entries found.'
    : 'Removed stale system.schema entries: ' . implode(', ', $removed) . '.';
}
