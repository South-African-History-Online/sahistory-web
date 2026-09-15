<?php

/**
 * @file
 * Post-update hooks for saho_utils.
 */

declare(strict_types=1);

use Drupal\node\NodeInterface;
use Drupal\saho_utils\RegisterGrid\RegisterGridMigration;

/**
 * Rewrites the four legacy register blocks to register_grid_block (#485).
 *
 * The legacy blocks live only in Layout Builder overrides stored on nodes
 * (today: history_classroom_block on the front page, node 144647), so
 * config import cannot reach them. Every node carrying a layout override is
 * loaded, its components remapped through RegisterGridMigration, and saved as
 * a new revision. Runs before config import uninstalls the legacy modules
 * (drush deploy: updatedb, then config:import).
 */
function saho_utils_post_update_register_grid_block(array &$sandbox): string {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $nids = $storage->getQuery()
    ->exists('layout_builder__layout')
    ->accessCheck(FALSE)
    ->execute();

  $rewritten = 0;
  $nodes_touched = 0;
  foreach ($storage->loadMultiple($nids) as $node) {
    if (!$node instanceof NodeInterface || !$node->hasField('layout_builder__layout')) {
      continue;
    }
    /** @var \Drupal\layout_builder\Field\LayoutSectionItemList $layout_field */
    $layout_field = $node->get('layout_builder__layout');
    $sections = $layout_field->getSections();
    $changed = FALSE;
    foreach ($sections as $section) {
      foreach ($section->getComponents() as $component) {
        if (!RegisterGridMigration::isLegacy($component->getPluginId())) {
          continue;
        }
        $new = RegisterGridMigration::map($component->get('configuration'));
        if ($new === NULL) {
          continue;
        }
        $component->setConfiguration($new);
        $changed = TRUE;
        $rewritten++;
      }
    }
    if (!$changed) {
      continue;
    }
    $node->set('layout_builder__layout', array_map(static fn($section) => ['section' => $section], $sections));
    if ($node->getEntityType()->isRevisionable()) {
      $node->setNewRevision(TRUE);
      $node->setRevisionLogMessage('Legacy register blocks rewritten to register_grid_block (#485).');
    }
    $node->save();
    $nodes_touched++;
  }

  return sprintf('Register grid migration: %d component(s) rewritten on %d node(s).', $rewritten, $nodes_touched);
}
