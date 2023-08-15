<?php

namespace Drupal\uc_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The maximum number of records.
 */
define('UC_REPORT_MAX_RECORDS', PHP_INT_MAX);


class Reports extends ControllerBase {

  /**
   * Displays the customer report.
   */
  public function customers() {
    $address_preference = $this->config('uc_store.settings')->get('customer_address');
    $first_name = ($address_preference == 'billing') ? 'billing_first_name' : 'delivery_first_name';
    $last_name = ($address_preference == 'billing') ? 'billing_last_name' : 'delivery_last_name';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
    $page_size = isset($_GET['nopage']) ? UC_REPORT_MAX_RECORDS : $this->config('uc_report.settings')->get('table_size');
    $order_statuses = uc_report_order_statuses();
    $rows = array();
    $csv_rows = array();

    $header = array(
      array('data' => $this->t('#')),
      array('data' => $this->t('Customer'), 'field' => "ou.$last_name"),
      array('data' => $this->t('Username'), 'field' => "u.name"),
      array('data' => $this->t('Orders'), 'field' => 'orders'),
      array('data' => $this->t('Products'), 'field' => 'products'),
      array('data' => $this->t('Total'), 'field' => 'total', 'sort' => 'desc'),
      array('data' => $this->t('Average'), 'field' => 'average'),
    );
    $csv_rows[] = array($this->t('#'), $this->t('Customer'), $this->t('Username'), $this->t('Orders'), $this->t('Products'), $this->t('Total'), $this->t('Average'));

    $query = db_select('users_field_data', 'u', ['fetch' => \PDO::FETCH_ASSOC])
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->leftJoin('uc_orders', 'ou', 'u.uid = ou.uid');
    $query->fields('u', array(
        'uid',
        'name',
      ))
      ->fields('ou', array(
        $first_name,
        $last_name,
      ))
      ->condition('u.uid', 0, '>')
      ->groupBy('u.uid');
    $query->addExpression("(SELECT COUNT(DISTINCT(order_id)) FROM {uc_orders} o WHERE o.uid = u.uid AND o.order_status IN (:statuses[]))", 'orders', [':statuses[]' => $order_statuses]);
    $query->addExpression("(SELECT SUM(qty) FROM {uc_order_products} ps LEFT JOIN {uc_orders} os ON ps.order_id = os.order_id WHERE os.order_status IN (:statuses2[]) AND os.uid = u.uid)", 'products', [':statuses2[]' => $order_statuses]);
    $query->addExpression("(SELECT SUM(ot.order_total) FROM {uc_orders} ot WHERE ot.uid = u.uid AND ot.order_status IN (:statuses3[]))", 'total', [':statuses3[]' => $order_statuses]);
    $query->addExpression("ROUND((SELECT SUM(ot.order_total) FROM {uc_orders} ot WHERE ot.uid = u.uid AND ot.order_status IN (:sum_statuses[]))/(SELECT COUNT(DISTINCT(order_id)) FROM {uc_orders} o WHERE o.uid = u.uid AND o.order_status IN (:count_statuses[])), 2)", 'average', [':sum_statuses[]' => $order_statuses, ':count_statuses[]' => $order_statuses]);

    $count_query = db_select('users_field_data', 'u');
    $count_query->leftJoin('uc_orders', 'ou', 'u.uid = ou.uid');
    $count_query->addExpression('COUNT(DISTINCT u.uid)');
    $count_query->condition('u.uid', 0, '>');

    $query->setCountQuery($count_query);
    $query->groupBy('u.uid')
      ->groupBy('u.name')
      ->groupBy("ou.$first_name")
      ->groupBy("ou.$last_name")
      ->orderByHeader($header)
      ->limit($page_size);

    $customers = $query->execute();

    foreach ($customers as $customer) {
      $name = (!empty($customer[$last_name]) || !empty($customer[$first_name])) ? Link::fromTextAndUrl($customer[$last_name] . ', ' . $customer[$first_name], Url::fromUri('base:admin/store/customers/orders/' . $customer['uid']))->toString() : Link::fromTextAndUrl($customer['name'], Url::fromUri('base:admin/store/customers/orders/' . $customer['uid']))->toString();
      $customer_number = ($page * $this->config('uc_report.settings')->get('table_size')) + (count($rows) + 1);
      $customer_order_name = (!empty($customer[$last_name]) || !empty($customer[$first_name])) ? $customer[$last_name] . ', ' . $customer[$first_name] : $customer['name'];
      $customer_name = $customer['name'];
      $orders = !empty($customer['orders']) ? $customer['orders'] : 0;
      $products = !empty($customer['products']) ? $customer['products'] : 0;
      $total_revenue = uc_currency_format($customer['total']);
      $average_revenue = uc_currency_format($customer['average']);
      $rows[] = array(
        array('data' => $customer_number),
        array('data' => $name),
        array('data' => Link::createFromRoute($customer_name, 'entity.user.canonical', ['user' => $customer['uid']])->toString()),
        array('data' => $orders),
        array('data' => $products),
        array('data' => $total_revenue),
        array('data' => $average_revenue),
      );
      $csv_rows[] = array($customer_number, $customer_order_name, $customer_name, $orders, $products, $customer['total'], $customer['average']);
    }
    $csv_data = $this->store_csv('uc_customers', $csv_rows);

    $build['report'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('width' => '100%', 'class' => array('uc-sales-table')),
      '#empty' => $this->t('No customers found'),
    );
    $build['pager'] = array(
      '#type' => 'pager',
    );
    $build['links'] = array(
      '#prefix' => '<div class="uc-reports-links">',
      '#suffix' => '</div>',
    );
    $build['links']['export_csv'] = array(
      '#markup' => Link::createFromRoute($this->t('Export to CSV file.'), 'uc_report.getcsv', ['report_id' => $csv_data['report'], 'user_id' => $csv_data['user']])->toString(),
      '#suffix' => '&nbsp;&nbsp;&nbsp;',
    );
    if (isset($_GET['nopage'])) {
      $build['links']['toggle_pager'] = array(
        '#markup' => Link::createFromRoute($this->t('Show paged records'), 'uc_report.customers')->toString(),
      );
    }
    else {
      $build['links']['toggle_pager'] = array(
        '#markup' => Link::createFromRoute($this->t('Show all records'), 'uc_report.customers', ['query' => ['nopage' => '1']])->toString(),
      );
    }

    return $build;
  }

