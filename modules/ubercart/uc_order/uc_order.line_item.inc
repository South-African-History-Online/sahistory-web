<?php

/**
 * @file
 * Contains the callbacks for the default line items for orders and
 * the various functions that make line items work.
 *
 * Line items are defined using hook_uc_line_item() and use a callback to
 * handle the different processes involved in line item
 * viewing/editing/calculating. The default line items are defined in
 * uc_order_uc_line_item() in uc_order.module.
 */

/**
 * Updates a line item.
 */
function uc_order_update_line_item($id, $title, $amount, $data = NULL) {
  $fields = array(
    'title' => $title,
    'amount' => $amount,
  );

  if (!is_null($data)) {
    $fields['data'] = serialize($data);
  }

  db_update('uc_order_line_items')
    ->fields($fields)
    ->condition('line_item_id', $id)
    ->execute();

  return TRUE;
}

/**
 * Deletes a specific line item, or every line item in an order.
 *
 * @param int $id
 *   The line item ID, or order ID.
 * @param bool $order
 *   If FALSE, deletes the line item with the specified ID (default).
 *   If TRUE, deletes all line items on the order with the specified ID.
 *
 * @return bool
 *   Always TRUE.
 */
function uc_order_delete_line_item($id, $order = FALSE) {
  if ($order === FALSE) {
    db_delete('uc_order_line_items')
      ->condition('line_item_id', $id)
      ->execute();
  }
  else {
    db_delete('uc_order_line_items')
      ->condition('order_id', $id)
      ->execute();
  }

  return TRUE;
}

/**
 * Adds a line item to an order.
 */
function uc_order_line_item_add($order_id, $type, $title, $amount, $weight = NULL, $data = NULL) {
  if (is_null($weight)) {
    $line_item_manager = \Drupal::service('plugin.manager.uc_order.line_item');
    $weight = $line_item_manager->getDefinition($type)['weight'];
  }

  $line_item = array(
    'order_id' => $order_id,
    'type' => $type,
    'title' => $title,
    'amount' => $amount,
    'weight' => $weight,
    'data' => serialize($data),
  );

  $line_item['line_item_id'] = db_insert('uc_order_line_items')
    ->fields($line_item)
    ->execute();

  $line_item['data'] = $data;

  return $line_item;
}
