<?php

namespace Drupal\uc_order\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_order\OrderInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for order routes.
 */
class OrderController extends ControllerBase {

  /**
   * Creates an order for the specified user, and redirects to the edit page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to create the order for.
   */
  public function createForUser(UserInterface $user) {
    $order = Order::create([
      'uid' => $user->id(),
      'order_status' => uc_order_state_default('post_checkout'),
    ]);
    $order->save();

    uc_order_comment_save($order->id(), \Drupal::currentUser()->id(), $this->t('Order created by the administration.'), 'admin');

    return $this->redirect('entity.uc_order.edit_form', ['uc_order' => $order->id()]);
  }

  /**
   * Displays an order invoice.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order entity.
   * @param bool $print
   *   Whether to generate a printable version.
   *
   * @return array|string
   *   A render array or HTML markup in a form suitable for printing.
   */
  public function invoice(OrderInterface $uc_order, $print = FALSE) {
    $invoice = array(
      '#theme' => 'uc_order_invoice',
      '#order' => $uc_order,
      '#op' => $print ? 'print' : 'view',
    );

    if ($print) {
      $build = array(
        '#theme' => 'uc_order_invoice_page',
        '#content' => $invoice,
      );
      $markup = \Drupal::service('renderer')->renderPlain($build);
      $response = new Response($markup);
      $response->headers->set('Content-Type', 'text/html; charset=utf-8');
      return $response;
    }

    return $invoice;
  }

  /**
   * Displays a log of changes made to an order.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order entity.
   *
   * @return array
   *   A render array.
   */
  public function log(OrderInterface $uc_order) {
    $result = db_query('SELECT order_log_id, uid, changes, created FROM {uc_order_log} WHERE order_id = :id ORDER BY order_log_id DESC', [':id' => $uc_order->id()]);

    $header = array($this->t('Time'), $this->t('User'), $this->t('Changes'));
    $rows = array();
    foreach ($result as $change) {
      $rows[] = array(
        \Drupal::service('date.formatter')->format($change->created, 'short'),
        array('data' => array('#theme' => 'username', '#account' => User::load($change->uid))),
        array('data' => array('#markup' => $change->changes)),
      );
    }

    $build['log'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No changes have been logged for this order.'),
    );

    return $build;
  }

  /**
   * The title callback for order view routes.
   *
   * @param \Drupal\uc_order\OrderInterface $uc_order
   *   The order that is being viewed.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(OrderInterface $uc_order) {
    return $uc_order->label();
  }

}