  /**
   * Displays the product reports.
   */
  public function products() {
    $views_column = $this->moduleHandler()->moduleExists('statistics') && $this->config('statistics.settings')->get('count_content_views');

    $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
    $page_size = isset($_GET['nopage']) ? UC_REPORT_MAX_RECORDS : $this->config('uc_report.settings')->get('table_size');
    $order_statuses = uc_report_order_statuses();
    $row_cell = $page * $this->config('uc_report.settings')->get('table_size') + 1;
    $rows = array();
    $csv_rows = array();

    // Hard code the ignore of the product kit for this report.
    $ignored_types = array('product_kit');

    // Build an array of valid product types to include on the report.
    $product_types = array();
    foreach (uc_product_types() as $type) {
      // Pass over any ignored types.
      if (!in_array($type, $ignored_types)) {
        $product_types[] = $type;
      }
    }

    $query = db_select('node_field_data', 'n', ['fetch' => \PDO::FETCH_ASSOC])
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->limit($page_size);

    $query->addField('n', 'nid');
    $query->addField('n', 'title');
    $query->addExpression("(SELECT SUM(uop.qty) FROM {uc_order_products} uop LEFT JOIN {uc_orders} uo ON uop.order_id = uo.order_id WHERE uo.order_status IN (:statuses[]) AND uop.nid = n.nid)", 'sold', [':statuses[]' => $order_statuses]);
    $query->addExpression("(SELECT (SUM(uop.price * uop.qty) - SUM(uop.cost * uop.qty)) FROM {uc_order_products} uop LEFT JOIN {uc_orders} uo ON uop.order_id = uo.order_id WHERE uo.order_status IN (:statuses2[]) AND uop.nid = n.nid)", 'gross', [':statuses2[]' => $order_statuses]);
    $query->addExpression("(SELECT (SUM(uop.price * uop.qty)) FROM {uc_order_products} uop LEFT JOIN {uc_orders} uo ON uop.order_id = uo.order_id WHERE uo.order_status IN (:statuses3[]) AND uop.nid = n.nid)", 'revenue', [':statuses3[]' => $order_statuses]);

    $header = array(
      array('data' => $this->t('#')),
      array('data' => $this->t('Product'), 'field' => 'n.title'),
      array('data' => $this->t('Sold'), 'field' => 'sold'),
      array('data' => $this->t('Revenue'), 'field' => 'revenue', 'sort' => 'desc'),
      array('data' => $this->t('Gross'), 'field' => 'gross'),
    );
    $csv_rows[] = array($this->t('#'), $this->t('Product'), $this->t('Sold'), $this->t('Revenue'), $this->t('Gross'));

    if ($views_column) {
      $header[] = array('data' => $this->t('Views'), 'field' => 'nc.totalcount');
      $csv_rows[0][] = $this->t('Views');
    }

    $query->orderByHeader($header);

    if ($views_column) {
      $query->leftJoin('node_counter', 'nc', 'n.nid = nc.nid');
      $query->addField('nc', 'totalcount');
    }

    $query->condition('n.type', $product_types, 'IN')
      ->groupBy('n.nid')
      ->groupBy('n.title');

    $products = $query->execute();
    foreach ($products as $product) {
      $product_cell = Link::createFromRoute($product['title'], 'entity.node.canonical', ['node' => $product['nid']])->toString();
      $product_csv = $product['title'];
      $sold_cell = empty($product['sold']) ? 0 : $product['sold'];
      $sold_csv = $sold_cell;
      $revenue_csv = empty($product['revenue']) ? 0 : $product['revenue'];
      $revenue_cell = uc_currency_format($revenue_csv);
      $gross_csv = empty($product['gross']) ? 0 : $product['gross'];
      $gross_cell = uc_currency_format($gross_csv);

      $row = array(
        'data' => array(
          $row_cell,
          $product_cell,
          array('data' => array('#prefix' => '<strong>', '#markup' => $sold_cell, '#suffix' => '</strong>')),
          array('data' => array('#prefix' => '<strong>', '#markup' => $revenue_cell, '#suffix' => '</strong>')),
          array('data' => array('#prefix' => '<strong>', '#markup' => $gross_cell, '#suffix' => '</strong>')),
        ),
        'primary' => TRUE,
      );
      $csv_row = array($row_cell, $product_csv, $sold_csv, $revenue_csv, $gross_csv);

      if ($views_column) {
        $views = isset($product['totalcount']) ? $product['totalcount'] : 0;
        $row['data'][] = $views;
        $csv_row[] = $views;
      }

      $rows[] = $row;
      $csv_rows[] = $csv_row;

      if ($this->moduleHandler()->moduleExists('uc_attribute')) {
        // Get the SKUs from this product.
        $models = $this->product_get_skus($product['nid']);
        // Add the product breakdown rows
        foreach ($models as $model) {
          $sold = db_query("SELECT SUM(qty) FROM {uc_order_products} p LEFT JOIN {uc_orders} o ON p.order_id = o.order_id WHERE o.order_status IN (:statuses[]) AND p.model = :model AND p.nid = :nid", [':statuses[]' => $order_statuses, ':model' => $model, ':nid' => $product['nid']])->fetchField();
          $revenue = db_query("SELECT SUM(p.price * p.qty) FROM {uc_order_products} p LEFT JOIN {uc_orders} o ON p.order_id = o.order_id WHERE o.order_status IN (:statuses[]) AND p.model = :model AND p.nid = :nid", [':statuses[]' => $order_statuses, ':model' => $model, ':nid' => $product['nid']])->fetchField();
          $gross = db_query("SELECT (SUM(p.price * p.qty) - SUM(p.cost * p.qty)) FROM {uc_order_products} p LEFT JOIN {uc_orders} o ON p.order_id = o.order_id WHERE o.order_status IN (:statuses[]) AND p.model = :model AND p.nid = :nid", [':statuses[]' => $order_statuses, ':model' => $model, ':nid' => $product['nid']])->fetchField();
          $breakdown_product = array('#markup' => "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$model");
          $product_csv = "     $model";

          $sold_csv = !empty($sold) ? $sold : 0;
          $breakdown_sold = $sold_csv;

          $revenue_csv = !empty($revenue) ? $revenue : 0;
          $breakdown_revenue = array('#theme' => 'uc_price', '#price' => $revenue_csv);

          $gross_csv = !empty($gross) ? $gross : 0;
          $breakdown_gross = array('#theme' => 'uc_price', '#price' => $gross_csv);

          $row = array(
            'data' => array(
              '',
              array('data' => $breakdown_product),
              $breakdown_sold,
              array('data' => $breakdown_revenue),
              array('data' => $breakdown_gross),
            ),
          );
          $csv_row = array('', $product_csv, $sold_csv, $revenue_csv, $gross_csv);

          if ($views_column) {
            $row['data'][] = '';
            $csv_row[] = '';
          }

          $rows[] = $row;
          $csv_rows[] = $csv_row;

        }
      }
      $row_cell++;
    }
    $csv_data = $this->store_csv('uc_products', $csv_rows);

    $build['report'] = array(
    // theme_uc_report_product_table stripes the rows differently than theme_table.
    // We want all of a product's SKUs to show up in separate rows, but they should all
    // be adjacent and grouped with each other visually by using the same striping for
    // each product SKU (all odd or all even).
    //  '#theme' => 'uc_report_product_table',
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('width' => '100%', 'class' => array('uc-sales-table')),
      '#empty' => $this->t('No products found'),
    );
    $build['pager'] = array(
      '#type' => 'pager',
    );

    $build['links'] = array(
      '#prefix' => '<div class="uc-reports-links">',
      '#suffix' => '</div>',
    );
    $build['links']['export_csv'] = array(
      '#markup' => Link::createFromRoute($this->t('Export to CSV file.'), 'uc_report.getcsv', ['report_id' => $csv_data['report'], 'user_id' => $csv_data['user']])->toString(),
      '#suffix' => '&nbsp;&nbsp;&nbsp;',
    );
    if (isset($_GET['nopage'])) {
      $build['links']['toggle_pager'] = array(
        '#markup' => Link::createFromRoute($this->t('Show paged records'), 'uc_report.products')->toString(),
      );
    }
    else {
      $build['links']['toggle_pager'] = array(
        '#markup' => Link::createFromRoute($this->t('Show all records'), 'uc_report.products', ['query' => ['nopage' => '1']])->toString(),
      );
    }
    $build['instructions'] = array('#markup' => '<small>*' . $this->t('Make sure %setting_name is set to %state in the <a href=":url">access log settings page</a> to enable views column.', ['%setting_name' => 'count content views', '%state' => 'enabled', ':url' => Url::fromUri('base:admin/config/system/statistics', ['query' => ['destination' => 'admin/store/reports/products']])->toString()]) . '</small>');

    return $build;
  }

