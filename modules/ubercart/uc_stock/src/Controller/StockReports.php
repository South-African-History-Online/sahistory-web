<?php

namespace Drupal\uc_stock\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\uc_report\Controller\Reports;

/**
 * Displays a stock report for products with stock tracking enabled.
 */
class StockReports extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function report() {

    //$page_size = (isset($_GET['nopage'])) ? UC_REPORT_MAX_RECORDS : variable_get('uc_report_table_size', 30);
    $page_size = 30;
    $csv_rows = array();
    $rows = array();

    $header = array(
      array('data' => $this->t('SKU'), 'field' => 'sku', 'sort' => 'asc'),
      array('data' => $this->t('Product'), 'field' => 'title'),
      array('data' => $this->t('Stock'), 'field' => 'stock'),
      array('data' => $this->t('Threshold'), 'field' => 'threshold'),
      array('data' => $this->t('Operations')),
    );

    $csv_rows[] = array($this->t('SKU'), $this->t('Product'), $this->t('Stock'), $this->t('Threshold'));

    $query = db_select('uc_product_stock', 's')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header)
      ->limit($page_size)
      ->fields('s', array(
        'nid',
        'sku',
        'stock',
        'threshold',
      ));

    $query->leftJoin('node_field_data', 'n', 's.nid = n.nid');
    $query->addField('n', 'title');
    $query->condition('active', 1)
      ->condition('title', '', '<>');


    // @todo: Replace arg()
    //if (arg(4) == 'threshold') {
    //  $query->where('threshold >= stock');
    //}

    $result = $query->execute();
    foreach ($result as $stock) {
      $op = '';
      if ($this->currentUser()->hasPermission('administer product stock')) {
        $op = array(
          '#type' => 'operations',
          '#links' => array(
            'edit' => array(
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute('uc_stock.edit', ['node' => $stock->nid], ['query' => ['destination' => 'admin/store/reports/stock']]),
            )
          ),
        );
      }

      // Add the data to a table row for display.
      $rows[] = array(
        'data' => array(
          array('data' => $stock->sku),
          array('data' => array('#type' => 'link', '#title' => $stock->title, '#url' => Url::fromRoute('entity.node.canonical', ['node' => $stock->nid]))),
          array('data' => $stock->stock),
          array('data' => $stock->threshold),
          array('data' => $op),
        ),
        'class' => array(($stock->threshold >= $stock->stock) ? 'uc-stock-below-threshold' : 'uc-stock-above-threshold'),
      );

      // Add the data to the CSV contents for export.
      $csv_rows[] = array($stock->sku, $stock->title, $stock->stock, $stock->threshold);
    }

    // Cache the CSV export.
    $controller = new Reports();
    $csv_data = $controller->store_csv('uc_stock', $csv_rows);

    $build['form'] = $this->formBuilder()->getForm('\Drupal\uc_stock\Form\StockReportForm');
    $build['report'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('width' => '100%', 'class' => array('uc-stock-table')),
    );
    $build['pager'] = array(
      '#type' => 'pager',
    );

    $build['links'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('uc-reports-links')),
    );
    $build['links']['export_csv'] = array(
      '#type' => 'link',
      '#title' => $this->t('Export to CSV file'),
      '#url' => Url::fromRoute('uc_report.getcsv', ['report_id' => $csv_data['report'], 'user_id' => $csv_data['user']]),
      '#suffix' => '&nbsp;&nbsp;&nbsp;',
    );

//    if (isset($_GET['nopage'])) {
//      $build['links']['toggle_pager'] = array(
//        '#type' => 'link',
//        '#title' => $this->t('Show paged records'),
//        '#url' => Url::fromRoute('uc_stock.reports'),
//      );
//    }
//    else {
      $build['links']['toggle_pager'] = array(
        '#type' => 'link',
        '#title' => $this->t('Show all records'),
        '#url' => Url::fromRoute('uc_stock.reports', [], ['query' => ['nopage' => '1']]),
      );
//    }

    return $build;
  }
}
