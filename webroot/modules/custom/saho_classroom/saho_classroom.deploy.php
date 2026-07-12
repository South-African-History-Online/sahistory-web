<?php

/**
 * @file
 * Deploy hooks for saho_classroom, run by `drush deploy` on every environment.
 */

declare(strict_types=1);

/**
 * Reproducibly sync presentation decks from module JSON into nodes.
 *
 * Runs on every deploy so committed slide-schema decks build/update their
 * presentation nodes identically on local, staging and production. Idempotent:
 * matches nodes by a deterministic UUID and preserves editorial publish state.
 */
function saho_classroom_deploy_sync_decks(array &$sandbox): string {
  $terms = \Drupal::service('saho_classroom.term_seeder')->seed();
  $decks = \Drupal::service('saho_classroom.deck_sync')->syncAll();
  return sprintf(
    'Classroom: terms %d created / %d present; decks %d created, %d updated, %d skipped.',
    $terms['created'],
    $terms['existing'],
    $decks['created'],
    $decks['updated'],
    $decks['skipped'],
  );
}

/**
 * Retag the legacy corpus onto the CAPS model (fresh-install-safe).
 *
 * The retag lives as a post_update for existing environments, but installing
 * saho_classroom via config import (fresh prod) marks every MODULE_post_update_*
 * as already-run, so it would be skipped there. Deploy hooks are not name-matched
 * by that install step, so this wrapper guarantees the (idempotent, additive-only)
 * retag runs on the first `drush deploy` of any environment.
 */
function saho_classroom_deploy_retag_legacy_corpus(array &$sandbox): string {
  \Drupal::moduleHandler()->loadInclude('saho_classroom', 'php', 'saho_classroom.post_update');
  return saho_classroom_post_update_retag_legacy_corpus($sandbox);
}

/**
 * Create 301s for the retired classroom views (fresh-install-safe).
 *
 * Same rationale as saho_classroom_deploy_retag_legacy_corpus(): guarantees the
 * idempotent redirect creation runs on fresh installs where the matching
 * post_update is auto-marked complete by config import.
 */
function saho_classroom_deploy_redirect_retired_views(array &$sandbox): string {
  \Drupal::moduleHandler()->loadInclude('saho_classroom', 'php', 'saho_classroom.post_update');
  return saho_classroom_post_update_redirect_retired_views($sandbox);
}