  /**
   * Gets the SKUs on a product, including adjustments and past orders.
   *
   * @param $nid
   *   The product's node ID.
   *
   * @return
   *   A unique sorted array of all skus.
   */
  public function product_get_skus($nid) {
    // Product SKU.
    $models = array(db_query("SELECT model FROM {uc_products} WHERE nid = :nid", [':nid' => $nid])->fetchField());
    // Adjustment SKUs.
    $models = array_merge($models, db_query("SELECT model FROM {uc_product_adjustments} WHERE nid = :nid", [':nid' => $nid])->fetchCol());

    // SKUs from orders.
    $models = array_merge($models, db_query("SELECT DISTINCT model FROM {uc_order_products} WHERE nid = :nid", [':nid' => $nid])->fetchCol());

    // Unique, sorted.
    $models = array_unique($models);
    asort($models);

    return $models;
  }

  /**
   * Displays the custom product report.
   */
  public function customProducts() {
    $views_column = $this->moduleHandler()->moduleExists('statistics') && $this->config('statistics.settings')->get('count_content_views');

    $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
    $page_size = isset($_GET['nopage']) ? UC_REPORT_MAX_RECORDS : $this->config('uc_report.settings')->get('table_size');
    $rows = array();
    $csv_rows = array();

    // Hard code the ignore of the product kit for this report.
    $ignored_types = array('product_kit');

    // Build an array of valid product types to include on the report.
    $product_types = array();
    foreach (uc_product_types() as $type) {
      // Pass over any ignored types.
      if (!in_array($type, $ignored_types)) {
        $product_types[] = $type;
      }
    }

    // Use default report parameters if we don't detect values in the URL.
    if (arg(5) == '') {
      $args = array(
        'start_date' => mktime(0, 0, 0, date('n'), 1, date('Y') - 1),
        'end_date' => REQUEST_TIME,
        'status' => uc_report_order_statuses(),
      );
    }
    else {
      $args = array(
        'start_date' => arg(5),
        'end_date' => arg(6),
        'status' => explode(',', arg(7)),
      );
    }

    $query = db_select('node', 'n', ['fetch' => \PDO::FETCH_ASSOC])
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->limit($page_size)
      ->fields('n', array(
        'nid',
        'title',
      ))
      ->condition('type', $product_types, 'IN')
      ->groupBy('n.nid');

    $query->addExpression("(SELECT SUM(qty) FROM {uc_order_products} p LEFT JOIN {uc_orders} o ON p.order_id = o.order_id WHERE o.order_status IN (:statuses[]) AND p.nid = n.nid AND o.created >= :start AND o.created <= :end)", 'sold', [':statuses[]' => $args['status'], ':start' => $args['start_date'], ':end' => $args['end_date']]);
    $query->addExpression("(SELECT (SUM(p2.price * p2.qty)) FROM {uc_order_products} p2 LEFT JOIN {uc_orders} o ON p2.order_id = o.order_id WHERE o.order_status IN (:statuses[]) AND p2.nid = n.nid AND o.created >= :start AND o.created <= :end)", 'revenue', [':statuses[]' => $args['status'], ':start' => $args['start_date'], ':end' => $args['end_date']]);
    $query->addExpression("(SELECT (SUM(p3.price * p3.qty) - SUM(p3.cost * p3.qty)) FROM {uc_order_products} p3 LEFT JOIN {uc_orders} o ON p3.order_id = o.order_id WHERE o.order_status IN (:statuses[]) AND p3.nid = n.nid AND o.created >= :start AND o.created <= :end)", 'gross', [':statuses[]' => $args['status'], ':start' => $args['start_date'], ':end' => $args['end_date']]);

    $header = array(
      array('data' => $this->t('#')),
      array('data' => $this->t('Product'), 'field' => 'n.title'),
      array('data' => $this->t('Sold'), 'field' => 'sold'),
      array('data' => $this->t('Revenue'), 'field' => 'revenue', 'sort' => 'desc'),
      array('data' => $this->t('Gross'), 'field' => 'gross'),
    );
    $csv_rows[] = array($this->t('#'), $this->t('Product'), $this->t('Sold'), $this->t('Revenue'), $this->t('Gross'));

    if ($views_column) {
      $header[] = array('data' => $this->t('Views'), 'field' => 'nc.totalcount');
      $csv_rows[0][] = $this->t('Views');
    }

    $query->orderByHeader($header);

    if ($views_column) {
      $query->leftJoin('node_counter', 'c', 'n.nid = c.nid');
      $query->addField('c', 'totalcount');
    }

    $products = $query->execute();
    foreach ($products as $product) {
      $row_cell = ($page * $this->config('uc_report.settings')->get('table_size')) + count($rows) + 1;
      $product_cell = Link::createFromRoute($product['title'], 'entity.node.canonical', ['node' => $product['nid']])->toString();
      $product_csv = $product['title'];
      $sold_cell = empty($product['sold']) ? 0 : $product['sold'];
      $sold_csv = $sold_cell;
      $revenue_csv = empty($product['revenue']) ? 0 : $product['revenue'];
      $revenue_cell = uc_currency_format($revenue_csv);
      $gross_csv = empty($product['gross']) ? 0 : $product['gross'];
      $gross_cell = uc_currency_format($gross_csv);

      if ($this->moduleHandler()->moduleExists('uc_attribute')) {
        $breakdown_product = $breakdown_sold = $breakdown_revenue = $breakdown_gross = '';

        // Get the SKUs from this product.
        $models = $this->product_get_skus($product['nid']);
        // Add the product breakdown rows
        foreach ($models as $model) {
          $sold = db_query("SELECT SUM(qty) FROM {uc_order_products} p LEFT JOIN {uc_orders} o ON p.order_id = o.order_id WHERE o.order_status IN (:statuses[]) AND p.model = :model AND p.nid = :nid AND o.created >= :start AND o.created <= :end", [':statuses[]' => $args['status'], ':start' => $args['start_date'], ':end' => $args['end_date'], ':model' => $model, ':nid' => $product['nid']])->fetchField();
          $sold = empty($sold) ? 0 : $sold;
          $revenue = db_query("SELECT SUM(p.price * p.qty) FROM {uc_order_products} p LEFT JOIN {uc_orders} o ON p.order_id = o.order_id WHERE o.order_status IN (:statuses[]) AND p.model = :model AND p.nid = :nid AND o.created >= :start AND o.created <= :end", [':statuses[]' => $args['status'], ':start' => $args['start_date'], ':end' => $args['end_date'], ':model' => $model, ':nid' => $product['nid']])->fetchField();
          $revenue = empty($revenue) ? 0 : $revenue;
          $gross = db_query("SELECT (SUM(p.price * p.qty) - SUM(p.cost * p.qty)) FROM {uc_order_products} p LEFT JOIN {uc_orders} o ON p.order_id = o.order_id WHERE o.order_status IN (:statuses[]) AND p.model = :model AND p.nid = :nid AND o.created >= :start AND o.created <= :end", [':statuses[]' => $args['status'], ':start' => $args['start_date'], ':end' => $args['end_date'], ':model' => $model, ':nid' => $product['nid']])->fetchField();
          $gross = empty($gross) ? 0 : $gross;

          $breakdown_product .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$model";
          $product_csv .= "\n     $model";

          $breakdown_sold .= "<br />" . $sold;
          $sold_csv .= "\n     " . $sold;

          $breakdown_revenue .= "<br />" . uc_currency_format($revenue);
          $revenue_csv .= "\n     " . $revenue;

          $breakdown_gross .= "<br />" . uc_currency_format($gross);
          $gross_csv .= "\n     " . $gross;
        }
        $product_cell = $product_cell . $breakdown_product;
        $sold_cell = '<strong>' . $sold_cell . '</strong>' . $breakdown_sold;
        $revenue_cell = '<strong>' . $revenue_cell . '</strong>' . $breakdown_revenue;
        $gross_cell = '<strong>' . $gross_cell . '</strong>' . $breakdown_gross;
      }
      if ($views_column) {
        $views = empty($product['totalcount']) ? 0 : $product['totalcount'];
        $rows[] = array(
          array('data' => $row_cell),
          array('data' => $product_cell),
          array('data' => $sold_cell),
          array('data' => $revenue_cell),
          array('data' => $gross_cell),
          array('data' => $views),
        );
        $csv_rows[] = array($row_cell, $product_csv, $sold_csv, $revenue_csv, $gross_csv, $views);
      }
      else {
        $rows[] = array(
          array('data' => $row_cell),
          array('data' => $product_cell),
          array('data' => $sold_cell),
          array('data' => $revenue_cell),
          array('data' => $gross_cell),
        );
        $csv_rows[] = array($row_cell, $product_csv, $sold_csv, $revenue_csv, $gross_csv);
      }
    }
    $csv_data = $this->store_csv('uc_products', $csv_rows);

    // Build the page output holding the form, table, and CSV export link.
    $build['form'] = $this->formBuilder()->getForm('uc_report_products_custom_form', $args);
    $build['report'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('width' => '100%', 'class' => array('uc-sales-table')),
      '#empty' => $this->t('No products found'),
    );
    $build['pager'] = array(
      '#type' => 'pager',
    );
    $build['links'] = array(
      '#prefix' => '<div class="uc-reports-links">',
      '#suffix' => '</div>',
    );
    $build['links']['export_csv'] = array(
      '#markup' => Link::createFromRoute($this->t('Export to CSV file.'), 'uc_report.getcsv', ['report_id' => $csv_data['report'], 'user_id' => $csv_data['user']])->toString(),
      '#suffix' => '&nbsp;&nbsp;&nbsp;',
    );
    if (isset($_GET['nopage'])) {
      $build['links']['toggle_pager'] = array(
        '#markup' => Link::createFromRoute($this->t('Show paged records'), 'uc_reports.custom_report')->toString(),
      );
    }
    else {
      $build['links']['toggle_pager'] = array(
        '#markup' => Link::createFromRoute($this->t('Show all records'), 'uc_reports.custom_report', ['query' => ['nopage' => '1']])->toString(),
      );
    }

    $build['instructions'] = array('#markup' => '<small>*' . $this->t('Make sure %setting_name is set to %state in the <a href=":url">access log settings page</a> to enable views column.', ['%setting_name' => 'count content views', '%state' => 'enabled', ':url' => Url::fromUri('base:admin/config/system/statistics', ['query' => ['destination' => 'admin/store/reports/products/custom']])->toString()]) . '</small>');

    return $build;
  }

