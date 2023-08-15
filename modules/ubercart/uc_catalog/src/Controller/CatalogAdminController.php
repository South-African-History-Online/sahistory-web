<?php

namespace Drupal\uc_catalog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\field\Entity\FieldStorageConfig;

/**
 *
 */
class CatalogAdminController extends ControllerBase {

  /**
   * Repairs the catalog taxonomy field if it is lost or deleted.
   */
  public function repairField() {
    foreach (uc_product_types() as $type) {
      uc_catalog_add_node_type($type);
    }
    uc_catalog_add_image_field();

    drupal_set_message($this->t('The catalog taxonomy reference field has been repaired.'));

    return $this->redirect('uc_store.admin');
  }

  /**
   * Displays links to all products that have not been categorized.
   *
   * @return
   *   Renderable form array.
   */
  public function orphans() {
    $build = array();

    if ($this->config('taxonomy.settings')->get('maintain_index_table')) {
      $vid = $this->config('uc_catalog.settings')->get('vocabulary');
      $product_types = uc_product_types();
      $field = FieldStorageConfig::loadByName('node', 'taxonomy_catalog');

      //@todo - figure this out
      // $field is a config object, not an array, so this doesn't work.
      //$types = array_intersect($product_types, $field['bundles']['node']);
      $types = $product_types; //temporary to get this to work at all

      $result = db_query('SELECT DISTINCT n.nid, n.title FROM {node_field_data} n LEFT JOIN (SELECT ti.nid, td.vid FROM {taxonomy_index} ti LEFT JOIN {taxonomy_term_data} td ON ti.tid = td.tid WHERE td.vid = :vid) txnome ON n.nid = txnome.nid WHERE n.type IN (:types[]) AND txnome.vid IS NULL', [':vid' => $vid, ':types[]' => $types]);

      $rows = array();
      while ($node = $result->fetchObject()) {
        $rows[] = Link::createFromRoute($node->title, 'entity.node.edit_form', ['node' => $node->nid], ['query' => ['destination' => 'admin/store/products/orphans']])->toString();
      }

      if (count($rows) > 0) {
        $build['orphans'] = array(
          '#theme' => 'item_list',
          '#items' => $rows,
        );
      }
      else {
        $build['orphans'] = array(
          '#markup' => $this->t('All products are currently listed in the catalog.'),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        );
      }
    }
    else {
      $build['orphans'] = array(
        '#markup' => $this->t('The node terms index is not being maintained, so Ubercart can not determine which products are not entered into the catalog.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );
    }

    return $build;
  }
}
