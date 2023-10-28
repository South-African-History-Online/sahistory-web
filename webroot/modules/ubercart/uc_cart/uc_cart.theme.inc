<?php

/**
 * @file
 * Theme functions for the uc_cart module.
 */

/**
 * Formats the cart contents table on the checkout page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - show_subtotal: TRUE or FALSE indicating if you want a subtotal row
 *     displayed in the table.
 *   - items: An associative array of cart item information containing:
 *     - qty: Quantity in cart.
 *     - title: Item title.
 *     - price: Item price.
 *     - desc: Item description.
 *
 * @return
 *   The HTML output for the cart review table.
 *
 * @ingroup themeable
 */
function theme_uc_cart_review_table(array $variables) {
  $items = $variables['items'];
  $show_subtotal = $variables['show_subtotal'];

  $subtotal = 0;

  // Set up table header.
  $header = array(
    array('data' => t('Quantity'), 'class' => array('qty')),
    array('data' => t('Products'), 'class' => array('products')),
    array('data' => t('Price'), 'class' => array('price')),
  );

  // Set up table rows.
  // @todo Replace with Views.
  foreach ($items as $item) {
    $subtotal += $item->price->value * $item->qty->value;
    $rows[] = array(
      'data' => array(
        array(
          'data' => array(
            '#theme' => 'uc_qty',
            '#qty' => $item->qty->value,
          ),
          'class' => array('qty'),
        ),
        array(
          'data' => array('#markup' => $item->title->value . uc_product_get_description($item)),
          'class' => array('products'),
        ),
        array(
          'data' => array(
            '#theme' => 'uc_price',
            '#price' => $item->price->value * $item->qty->value,
          ),
          'class' => array('price'),
        ),
      ),
      'no_striping' => TRUE,
    );
  }

  // Add the subtotal in the table footer.
  $footer = array();
  if ($show_subtotal) {
    $footer[] = array(
      'data' => array(
        // Cells.
        array(
          'data' => array('#markup' => t('Subtotal:')),
          'colspan' => 2,
          'class' => array('subtotal-title'),
        ),
        array(
          'data' => array(
            '#theme' => 'uc_price',
            '#price' => $subtotal,
          ),
          'class' => array('price'),
        ),
      ),
      // Row attributes.
      'no_striping' => TRUE,
    );
  }

  $table = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#footer' => $footer,
    '#attributes' => array('class' => array('cart-review')),
  );

  return drupal_render($table);
}

/**
 * Themes the checkout review order page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form, that by default includes
 *     the 'Back' and 'Submit order' buttons at the bottom of the review page.
 *   - panes: An associative array for each checkout pane that has information
 *     to add to the review page, keyed by the pane title:
 *     - <pane title>: The data returned for that pane or an array of returned
 *       data.
 *
 * @return
 *   A string of HTML for the page contents.
 *
 * @ingroup themeable
 */
function theme_uc_cart_checkout_review(array $variables) {
  $panes = $variables['panes'];
  $form = $variables['form'];

  $build['instructions'] = array(
    '#prefix' => '<p>',
    '#markup' => t("Your order is almost complete. Please review the details below and click 'Submit order' if all the information is correct.  You may use the 'Back' button to make changes to your order if necessary."),
    '#suffix' => '</p>',
  );

  $rows = array();
  foreach ($panes as $title => $data) {
    // First pane row is the pane title.
    $rows[] = array(
      'data' => array(
        array('data' => $title, 'colspan' => '2'),
      ),
      'no_striping' => TRUE,
      'class' => array('pane-title-row'),
    );
    // Subsequent pane rows may be subtitles or subtitle-data pairs.
    foreach ($data as $row) {
      if (isset($row['data'])) {
        if (isset($row['border'])) {
          $border = 'row-border-' . $row['border'];
        }
        else {
          $border = '';
        }
        $rows[] = array(
          'data' => array(
            array('data' => $row['title'] . ':', 'class' => array('title-col')),
            array('data' => $row['data'], 'class' => array('data-col')),
          ),
          'no_striping' => TRUE,
          'class' => array($border),
        );
      }
      else {
        // Row is the cart review table.
        $rows[] = array('data' => array(array('data' => $row, 'colspan' => '2')));
      }
    }
  }

  $rows[] = array(
    'data' => array(
      array('data' => $form, 'colspan' => '2'),
    ),
    'no_striping' => TRUE,
    'class' => array('review-button-row')
  );

  $build['review-table'] = array(
    '#type' => 'table',
    '#rows' => $rows,
    '#attributes' => array('class' => array('order-review-table')),
  );

  return drupal_render($build);
}
