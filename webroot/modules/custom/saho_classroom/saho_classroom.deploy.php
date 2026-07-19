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

/**
 * Backfill field_caps_topic from same-topic siblings on legacy articles.
 *
 * The retag migration tagged most "History Grade N - Topic M ..." companion
 * articles with their CAPS topic, but missed some (e.g. the Grade 11 Topic 4
 * Contextual Overview) - and without the topic term the teacher CTA cannot
 * pair the article with its presentation deck and falls back to the generic
 * presentations browse page. Siblings share the "History Grade N - Topic M"
 * title prefix, so an untagged article inherits the topic its tagged siblings
 * agree on. Idempotent and additive-only: never overwrites an existing value,
 * skips prefixes whose tagged siblings disagree.
 */
function saho_classroom_deploy_backfill_sibling_caps_topics(array &$sandbox): string {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $nids = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', 'article')
    ->condition('title', 'History Grade %', 'LIKE')
    ->execute();
  if (!$nids) {
    return 'No legacy grade-topic articles found.';
  }

  // Group by the "History Grade N - Topic M" prefix and collect each group's
  // agreed topic term from the already-tagged members.
  $groups = [];
  foreach ($storage->loadMultiple($nids) as $node) {
    if (!preg_match('/^(History Grade \d+ - Topic \d+)\b/', (string) $node->label(), $m)) {
      continue;
    }
    $groups[$m[1]][] = $node;
  }

  $filled = 0;
  $conflicts = [];
  foreach ($groups as $prefix => $nodes) {
    $topics = [];
    foreach ($nodes as $node) {
      if ($node->hasField('field_caps_topic') && !$node->get('field_caps_topic')->isEmpty()) {
        $topics[(int) $node->get('field_caps_topic')->target_id] = TRUE;
      }
    }
    if (count($topics) !== 1) {
      if (count($topics) > 1) {
        $conflicts[] = $prefix;
      }
      continue;
    }
    $topic_id = array_key_first($topics);
    foreach ($nodes as $node) {
      if ($node->hasField('field_caps_topic') && $node->get('field_caps_topic')->isEmpty()) {
        $node->set('field_caps_topic', $topic_id);
        $node->setNewRevision(FALSE);
        $node->setSyncing(TRUE);
        $node->save();
        $filled++;
      }
    }
  }

  $message = sprintf('Backfilled field_caps_topic on %d legacy grade-topic articles.', $filled);
  if ($conflicts) {
    $message .= ' Skipped conflicting prefixes: ' . implode('; ', $conflicts) . '.';
  }
  return $message;
}