  /**
   * Displays the sales summary report.
   */
  public function sales() {
    $order_statuses = uc_report_order_statuses();

    $date_day_of_month = date('j');
    $date_month = date('n');
    $month_start = mktime(0, 0, 0, $date_month, 1);
    $month_end = mktime(0, 0, 0, $date_month + 1, 1) - 1;
    $today_start = mktime(0, 0, 0);
    $today_end = mktime(23, 59, 59);

    // Build the report table header.
    $header = array($this->t('Sales data'), $this->t('Number of orders'), $this->t('Total revenue'), $this->t('Average order'));

    // Calculate and add today's sales summary to the report table.
    $today = self::get_sales($today_start);

    $rows[] = array(
      Link::fromTextAndUrl($this->t('Today, @date', ['@date' => \Drupal::service('date.formatter')->format($today_start, 'uc_store')]), Url::fromUri('base:admin/store/orders/search/results/0/0/0/0/0/0/' . $today_start . '/' . $today_end))->toString(),
      $today['total'],
      array('data' => array('#theme' => 'uc_price', '#price' => $today['income'])),
      array('data' => array('#theme' => 'uc_price', '#price' => $today['average'])),
    );

    // Calculate and add yesterday's sales summary to the report table.
    $yesterday = self::get_sales($today_start - 86400);

    $rows[] = array(
      Link::fromTextAndUrl($this->t('Yesterday, @date', ['@date' => \Drupal::service('date.formatter')->format($today_start - 86400, 'uc_store')]), Url::fromUri('base:admin/store/orders/search/results/0/0/0/0/0/0/' . ($today_start - 86400) . '/' . ($today_end - 86400)))->toString(),
      $yesterday['total'],
      array('data' => array('#theme' => 'uc_price', '#price' => $yesterday['income'])),
      array('data' => array('#theme' => 'uc_price', '#price' => $yesterday['average'])),
    );

    // Get the sales report for the month.
    $month = self::get_sales($month_start, 'month');
    $month_title = \Drupal::service('date.formatter')->format($month_start, 'custom', 'M Y');

    // Add the month-to-date details to the report table.
    $rows[] = array(
      Link::fromTextAndUrl($this->t('Month-to-date, @month', ['@month' => $month_title]), Url::fromUri('base:admin/store/orders/search/results/0/0/0/0/0/0/' . $month_start . '/' . $month_end))->toString(),
      $month['total'],
      array('data' => array('#theme' => 'uc_price', '#price' => $month['income'])),
      array('data' => array('#theme' => 'uc_price', '#price' => $month['average'])),
    );

    // Calculate the daily averages for the month.
    $daily_orders = round($month['total'] / $date_day_of_month, 2);
    $daily_revenue = round($month['income'] / $date_day_of_month, 2);

    if ($daily_orders > 0) {
      $daily_average = round($daily_revenue / $daily_orders, 2);
    }
    else {
      $daily_average = 0;
    }

    // Add the daily averages for the month to the report table.
    $rows[] = array(
      $this->t('Daily average for @month', ['@month' => $month_title]),
      $daily_orders,
      array('data' => array('#theme' => 'uc_price', '#price' => $daily_revenue)),
      '',
    );

    // Store the number of days remaining in the month.
    $remaining_days = date('t') - $date_day_of_month;

    // Add the projected totals for the month to the report table.
    $rows[] = array(
      $this->t('Projected totals for @date', ['@date' => $month_title]),
      round($month['total'] + ($daily_orders * $remaining_days), 2),
      array('data' => array('#theme' => 'uc_price', '#price' => round($month['income'] + ($daily_revenue * $remaining_days), 2))),
      '',
    );

    // Add the sales data report table to the output.
    $build['sales'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('class' => array('uc-sales-table')),
    );

    // Build the header statistics table header.
    $header = array(array('data' => $this->t('Statistics'), 'width' => '50%'), '');

    $rows = array(
      array(array('data' => $this->t('Grand total sales')), array('data' => array('#theme' => 'uc_price', '#price' => db_query("SELECT SUM(order_total) FROM {uc_orders} WHERE order_status IN (:statuses[])", [':statuses[]' => $order_statuses])->fetchField()))),
      array(array('data' => $this->t('Customers total')), array('data' => db_query("SELECT COUNT(DISTINCT uid) FROM {uc_orders} WHERE order_status IN (:statuses[])", [':statuses[]' => $order_statuses])->fetchField())),
      array(array('data' => $this->t('New customers today')), array('data' => db_query("SELECT COUNT(DISTINCT uid) FROM {uc_orders} WHERE order_status IN (:statuses[]) AND :start <= created AND created <= :end", [':statuses[]' => $order_statuses, ':start' => $today_start, ':end' => $today_end])->fetchField())),
      array(array('data' => $this->t('Online customers')), array('data' => db_query("SELECT COUNT(DISTINCT s.uid) FROM {sessions} s LEFT JOIN {uc_orders} o ON s.uid = o.uid WHERE s.uid > 0 AND o.order_status IN (:statuses[])", [':statuses[]' => $order_statuses])->fetchField())),
    );

    // Add the statistics table to the output.
    $build['statistics'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('width' => '100%', 'class' => array('uc-sales-table')),
    );

    // Build the total orders by status table header.
    $header = array(array('data' => $this->t('Total orders by status'), 'width' => '50%'), '');
    $rows = array();
    $unknown = 0;

    // Loop through the order statuses with their total number of orders.
/*
    $result = db_query("SELECT s.order_status_id, order_status, s.title, s.weight, COUNT(o.order_status) as order_count FROM {uc_orders} o LEFT JOIN {uc_order_statuses} s ON s.order_status_id = o.order_status GROUP BY s.order_status_id, order_status, s.title, s.weight ORDER BY s.weight DESC");
    while ($status = $result->fetchAssoc()) {
      if (!empty($status['title'])) {
        // Add the total number of orders with this status to the table.
        $rows[] = array(
          Link::fromTextAndUrl($status['title'], Url::fromUri('base:admin/store/orders/view', ['query' => ['order_status' => $status['order_status_id']]]))->toString(),
          $status['order_count'],
        );
      }
      else {
        // Keep track of the count of orders with an unknown status.
        $unknown += $status['order_count'];
      }
    }
*/

    // Add the unknown status count to the table.
    if ($unknown > 0) {
      $rows[] = array(
        $this->t('Unknown status'),
        $unknown,
      );
    }

    // Add the total orders by status table to the output.
    $build['orders'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('class' => array('uc-sales-table')),
    );

    return $build;
  }

