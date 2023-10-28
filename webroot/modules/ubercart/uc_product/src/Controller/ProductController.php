<?php

namespace Drupal\uc_product\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller routines for product routes.
 */
class ProductController extends ControllerBase {

  /**
   * Displays a list of product classes.
   */
  public function classOverview() {
    $classes = $this->entityTypeManager()->getStorage('node_type')->loadByProperties(array(
      'third_party_settings.uc_product.product' => TRUE,
    ));
    $header = array($this->t('Class ID'), $this->t('Name'), $this->t('Description'), $this->t('Operations'));
    $rows = [];
    foreach ($classes as $class) {
      $links = [];
      $links['edit'] = array(
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.node_type.edit_form', ['node_type' =>$class->id()]),
        'query' => array(
          'destination' => 'admin/store/products/classes',
        ),
      );
      if (!$class->isLocked()) {
        $links['delete'] = array(
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('entity.node_type.delete_form', ['node_type' => $class->id()]),
          'query' => array(
            'destination' => 'admin/store/products/classes',
          ),
        );
      }
      $rows[] = array(
        $class->id(),
        $class->label(),
        array('data' => array('#markup' => $class->getDescription())),
        array(
          'data' => array(
            '#type' => 'operations',
            '#links' => $links,
          )
        ),
      );
    }

    return array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No product classes have been defined yet.'),
    );
  }

  /**
   * Sets up the default image field for products.
   */
  public function setImageDefaults() {
    uc_product_add_default_image_field();

    drupal_set_message($this->t('Default image support configured for Ubercart products.'));

    return $this->redirect('uc_store.admin');
  }

}
