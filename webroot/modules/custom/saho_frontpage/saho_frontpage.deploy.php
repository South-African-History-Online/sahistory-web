<?php

/**
 * @file
 * Deploy hooks for saho_frontpage, run by `drush deploy` on every environment.
 */

declare(strict_types=1);

/**
 * Rebuild the node-144647 catalogue home layout (fresh-install-safe).
 *
 * The rebuild also exists as saho_frontpage_post_update_rebuild_node_144647_home
 * for environments where the module is already installed, but installing
 * saho_frontpage via config import (a fresh production environment) marks every
 * MODULE_post_update_* as already-run, so that hook is skipped there and the
 * front page keeps its legacy layout. Deploy hooks are not name-matched by that
 * install step, so this guarantees the (idempotent) rebuild runs on the first
 * `drush deploy`. The deploy script also invokes `drush saho:frontpage-rebuild`
 * explicitly as belt-and-suspenders.
 */
function saho_frontpage_deploy_rebuild_node_144647_home(array &$sandbox): string {
  return \Drupal::service('saho_frontpage.home_layout')->rebuild();
}
