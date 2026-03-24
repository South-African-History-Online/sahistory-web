<?php

/**
 * @file
 * Theme hooks for Views integration.
 */

/**
 * Implements hook_views_pre_render().
 *
 * Restricts the publications catalog to the 'publication' product type,
 * removing Champion subscription products (bundle: 'default') from results.
 */
function saho_shop_views_pre_render(\Drupal\views\ViewExecutable $view) {
  if ($view->id() !== 'publications_search') {
    return;
  }
  foreach ($view->result as $key => $row) {
    // Each Search API result row stores its entity in _item or _entity.
    $entity = NULL;
    if (isset($row->_item)) {
      $entity = $row->_item->getOriginalObject()->getValue();
    }
    elseif (isset($row->_entity)) {
      $entity = $row->_entity;
    }
    if ($entity && method_exists($entity, 'bundle') && $entity->bundle() !== 'publication') {
      unset($view->result[$key]);
    }
  }
}
