<?php

namespace Drupal\uc_order\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for uc_orders.
 */
class OrderRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();
    $route = (new Route('/admin/store/orders/{uc_order}'))
      ->addDefaults([
        '_entity_view' => 'uc_order.view',
        '_title_callback' => '\Drupal\uc_order\Controller\OrderController::pageTitle',
      ])
      ->setRequirement('uc_order', '\d+')
      ->setRequirement('_permission', 'view all orders');
    $route_collection->add('entity.uc_order.canonical', $route);

    $route = (new Route('/admin/store/orders/{uc_order}/delete'))
      ->addDefaults([
        '_entity_form' => 'uc_order.delete',
        '_title' => 'Delete',
      ])
      ->setRequirement('uc_order', '\d+')
      ->setRequirement('_entity_access', 'uc_order.delete');
    $route_collection->add('entity.uc_order.delete_form', $route);

    $route = (new Route('/admin/store/orders/{uc_order}/edit'))
      ->setDefaults([
        '_entity_form' => 'uc_order.edit',
        '_title_callback' => '\Drupal\uc_order\Controller\OrderController::pageTitle',
      ])
      ->setRequirement('_permission', 'edit orders')
      ->setRequirement('uc_order', '\d+');
    $route_collection->add('entity.uc_order.edit_form', $route);

    return $route_collection;
  }

}