  /**
   * Displays the yearly sales report form and table.
   */
  public function yearSales() {
    // Get the year for the report from the URL.
    if (intval(arg(5)) == 0) {
      $year = date('Y');
    }
    else {
      $year = arg(5);
    }

    // Build the header for the report table.
    $header = array($this->t('Month'), $this->t('Number of orders'), $this->t('Total revenue'), $this->t('Average order'));

    // Build the header to the CSV export.
    $csv_rows = array(array($this->t('Month'), $this->t('Number of orders'), $this->t('Total revenue'), $this->t('Average order')));

    // For each month of the year...
    for ($i = 1; $i <= 12; $i++) {
      // Calculate the start and end timestamps for the month in local time.
      $month_start = mktime(0, 0, 0, $i, 1, $year);
      $month_end = mktime(23, 59, 59, $i + 1, 0, $year);

      // Get the sales report for the month.
      $month_sales = self::get_sales($month_start, 'month');

      // Calculate the average order total for the month.
      if ($month_sales['total'] != 0) {
        $month_average = round($month_sales['income'] / $month_sales['total'], 2);
      }
      else {
        $month_average = 0;
      }

      // Add the month's row to the report table.
      $rows[] = array(
        Link::fromTextAndUrl(date('M Y', $month_start), Url::fromUri('base:admin/store/orders/search/results/0/0/0/0/0/0/' . $month_start . '/' . $month_end))->toString(),
        $month_sales['total'],
        uc_currency_format($month_sales['income']),
        uc_currency_format($month_average),
      );

      // Add the data to the CSV export.
      $csv_rows[] = array(
        date('M Y', $month_start),
        $month_sales['total'],
        $month_sales['income'],
        $month_average,
      );
    }

    // Calculate the start and end timestamps for the year in local time.
    $year_start = mktime(0, 0, 0, 1, 1, $year);
    $year_end = mktime(23, 59, 59, 1, 0, $year + 1);

    // Get the sales report for the year.
    $year_sales = self::get_sales($year_start, 'year');

    // Calculate the average order total for the year.
    if ($year_sales['total'] != 0) {
      $year_average = round($year_sales['income'] / $year_sales['total'], 2);
    }
    else {
      $year_average = 0;
    }

    // Add the total row to the report table.
    $rows[] = array(
      Link::fromTextAndUrl($this->t('Total @year', ['@year' => $year]), Url::fromUri('base:admin/store/orders/search/results/0/0/0/0/0/0/' . $year_start . '/' . $year_end))->toString(),
      $year_sales['total'],
      uc_currency_format($year_sales['income']),
      uc_currency_format($year_average),
    );

    // Add the total data to the CSV export.
    $csv_rows[] = array(
      $this->t('Total @year', ['@year' => $year]),
      $year_sales['total'],
      $year_sales['income'],
      $year_average,
    );

    // Cache the CSV export.
    $csv_data = $this->store_csv('uc_sales_yearly', $csv_rows);

    // Build the page output holding the form, table, and CSV export link.
    $build['form'] = $this->formBuilder()->getForm('uc_report_sales_year_form', $year);
    $build['report'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('width' => '100%', 'class' => array('uc-sales-table')),
    );

    $build['links'] = array(
      '#prefix' => '<div class="uc-reports-links">',
      '#suffix' => '</div>',
    );
    $build['links']['export_csv'] = array(
      '#markup' => Link::createFromRoute($this->t('Export to CSV file.'), 'uc_report.getcsv', ['report_id' => $csv_data['report'], 'user_id' => $csv_data['user']])->toString(),
    );

    return $build;
  }

