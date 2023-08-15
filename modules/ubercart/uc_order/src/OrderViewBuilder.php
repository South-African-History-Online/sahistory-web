<?php

namespace Drupal\uc_order;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\uc_order\Plugin\OrderPaneManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View builder for orders.
 */
class OrderViewBuilder extends EntityViewBuilder {

  /**
   * The order pane manager.
   *
   * @var \Drupal\uc_order\Plugin\OrderPaneManager
   */
  protected $orderPaneManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, OrderPaneManager $order_pane_manager) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->orderPaneManager = $order_pane_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('plugin.manager.uc_order.order_pane')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // For now, the entity has no template itself.
    unset($build['#theme']);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    $panes = $this->orderPaneManager->getPanes();
    $components = $displays['uc_order']->getComponents();
    foreach ($entities as $id => $order) {
      foreach ($panes as $pane_id => $pane) {
        // Skip panes that are hidden in "Manage display".
        if (!isset($components[$pane_id])) {
          continue;
        }

        if ($contents = $pane->view($order, $view_mode)) {
          $build[$id][$pane_id] = array(
            '#type' => 'container',
            '#attributes' => array(
              'id' => 'order-pane-' . $pane_id,
              'class' => array_merge(array('order-pane'), $pane->getClasses()),
            ),
          );

          if ($title = $pane->getTitle()) {
            $build[$id][$pane_id]['title'] = array(
              '#type' => 'container',
              '#markup' => $title . ':',
              '#attributes' => array(
                'class' => array('order-pane-title'),
              ),
            );
          }

          $build[$id][$pane_id]['pane'] = $contents;
        }
      }
    }
  }
}
