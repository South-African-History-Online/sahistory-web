<?php

/**
 * @file
 * Deploy hooks for saho_utils (run by drush deploy:hook after cim).
 *
 * Deploy hooks rather than post_update: they run after config import, so
 * imported config (here the pathauto pattern) is in place when they execute.
 */

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\pathauto\PathautoState;

/**
 * Backfills /image/<title> aliases for image nodes that have none (#583).
 *
 * The image bundle was never in a pathauto pattern, so every image record
 * created since the D7 migration lives at /node/NNN while the migrated ones
 * carry /image/<title>. The mainpath pattern now covers the bundle; this walks
 * the existing backlog in batches of 200 and generates the missing aliases.
 * Idempotent: a re-run finds nothing to do. Existing aliases are never touched
 * (only un-aliased nodes are selected, and pathauto's update_action is 0).
 *
 * @param array $sandbox
 *   The batch sandbox.
 *
 * @return string
 *   A summary for the deploy log.
 */
function saho_utils_deploy_image_node_aliases(array &$sandbox): string {
  if (!\Drupal::moduleHandler()->moduleExists('pathauto')) {
    $sandbox['#finished'] = 1;
    return 'pathauto module absent - nothing to do.';
  }

  $database = \Drupal::database();
  if (!isset($sandbox['total'])) {
    $sandbox['total'] = (int) _saho_utils_unaliased_image_nodes($database)
      ->countQuery()
      ->execute()
      ->fetchField();
    $sandbox['done'] = 0;
    $sandbox['created'] = 0;
    $sandbox['last_nid'] = 0;
  }

  // Cursor on nid rather than re-selecting "still un-aliased": a node the
  // generator declines (empty title, pathauto state SKIP) must not be
  // re-selected forever.
  $nids = _saho_utils_unaliased_image_nodes($database)
    ->condition('n.nid', $sandbox['last_nid'], '>')
    ->orderBy('n.nid')
    ->range(0, 200)
    ->execute()
    ->fetchCol();

  if ($nids === []) {
    $sandbox['#finished'] = 1;
    return sprintf('Image node aliases: created %d for %d un-aliased image nodes.', $sandbox['created'], $sandbox['done']);
  }

  $generator = \Drupal::service('pathauto.generator');
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  foreach ($storage->loadMultiple($nids) as $node) {
    // Nodes saved while no pattern covered the bundle carry pathauto state
    // SKIP (the "generate automatic alias" box was absent, so unchecked).
    // With no alias to protect, SKIP is an artefact, not an editorial
    // choice: opt the node back in and persist that so later edits keep
    // the alias current.
    $path_item = $node->get('path')->first();
    if ($path_item && (int) $path_item->get('pathauto')->getValue() === PathautoState::SKIP) {
      $path_item->get('pathauto')->setValue(PathautoState::CREATE);
      $path_item->get('pathauto')->persist();
    }
    if ($generator->updateEntityAlias($node, 'insert')) {
      $sandbox['created']++;
    }
    $sandbox['done']++;
    $sandbox['last_nid'] = (int) $node->id();
  }
  // Keep memory flat across the ~14k-node backlog.
  $storage->resetCache($nids);

  $sandbox['#finished'] = $sandbox['total'] > 0 ? min(0.99, $sandbox['done'] / $sandbox['total']) : 1;
  return sprintf('Image node aliases: %d of %d processed, %d created so far.', $sandbox['done'], $sandbox['total'], $sandbox['created']);
}

/**
 * Builds the query for published-language image nodes with no path alias.
 *
 * @param \Drupal\Core\Database\Connection $database
 *   The database connection.
 *
 * @return \Drupal\Core\Database\Query\SelectInterface
 *   A select query over node_field_data (alias n) returning nid.
 */
function _saho_utils_unaliased_image_nodes(Connection $database): SelectInterface {
  $query = $database->select('node_field_data', 'n')
    ->fields('n', ['nid'])
    ->condition('n.type', 'image')
    ->condition('n.default_langcode', 1);
  $query->leftJoin('path_alias', 'p', "p.path = CONCAT('/node/', n.nid) AND p.status = 1");
  $query->isNull('p.id');
  return $query;
}
