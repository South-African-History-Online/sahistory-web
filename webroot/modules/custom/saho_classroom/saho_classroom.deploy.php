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
