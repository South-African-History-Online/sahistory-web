<?php

namespace Drupal\uc_order;

/**
 * Defines an interface for order pane plugins.
 *
 * Order pane plugins add panes to the order viewing and administration
 * screens. The default panes include areas to display and edit addresses,
 * products, comments, etc. Developers should use these when they need to
 * display or modify any custom data pertaining to an order. For example, a
 * store that uses a custom checkout pane to find out a customer's desired
 * delivery date would then create a corresponding order pane to show the data
 * on the order screens.
 */
interface OrderPanePluginInterface {

  /**
   * Returns the title of an order pane.
   *
   * @return string
   *   The order pane title.
   */
  public function getTitle();

  /**
   * Returns the classes used to wrap an order pane.
   *
   * Choose "pos-left" to float left against the previous pane or "abs-left"
   * to start a new line of panes.
   *
   * @return array
   *   An array of CSS classes.
   */
  public function getClasses();

  /**
   * Returns the contents of an order pane as a store administrator.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being viewed.
   * @param string $view_mode
   *   The view mode that is being used to render the order.
   *
   * @return array
   *   A render array showing order data.
   */
  public function view(OrderInterface $order, $view_mode);

}
