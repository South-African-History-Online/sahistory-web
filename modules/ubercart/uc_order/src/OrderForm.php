<?php

namespace Drupal\uc_order;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\Plugin\OrderPaneManager;
use Drupal\uc_store\AjaxAttachTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the Ubercart order form.
 */
class OrderForm extends ContentEntityForm {

  use AjaxAttachTrait;

  /**
   * The order pane plugin manager.
   *
   * @var \Drupal\uc_order\Plugin\OrderPaneManager
   */
  protected $orderPaneManager;

  /**
   * Constructs the order edit form.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\uc_order\Plugin\OrderPaneManager $order_pane_manager
   *   The order pane plugin manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, OrderPaneManager $order_pane_manager) {
    parent::__construct($entity_manager);
    $this->orderPaneManager = $order_pane_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.uc_order.order_pane')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\uc_order\OrderInterface $order */
    $order = $this->entity;

    $form['#order'] = $order;

    $form['order_modified'] = array(
      '#type' => 'value',
      '#value' => $form_state->getValue('order_modified') ?: $order->getChangedTime(),
    );

    $panes = $this->orderPaneManager->getPanes();
    $components = $this->getFormDisplay($form_state)->getComponents();
    foreach ($panes as $id => $pane) {
      if ($pane instanceof EditableOrderPanePluginInterface) {
        $form[$id] = $pane->buildForm($order, array(), $form_state);

        $form[$id]['#prefix'] = '<div class="order-pane ' . implode(' ', $pane->getClasses()) . '" id="order-pane-' . $id . '">';
        if ($title = $pane->getTitle()) {
          $form[$id]['#prefix'] .= '<div class="order-pane-title">' . $title . ':' . '</div>';
        }
        $form[$id]['#suffix'] = '</div>';
        $form[$id]['#weight'] = $components[$id]['weight'];
      }
    }

    $form = parent::form($form, $form_state);

    $form['#process'][] = array($this, 'ajaxProcessForm');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $element['submit']['#value'] = $this->t('Save changes');
    $element['delete']['#access'] = $this->entity->access('delete');
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    $order = $this->buildEntity($form, $form_state);

    if ($form_state->getValue('order_modified') != $order->getChangedTime()) {
      $form_state->setErrorByName('order_modified', $this->t('This order has been modified by another user, changes cannot be saved.'));
    }

    parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\uc_order\OrderInterface $order */
    $order = $this->entity;
    $original = clone $order;

    // Build list of changes to be applied.
    $panes = $this->orderPaneManager->getPanes();
    foreach ($panes as $pane) {
      if ($pane instanceof EditableOrderPanePluginInterface) {
        $pane->submitForm($order, $form, $form_state);
      }
    }

    $log = array();

    foreach (array_keys($order->getFieldDefinitions()) as $key) {
      if ($order->$key->value != $original->$key->value) {
        if (!is_array($order->$key->value)) {
          $log[$key] = array('old' => $original->$key->value, 'new' => $order->$key->value);
        }
      }
    }

    // Load line items again, since some may have been updated by the form.
    $order->line_items = $order->getLineItems();

    $order->logChanges($log);

    $order->save();

    drupal_set_message($this->t('Order changes saved.'));
  }

}
