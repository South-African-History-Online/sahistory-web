<?php

/**
 * @file
 * Deploy hooks for saho_timeline (run by drush deploy:hook after cim).
 *
 * Deploy hooks rather than post_update: config-import on a fresh install
 * marks post_updates as already run, silently skipping them.
 */

use Drupal\redirect\Entity\Redirect;

/**
 * Redirect the retired /saho-timeline SPA path to the rebuilt /timeline.
 *
 * Fragments (#event-12401) survive: browsers re-attach the hash to the
 * redirect target, where the app resolves it to the detail panel.
 */
function saho_timeline_deploy_redirect_saho_timeline(): string {
  if (!\Drupal::moduleHandler()->moduleExists('redirect')) {
    return 'redirect module absent - nothing to do.';
  }

  // Node 151109 ("SAHO Timeline and Milestones", the organisation's own
  // history page) holds the /saho-timeline alias - unreachable for years
  // while the static SPA shadowed it, but it would shadow the redirect
  // now. Move it to a descriptive alias first.
  $alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
  foreach ($alias_storage->loadByProperties(['alias' => '/saho-timeline']) as $alias) {
    $alias->setAlias('/saho-timeline-and-milestones');
    $alias->save();
  }

  $storage = \Drupal::entityTypeManager()->getStorage('redirect');
  $existing = $storage->loadByProperties(['redirect_source__path' => 'saho-timeline']);
  if ($existing) {
    return 'Redirect /saho-timeline already exists.';
  }
  Redirect::create([
    'redirect_source' => ['path' => 'saho-timeline', 'query' => []],
    'redirect_redirect' => ['uri' => 'internal:/timeline'],
    'status_code' => 301,
    'language' => 'und',
  ])->save();
  return 'Created 301 /saho-timeline -> /timeline.';
}