  /**
   * Displays the custom sales report form and table.
   */
  public function customSales() {
    // Use default report parameters if we don't detect values in the URL.
    if (arg(5) == '') {
      $args = array(
        'start_date' => mktime(0, 0, 0, date('n'), 1, date('Y') - 1),
        'end_date' => REQUEST_TIME,
        'length' => 'month',
        'status' => uc_report_order_statuses(),
        'detail' => FALSE,
      );
    }
    else {
      $args = array(
        'start_date' => arg(5),
        'end_date' => arg(6),
        'length' => arg(7),
        'status' => explode(',', arg(8)),
        'detail' => arg(9),
      );
    }

    // Build the header for the report table.
    $header = array($this->t('Date'), $this->t('Number of orders'), $this->t('Products sold'), $this->t('Total revenue'));

    // Build the header to the CSV export.
    $csv_rows = array(array($this->t('Date'), $this->t('Number of orders'), $this->t('Products sold'), $this->t('Total revenue')));

    // Grab the subreports based on the date range and the report breakdown.
    $subreports = $this->subreport_intervals($args['start_date'], $args['end_date'], $args['length']);

    // Loop through the subreports and build the report table.
    foreach ($subreports as $subreport) {
      $product_data = '';
      $product_csv = '';

      // Create the date title for the subreport.
      if ($args['length'] == 'day') {
        $date = \Drupal::service('date.formatter')->format($subreport['start'], 'uc_store');
      }
      else {
        $date = \Drupal::service('date.formatter')->format($subreport['start'], 'uc_store') . ' - ' . \Drupal::service('date.formatter')->format($subreport['end'], 'uc_store');
      }

      // Build the order data for the subreport.
      $result = db_query("SELECT COUNT(*) as count, title FROM {uc_orders} LEFT JOIN {uc_order_statuses} ON order_status_id = order_status WHERE :start <= created AND created <= :end AND order_status IN (:statuses[]) GROUP BY order_status, {uc_order_statuses}.title, {uc_order_statuses}.weight ORDER BY weight ASC", [':statuses[]' => $args['status'], ':start' => $subreport['start'], ':end' => $subreport['end']]);
      $statuses = array();

      // Put the order counts into an array by status.
      foreach ($result as $status) {
        $statuses[] = $this->t('@count - @title', ['@count' => $status->count, '@title' => $status->title]);
      }

      $order_data = implode('<br />', $statuses);
      $order_csv = implode("\n", $statuses);

      // Build the product data for the subreport.
      if ($args['detail']) {
        // Grab the detailed product breakdown if selected.
        $result = db_query("SELECT SUM(op.qty) as count, n.title, n.nid FROM {uc_order_products} op LEFT JOIN {uc_orders} o ON o.order_id = op.order_id LEFT JOIN {node_field_data} n ON n.nid = op.nid WHERE :start <= o.created AND o.created <= :end AND o.order_status IN (:statuses[]) GROUP BY n.nid ORDER BY count DESC, n.title ASC", [':statuses[]' => $args['status'], ':start' => $subreport['start'], ':end' => $subreport['end']]);
        foreach ($result as $product_breakdown) {
          $product_data .= $product_breakdown->count . ' x ' . Link::createFromRoute($product_breakdown->title, 'entity.node.canonical', ['node' => $product_breakdown->nid])->toString() . "<br />\n";
          $product_csv .= $product_breakdown->count . ' x ' . $product_breakdown->title . "\n";
        }
      }
      else {
        // Otherwise just display the total number of products sold.
        $product_data = db_query("SELECT SUM(qty) FROM {uc_orders} o LEFT JOIN {uc_order_products} op ON o.order_id = op.order_id WHERE :start <= created AND created <= :end AND order_status IN (:statuses[])", [':statuses[]' => $args['status'], ':start' => $subreport['start'], ':end' => $subreport['end']])->fetchField();
        $product_csv = $product_data;
      }

      // Tally up the revenue from the orders.
      $revenue_count = db_query("SELECT SUM(order_total) FROM {uc_orders} WHERE :start <= created AND created <= :end AND order_status IN (:statuses[])", [':statuses[]' => $args['status'], ':start' => $subreport['start'], ':end' => $subreport['end']])->fetchField();

      // Add the subreport's row to the report table.
      $rows[] = array(
        $date,
        empty($order_data) ? '0' : $order_data,
        empty($product_data) ? '0' : $product_data,
        uc_currency_format($revenue_count),
      );

      // Add the data to the CSV export.
      $csv_rows[] = array(
        $date,
        empty($order_csv) ? '0' : $order_csv,
        empty($product_csv) ? '0' : $product_csv,
        $revenue_count,
      );
    }

    // Calculate the totals for the report.
    $order_total = db_query("SELECT COUNT(*) FROM {uc_orders} WHERE :start <= created AND created <= :end AND order_status IN (:statuses[])", [':statuses[]' => $args['status'], ':start' => $args['start_date'], ':end' => $args['end_date']])->fetchField();
    $product_total = db_query("SELECT SUM(qty) FROM {uc_orders} o LEFT JOIN {uc_order_products} op ON o.order_id = op.order_id WHERE :start <= created AND created <= :end AND order_status IN (:statuses[])", [':statuses[]' => $args['status'], ':start' => $args['start_date'], ':end' => $args['end_date']])->fetchField();
    $revenue_total = db_query("SELECT SUM(order_total) FROM {uc_orders} WHERE :start <= created AND created <= :end AND order_status IN (:statuses[])", [':statuses[]' => $args['status'], ':start' => $args['start_date'], ':end' => $args['end_date']])->fetchField();

    // Add the total row to the report table.
    $rows[] = array(
      $this->t('Total'),
      $order_total,
      $product_total,
      uc_currency_format($revenue_total),
    );

    // Add the total data to the CSV export.
    $csv_rows[] = array(
      $this->t('Total'),
      $order_total,
      $product_total,
      $revenue_total,
    );

    // Cache the CSV export.
    $csv_data = $this->store_csv('uc_sales_custom', $csv_rows);

    // Build the page output holding the form, table, and CSV export link.
    $build['form'] = $this->formBuilder()->getForm('uc_report_sales_custom_form', $args, $args['status']);
    $build['report'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('width' => '100%', 'class' => array('uc-sales-table')),
    );
    $build['links'] = array(
      '#prefix' => '<div class="uc-reports-links">',
      '#suffix' => '</div>',
    );
    $build['links']['export_csv'] = array(
      '#markup' => Link::createFromRoute($this->t('Export to CSV file.'), 'uc_report.getcsv', ['report_id' => $csv_data['report'], 'user_id' => $csv_data['user']])->toString(),
    );

    return $build;
  }

