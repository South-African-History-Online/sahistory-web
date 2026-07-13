<?php

/**
 * @file
 * Post update functions for saho_cleanup.
 */

declare(strict_types=1);

/**
 * Removes phantom modules from core.extension before config import runs.
 *
 * A module enabled on an environment whose code later left the codebase
 * (staging carried saho_example_block) stays listed in the active
 * core.extension config. That phantom breaks ANY config import that
 * installs modules: ModuleInstaller re-resolves the full extension list and
 * throws UnknownExtensionException - which is exactly how the Open Record
 * staging deploy died. Post-updates run during updatedb, which drush deploy
 * executes BEFORE config:import, so this strips phantoms in time on every
 * environment without hand surgery.
 */
function saho_cleanup_post_update_remove_phantom_extensions(&$sandbox = NULL): string {
  $module_list = \Drupal::service('extension.list.module');
  $extension_config = \Drupal::configFactory()->getEditable('core.extension');
  $schema = \Drupal::keyValue('system.schema');
  $modules = $extension_config->get('module') ?? [];
  $removed = [];
  foreach (array_keys($modules) as $name) {
    if (!$module_list->exists($name)) {
      unset($modules[$name]);
      $schema->delete($name);
      $removed[] = $name;
    }
  }
  if ($removed !== []) {
    $extension_config->set('module', $modules)->save();
  }
  return $removed === []
    ? 'No phantom modules in core.extension.'
    : 'Removed phantom modules from core.extension: ' . implode(', ', $removed) . '.';
}

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
