<?php

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\EditableOrderPanePluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * View and modify an order's line items.
 *
 * @UbercartOrderPane(
 *   id = "line_items",
 *   title = @Translation("Line items"),
 *   weight = 6,
 * )
 */
class LineItems extends EditableOrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    $rows = array();
    foreach ($order->getDisplayLineItems() as $item) {
      $rows[] = array(
        'data' => array(
          // Title column.
          array(
            'data' => array('#markup' => $item['title']),
            'class' => array('li-title'),
          ),
          // Amount column.
          array(
            'data' => array('#theme' => 'uc_price', '#price' => $item['amount']),
            'class' => array('li-amount'),
          ),
        ),
      );
    }

    $build['line_items'] = array(
      '#type' => 'table',
      '#rows' => $rows,
      '#attributes' => array('class' => array('line-item-table')),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $options = array();
    $line_item_manager = \Drupal::service('plugin.manager.uc_order.line_item');
    $definitions = $line_item_manager->getDefinitions();
    foreach ($definitions as $item) {
      if ($item['add_list']) {
        $options[$item['id']] = $item['title'];
      }
    }

    $form['add_line_item'] = array('#type' => 'container');

    $form['add_line_item']['li_type_select'] = array(
      '#type' => 'select',
      '#title' => $this->t('Add a line item'),
      '#options' => $options,
    );
    $form['add_line_item']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add line'),
      '#submit' => array(array($this, 'submitForm'), array($this, 'addLineItem')),
      '#ajax' => array(
        'callback' => array($this, 'ajaxCallback'),
      ),
    );
    $form['line_items'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#attributes' => array('class' => array('line-item-table')),
      '#prefix' => '<div id="order-line-items">',
      '#suffix' => '</div>',
    );

    foreach ($order->getDisplayLineItems() as $item) {
      $id = $item['line_item_id'];
      $form['line_items'][$id]['li_id'] = array(
        '#type' => 'hidden',
        '#value' => $id,
      );
      if (!empty($definitions[$item['type']]['stored'])) {
        $form['line_items'][$id]['remove'] = array(
          '#type' => 'image_button',
          '#title' => $this->t('Remove line item.'),
          '#src' => drupal_get_path('module', 'uc_store') . '/images/error.gif',
          '#button_type' => 'remove',
          '#submit' => array(array($this, 'submitForm'), array($this, 'removeLineItem')),
          '#ajax' => array(
            'callback' => array($this, 'ajaxCallback'),
          ),
          '#return_value' => $id,
        );
        $form['line_items'][$id]['title'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Title'),
          '#title_display' => 'invisible',
          '#default_value' => $item['title'],
          '#size' => 40,
          '#maxlength' => 128,
        );
        $form['line_items'][$id]['amount'] = array(
          '#type' => 'uc_price',
          '#title' => $this->t('Amount'),
          '#title_display' => 'invisible',
          '#default_value' => $item['amount'],
          '#size' => 6,
          '#allow_negative' => TRUE,
          '#wrapper_attributes' => array('class' => array('li-amount')),
        );
      }
      else {
        $form['line_items'][$id]['remove'] = array(
          '#markup' => '',
        );
        $form['line_items'][$id]['title'] = array(
          '#plain_text' => $item['title'],
        );
        $form['line_items'][$id]['amount'] = array(
          '#theme' => 'uc_price',
          '#price' => $item['amount'],
          '#wrapper_attributes' => array('class' => array('li-amount')),
        );
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(OrderInterface $order, array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (is_array($values['line_items'])) {
      foreach ($values['line_items'] as $line) {
        if (is_numeric($line['li_id']) && intval($line['li_id']) > 0 && isset($line['title']) && isset($line['amount'])) {
          uc_order_update_line_item($line['li_id'], $line['title'], $line['amount']);
        }
      }
    }
  }

  /**
   * Order pane submit callback: Add a line item to an order.
   */
  public function addLineItem($form, FormStateInterface $form_state) {
    $order = &$form_state->get('order');
    $type = $form_state->getValue('li_type_select');

    $line_item_manager = \Drupal::service('plugin.manager.uc_order.line_item');
    uc_order_line_item_add($order->id(), $type, $line_item_manager->getDefinition($type)['title'], 0);
    $order->line_items = $order->getLineItems();

    $form_state->setRebuild();
  }

  /**
   * Order pane submit callback: Remove a line item from an order.
   */
  public function removeLineItem($form, FormStateInterface $form_state) {
    $order = &$form_state->get('order');
    $triggering_element = $form_state->getTriggeringElement();
    $line_item_id = intval($triggering_element['#return_value']);

    uc_order_delete_line_item($line_item_id);
    $order->line_items = $order->getLineItems();

    $form_state->setRebuild();
  }

  /**
   * AJAX callback to render the line items.
   */
  public function ajaxCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#order-line-items', trim(drupal_render($form['line_items']))));
    $status_messages = array('#type' => 'status_messages');
    $response->addCommand(new PrependCommand('#order-line-items', drupal_render($status_messages)));

    return $response;
  }

}