  /**
   * Stores a CSV file for a report in Drupal's cache to be retrieved later.
   *
   * @param $report_id
   *   A unique string that identifies the report of the CSV file.
   * @param $rows
   *   The rows (table header included) that make CSV file.
   *
   * @return
   *   An array containing the values need to build URL that return the CSV file
   *   of the report and the CSV data itself.
   */
  public function store_csv($report_id, $rows) {
    $account = $this->currentUser();
    $csv_output = '';
    $user_id = $account->isAnonymous() ? session_id() : $account->id();
    foreach ($rows as $row) {
      foreach ($row as $index => $column) {
        $row[$index] = '"' . str_replace('"', '""', $column) . '"';
      }
      $csv_output .= implode(',', $row) . "\n";
    }
    \Drupal::cache()->set('uc_report_' . $report_id . '_' . $user_id, $csv_output, REQUEST_TIME + 86400);
    return array('user' => $user_id, 'report' => $report_id, 'csv' => $csv_output);
  }

  /**
   * Retrieves a cached CSV report & send its data.
   *
   * @param $report_id
   *   A unique string that identifies the specific report CSV to retrieve.
   * @param $user_id
   *   The user id to who's retrieving the report:
   *   - uid: Equals uid for authenticated users.
   *   - sid: Equals session_id for anonymous users.
   */
  public function getCSV($report_id, $user_id) {
    $account = $this->currentUser();
    $user_check = $account->isAnonymous() ? session_id() : $account->id();
    $csv_data = \Drupal::cache()->get('uc_report_' . $report_id . '_' . $user_id);

    if (!$csv_data || $user_id != $user_check) {
      drupal_set_message($this->t("The CSV data could not be retrieved. It's possible the data might have expired. Refresh the report page and try to retrieve the CSV file again."), 'error');
      throw new NotFoundHttpException();
    }
    else {
      $response = new Response($csv_data->data);
      $http_headers = array(
        'Pragma' => 'private',
        'Expires' => '0',
        'Cache-Control' => 'private, must-revalidate',
        'Content-Transfer-Encoding' => 'binary',
        'Content-Length' => strlen($csv_data->data),
        'Content-Disposition' => 'attachment; filename="' . $report_id . '.csv"',
        'Content-Type' => 'text/csv'
      );
      foreach ($http_headers as $header => $value) {
        $value = preg_replace('/\r?\n(?!\t| )/', '', $value);
        $response->headers->set($header, $value);
      }
      return $response;
    }
  }

