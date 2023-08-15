<?php

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\uc_order\EditableOrderPanePluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * View the admin comments, used for administrative notes and instructions.
 *
 * @UbercartOrderPane(
 *   id = "admin_comments",
 *   title = @Translation("Admin comments"),
 *   weight = 9,
 * )
 */
class AdminComments extends EditableOrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    if ($view_mode != 'customer') {
      $build = array(
        '#theme' => 'table',
        '#header' => array(
          array('data' => $this->t('Date'), 'class' => array('date')),
          array('data' => $this->t('User'), 'class' => array('user')),
          array('data' => $this->t('Comment'), 'class' => array('message')),
        ),
        '#rows' => array(),
        '#attributes' => array('class' => array('order-pane-table uc-order-comments')),
        '#empty' => $this->t('This order has no admin comments associated with it.'),
      );
      $comments = uc_order_comments_load($order->id(), TRUE);
      foreach ($comments as $comment) {
        $build['#rows'][] = array(
          array('data' => \Drupal::service('date.formatter')->format($comment->created, 'short'), 'class' => array('date')),
          array('data' => array('#theme' => 'username', '#account' => User::load($comment->uid)), 'class' => array('user')),
          array('data' => array('#markup' => $comment->message), 'class' => array('message')),
        );
      }
      return $build;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $items = array();
    $comments = uc_order_comments_load($order->id(), TRUE);
    foreach ($comments as $comment) {
      $items[] = [
        'username' => [
          '#theme' => 'username',
          '#account' => User::load($comment->uid),
          '#prefix' => '[',
          '#suffix' => '] ',
        ],
        'message' => [
          '#markup' => $comment->message,
        ]
      ];
    }
    $form['comments'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
      '#empty' => $this->t('No admin comments have been entered for this order.'),
    );

    $form['admin_comment_field'] = array(
      '#type' => 'details',
      '#title' => $this->t('Add an admin comment'),
    );
    $form['admin_comment_field']['admin_comment'] = array(
      '#type' => 'textarea',
      '#description' => $this->t('Admin comments are only seen by store administrators.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(OrderInterface $order, array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('admin_comment')) {
      $uid = \Drupal::currentUser()->id();
      uc_order_comment_save($form_state->getValue('order_id'), $uid, $form_state->getValue('admin_comment'));
    }
  }

}
