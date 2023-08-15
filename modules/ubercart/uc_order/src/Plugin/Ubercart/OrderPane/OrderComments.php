<?php

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\user\Entity\User;
use Drupal\uc_order\Entity\OrderStatus;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\OrderPanePluginBase;

/**
 * View the order comments, used for communicating with customers.
 *
 * @UbercartOrderPane(
 *   id = "order_comments",
 *   title = @Translation("Order comments"),
 *   weight = 8,
 * )
 */
class OrderComments extends OrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    // @todo Simplify this or replace with Views
    if ($view_mode == 'customer') {
      $comments = uc_order_comments_load($order->id());
      $statuses = OrderStatus::loadMultiple();
      $header = array(
        array('data' => $this->t('Date'), 'class' => array('date')),
        array('data' => $this->t('Status'), 'class' => array('status')),
        array('data' => $this->t('Message'), 'class' => array('message')),
      );
      $rows[] = array(
        array('data' => \Drupal::service('date.formatter')->format($order->created->value, 'short'), 'class' => array('date')),
        array('data' => '-', 'class' => array('status')),
        array('data' => $this->t('Order created.'), 'class' => array('message')),
      );
      if (count($comments) > 0) {
        foreach ($comments as $comment) {
          $rows[] = array(
            array('data' => \Drupal::service('date.formatter')->format($comment->created, 'short'), 'class' => array('date')),
            array('data' => array('#plain_text' => $statuses[$comment->order_status]->getName()), 'class' => array('status')),
            array('data' => array('#markup' => $comment->message), 'class' => array('message')),
          );
        }
      }
      $build = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => array('class' => array('uc-order-comments')),
      );
    }
    else {
      $build = array(
        '#theme' => 'table',
        '#header' => array(
          array('data' => $this->t('Date'), 'class' => array('date')),
          array('data' => $this->t('User'), 'class' => array('user', RESPONSIVE_PRIORITY_LOW)),
          array('data' => $this->t('Notified'), 'class' => array('notified')),
          array('data' => $this->t('Status'), 'class' => array('status', RESPONSIVE_PRIORITY_LOW)),
          array('data' => $this->t('Comment'), 'class' => array('message')),
        ),
        '#rows' => array(),
        '#attributes' => array('class' => array('order-pane-table uc-order-comments')),
        '#empty' => $this->t('This order has no comments associated with it.'),
      );
      $comments = uc_order_comments_load($order->id());
      $statuses = OrderStatus::loadMultiple();
      foreach ($comments as $comment) {
        $icon = $comment->notified ? 'true-icon.gif' : 'false-icon.gif';
        $build['#rows'][] = array(
          array('data' => \Drupal::service('date.formatter')->format($comment->created, 'short'), 'class' => array('date')),
          array('data' => array('#theme' => 'username', '#account' => User::load($comment->uid)), 'class' => array('user')),
          array('data' => array('#theme' => 'image', '#uri' => drupal_get_path('module', 'uc_order') . '/images/' . $icon), 'class' => array('notified')),
          array('data' => array('#plain_text' => $statuses[$comment->order_status]->getName()), 'class' => array('status')),
          array('data' => array('#markup' => $comment->message), 'class' => array('message')),
        );
      }
    }

    return $build;
  }

}