  /**
   * Returns sales that occurred in a given time period.
   *
   * @param $time
   *   A UNIX timestamp representing the time in which to get sales data.
   * @param $interval
   *   The amount of time over which to count sales (e.g. [1] day, month, year).
   *
   * @return
   *   An associative array containing information about sales:
   *   - date: A string representing the day counting was started.
   *   - income: The total revenue that occurred during the time period.
   *   - total: The total number of orders completed during the time period.
   *   - average: The average revenue produced for each order.
   */
  public function get_sales($start, $interval = 'day') {
    // Add one to the granularity chosen, and use it to calc the new time.
    $end = strtotime('+1 ' . $interval, $start) - 1;

    // Set up the default SQL for getting orders with the proper status
    // within this period.
    $order_statuses = uc_report_order_statuses();

    // Get the total value of the orders.
    $output = array('income' => 0);
    $orders = db_query("SELECT o.order_total FROM {uc_orders} o WHERE o.order_status IN (:statuses[]) AND :start <= created AND created <= :end", [':statuses[]' => $order_statuses, ':start' => $start, ':end' => $end]);
    while ($order = $orders->fetchObject()) {
      $output['income'] += $order->order_total;
    }

    // Get the total amount of orders.
    $count = db_query("SELECT COUNT(o.order_total) FROM {uc_orders} o WHERE o.order_status IN (:statuses[]) AND :start <= created AND created <= :end", [':statuses[]' => $order_statuses, ':start' => $start, ':end' => $end])->fetchField();
    $output['total'] = $count;

    // Average for this period.
    $output['average'] = ($count != 0) ? round($output['income'] / $count, 2) : 0;

    return $output;
  }

  /**
   * Returns a list of timespans for subreports over that report's time span.
   *
   * To be used with a given time span for a report and specified interval for
   * subreports.
   *
   * @param $start
   *   A UNIX timestamp representing the time to start the report.
   * @param $end
   *   A UNIX timestamp representing the time to end the report.
   * @param $interval
   *   Text representing the time span of the subreport (e.g. 'day', 'week').
   *
   * @return
   *   An array of keyed arrays with the following values:
   *   - start: The starting point of the sub report.
   *   - end: The ending point of the sub report.
   */
  public function subreport_intervals($start, $report_end, $interval) {
    $subreports = array();

    while ($start < $report_end) {
      $end = strtotime('+1 ' . $interval, $start) - 1;
      $subreports[] = array(
        'start' => $start,
        'end' => min($end, $report_end),
      );

      $start = $end + 1;
    }

    return $subreports;
  }
}
