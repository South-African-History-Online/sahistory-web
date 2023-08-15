<?php

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\EditableOrderPanePluginBase;
use Drupal\uc_order\Entity\OrderProduct;
use Drupal\uc_order\OrderInterface;

/**
 * Manage the products an order contains.
 *
 * @UbercartOrderPane(
 *   id = "products",
 *   title = @Translation("Products"),
 *   weight = 5,
 * )
 */
class Products extends EditableOrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    $build = array(
      '#type' => 'table',
      '#attributes' => array('class' => array('order-pane-table')),
      '#header' => array(
        'qty' => array(
          'data' => $this->t('Qty'),
          'class' => array('qty'),
        ),
        'product' => array(
          'data' => $this->t('Product'),
          'class' => array('product'),
        ),
        'model' => array(
          'data' => $this->t('SKU'),
          'class' => array('sku', RESPONSIVE_PRIORITY_LOW),
        ),
        'cost' => array(
          'data' => $this->t('Cost'),
          'class' => array('cost', RESPONSIVE_PRIORITY_LOW),
        ),
        'price' => array(
          'data' => $this->t('Price'),
          'class' => array('price'),
        ),
        'total' => array(
          'data' => $this->t('Total'),
          'class' => array('price'),
        ),
      ),
      '#empty' => $this->t('This order contains no products.'),
    );

    $account = \Drupal::currentUser();
    if (!$account->hasPermission('administer products')) {
      unset($build['#header']['cost']);
    }

    // @todo Replace with Views.
    $rows = array();
    foreach ($order->products as $id => $product) {
      $rows[$id]['qty'] = array(
        'data' => array(
          '#theme' => 'uc_qty',
          '#qty' => $product->qty->value,
        ),
        'class' => array('qty'),
      );

      if ($product->nid->entity && $product->nid->entity->access('view')) {
        $title = $product->nid->entity->toLink()->toString();
      }
      else {
        $title = $product->title->value;
      }
      $rows[$id]['product'] = array(
        'data' => array('#markup' => $title . uc_product_get_description($product)),
        'class' => array('product'),
      );
      $rows[$id]['model'] = array(
        'data' => array('#markup' => $product->model->value),
        'class' => array('sku'),
      );
      if ($account->hasPermission('administer products')) {
        $rows[$id]['cost'] = array(
          'data' => array(
            '#theme' => 'uc_price',
            '#price' => $product->cost->value,
          ),
          'class' => array('cost'),
        );
      }
      $rows[$id]['price'] = array(
        'data' => array(
          '#theme' => 'uc_price',
          '#price' => $product->price->value,
          '#suffixes' => array(),
        ),
        'class' => array('price'),
      );
      $rows[$id]['total'] = array(
        'data' => array(
          '#theme' => 'uc_price',
          '#price' => $product->price->value * $product->qty->value,
          '#suffixes' => array(),
        ),
        'class' => array('total'),
      );
    }
    $build['#rows'] = $rows;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $form['add_product_button'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add product'),
      '#submit' => array(array($this, 'productSelectSearch')),
      '#ajax' => array(
        'callback' => array($this, 'ajaxCallback'),
        'wrapper' => 'product-controls',
      ),
    );
    $form['add_blank_line_button'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add blank line'),
      '#submit' => array(array($this, 'addBlank')),
      '#ajax' => array(
        'callback' => array($this, 'ajaxCallback'),
        'wrapper' => 'product-controls',
      ),
    );

    $form['product_controls'] = array(
      '#tree' => TRUE,
      '#prefix' => '<div id="product-controls">',
      '#suffix' => '</div>',
    );

    $controls = array();

    if ($form_state->has('products_action')) {
      switch ($form_state->get('products_action')) {
        case 'select':
          $controls = $this->productSelectForm($form['product_controls'], $form_state, $order);
          break;
        case 'add_product':
          $controls = $this->addProductForm($form['product_controls'], $form_state, $order, $form_state->get('node'));
          break;
      }
    }

    $form['product_controls'] += $controls;

    $form += $this->editProductsForm($form, $form_state, $order->products);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(OrderInterface $order, array &$form, FormStateInterface $form_state) {
    // @todo Decouple stock related code into uc_stock
    if (\Drupal::moduleHandler()->moduleExists('uc_stock')) {
      $qtys = array();
      foreach ($order->products as $product) {
        $qtys[$product->order_product_id] = $product->qty;
      }
    }

    if (is_array($form_state->getValue('products'))) {
      foreach ($form_state->getValue('products') as $product) {
        if (isset($order->products[$product['order_product_id']])) {
          foreach (array('qty', 'title', 'model', 'weight', 'weight_units', 'cost', 'price') as $field) {
            $order->products[$product['order_product_id']]->$field = $product[$field];
          }

          if (\Drupal::moduleHandler()->moduleExists('uc_stock')) {
            $product = (object)$product;
            $temp = $product->qty;
            $product->qty = $product->qty - $qtys[$product->order_product_id];
            uc_stock_adjust_product_stock($product, 0, $order);
            $product->qty = $temp;
          }
        }
      }
    }
  }

  /**
   * Form to choose a product to add to the order.
   *
   * @ingroup forms
   */
  protected function productSelectForm($form, FormStateInterface $form_state, $order) {
    $options = $form_state->get('product_select_options');
    $ajax = array(
      'callback' => array($this, 'ajaxCallback'),
      'wrapper' => 'product-controls',
    );

    $form['nid'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#size' => 7,
      '#ajax' => $ajax + array(
          'event' => 'dblclick',
          'trigger_as' => array(
            'name' => 'op',
            'value' => $this->t('Select'),
          ),
        ),
    );
    $form['product_search'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Search by name or model/SKU (* is the wildcard)'),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['select'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Select'),
      '#validate' => array(array($this, 'productSelectValidate')),
      '#submit' => array(array($this, 'productSelectSubmit')),
      '#ajax' => $ajax,
      '#weight' => 0,
    );
    $form['actions']['search'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#submit' => array(array($this, 'productSelectSearch')),
      '#ajax' => $ajax,
      '#weight' => 1,
    );
    $form['actions']['close'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Close'),
      '#submit' => array(array($this, 'productSelectClose')),
      '#ajax' => $ajax,
      '#weight' => 2,
    );

    return $form;
  }

  /**
   * Validation handler for self::productSelectForm().
   */
  public function productSelectValidate($form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty(['product_controls', 'nid'])) {
      $form_state->setErrorByName('product_controls][nid', $this->t('Please select a product.'));
    }
  }

  /**
   * Sets the quantity and attributes of a product added to the order.
   */
  protected function addProductForm($form, FormStateInterface $form_state, $order, $node) {
    $data = array();
    if ($form_state->hasValue(['product_controls', 'qty'])) {
      $data += \Drupal::moduleHandler()->invokeAll('uc_add_to_cart_data', array($form_state->getValue('product_controls')));
    }
    if (!empty($node->data) && is_array($node->data)) {
      $data += $node->data;
    }
    $node = uc_product_load_variant(intval($form_state->getValue(['product_controls', 'nid'])), $data);
    $form['title'] = array(
      '#markup' => '<h3>' . $node->label() . '</h3>',
    );
    $form['nid'] = array(
      '#type' => 'hidden',
      '#value' => $node->id(),
    );
    $form['qty'] = array(
      '#type' => 'uc_quantity',
      '#title' => $this->t('Quantity'),
      '#default_value' => 1,
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add to order'),
      '#submit' => array(array($this, 'addProductSubmit')),
      '#ajax' =>  array(
        'callback' => array($this, 'ajaxCallback'),
        'wrapper' => 'product-controls',
      ),
    );
    $form['actions']['cancel'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => array(array($this, 'productSelectSearch')),
      '#ajax' =>  array(
        'callback' => array($this, 'ajaxCallback'),
        'wrapper' => 'product-controls',
      ),
      '#limit_validation_errors' => array(),
    );
    $form['node'] = array(
      '#type' => 'value',
      '#value' => $node,
    );

    uc_form_alter($form, $form_state, __FUNCTION__);

    return $form;
  }

  /**
   * Form to allow ordered products' data to be changed.
   */
  protected function editProductsForm($form, FormStateInterface $form_state, $products) {
    $form['products'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => array($this->t('Remove'), $this->t('Quantity'), $this->t('Name'), $this->t('SKU'), $this->t('Weight'), $this->t('Units'), $this->t('Cost'), $this->t('Price')),
      '#attributes' => array('id' => 'order-edit-products', 'class' => array('order-pane-table')),
      '#empty' => $this->t('This order contains no products.'),
    );
    $form['data'] = array(
      '#tree' => TRUE,
      '#parents' => array('products'),
    );
    foreach ($products as $i => $product) {
      $form['products'][$i]['remove'] = array(
        '#type' => 'image_button',
        '#title' => $this->t('Remove this product.'),
        '#name' => "products[$i][remove]",
        '#src' => drupal_get_path('module', 'uc_store') . '/images/error.gif',
        '#button_type' => 'remove',
        '#submit' => array(array($this, 'removeProduct'), '::submitForm'),
        '#return_value' => $product->order_product_id->value,
      );
      $form['data'][$i]['order_product_id'] = array(
        '#type' => 'hidden',
        '#value' => $product->order_product_id->value,
      );
      $form['data'][$i]['nid'] = array(
        '#type' => 'hidden',
        '#value' => $product->nid->target_id,
      );
      $form['products'][$i]['qty'] = array(
        '#type' => 'uc_quantity',
        '#title' => $this->t('Quantity'),
        '#title_display' => 'invisible',
        '#default_value' => $product->qty->value,
      );
      $form['products'][$i]['title'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#title_display' => 'invisible',
        '#default_value' => $product->title->value,
        '#size' => 32,
        '#maxlength' => 255,
      );
      $form['products'][$i]['model'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('SKU'),
        '#title_display' => 'invisible',
        '#default_value' => $product->model->value,
        '#size' => 6,
      );
      $form['products'][$i]['weight'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $product->weight->value,
        '#size' => 3,
      );
      $units = array(
        'lb' => $this->t('Pounds'),
        'kg' => $this->t('Kilograms'),
        'oz' => $this->t('Ounces'),
        'g'  => $this->t('Grams'),
      );
      $form['products'][$i]['weight_units'] = array(
        '#type' => 'select',
        '#title' => $this->t('Units'),
        '#title_display' => 'invisible',
        '#default_value' => $product->weight->units,
        '#options' => $units,
      );
      $form['products'][$i]['cost'] = array(
        '#type' => 'uc_price',
        '#title' => $this->t('Cost'),
        '#title_display' => 'invisible',
        '#default_value' => $product->cost->value,
        '#size' => 5,
      );
      $form['products'][$i]['price'] = array(
        '#type' => 'uc_price',
        '#title' => $this->t('Price'),
        '#title_display' => 'invisible',
        '#default_value' => $product->price->value,
        '#size' => 5,
      );
      $form['data'][$i]['data'] = array(
        '#type' => 'hidden',
        '#value' => serialize($product->data->first() ? $product->data->first()->toArray() : NULL),
      );
    }

    return $form;
  }

  /**
   * Sets the order pane to show the product selection form.
   */
  public function productSelectSearch($form, FormStateInterface $form_state) {
    $types = uc_product_types();
    $options = array();

    $query = db_select('node_field_data', 'n')
      ->fields('n', array('nid', 'title'))
      ->condition('n.type', $types, 'IN')
      ->orderBy('n.title')
      ->addTag('node_access');

    if (!$form_state->isValueEmpty(['product_controls', 'product_search'])) {
      $search = strtolower(str_replace('*', '%', $form_state->getValue(['product_controls', 'product_search'])));
      $query->leftJoin('uc_products', 'p', 'n.nid = p.nid');
      $query->condition(db_or()
        ->condition('n.title', $search, 'LIKE')
        ->condition('p.model', $search, 'LIKE')
      );
    }

    $result = $query->execute();
    foreach ($result as $row) {
      $options[$row->nid] = $row->title;
    }

    if (count($options) == 0) {
      $options[0] = $this->t('No products found.');
    }

    $form_state->set('products_action', 'select');
    $form_state->set('product_select_options', $options);
    $form_state->set('refresh_products', NULL);
    $form_state->setRebuild();
  }

  /**
   * Sets the order pane to show the add product to order form.
   */
  public function productSelectSubmit($form, FormStateInterface $form_state) {
    $form_state->set('products_action', 'add_product');
    $form_state->set('node', node_load($form_state->getValue(['product_controls', 'nid'])));
    $form_state->set('refresh_products', NULL);
    $form_state->setRebuild();
  }

  /**
   * Hides the form to add another product to the order.
   */
  public function productSelectClose($form, FormStateInterface $form_state) {
    $form_state->set('products_action', NULL);
    $form_state->set('refresh_products', NULL);
    $form_state->set('product_select_options', NULL);
    $form_state->setRebuild();
  }

  /**
   * Form submit callback: add a blank line product to an order.
   */
  public function addBlank($form, FormStateInterface $form_state) {
    $form_state->set('refresh_products', TRUE);
    $form_state->setRebuild();

    $order = $form['#order'];

    $product = OrderProduct::create(array(
      'qty' => 1,
      'order_id' => $order->id(),
    ));
    $product->save();

    $order->products[] = $product;
    $order->logChanges(array('add' => $this->t('Added new product line to order.')));
  }

  /**
   * Form submit callback: add a product to an order.
   */
  public function addProductSubmit($form, FormStateInterface $form_state) {
    $form_state->set('products_action', 'products_select');
    $form_state->set('refresh_products', TRUE);
    $form_state->setRebuild();

    $order = $form['#order'];

    $data = \Drupal::moduleHandler()->invokeAll('uc_add_to_cart_data', array($form_state->getValue('product_controls')));
    $values = uc_product_load_variant(intval($form_state->getValue(['product_controls', 'nid'])), $data)->toArray();
    $values['qty'] = $form_state->getValue(['product_controls', 'qty']);
    $values['order_id'] = $order->id();

    $product = OrderProduct::create($values);
    \Drupal::moduleHandler()->alter('uc_order_product', $product, $order);
    $product->save();

    $order->products[] = $product;
    $order->logChanges([$this->t('Added (@qty) @title to order.', ['@qty' => $product->qty->value, '@title' => $product->title->value])]);

    // Decrement stock.
    if (\Drupal::moduleHandler()->moduleExists('uc_stock')) {
      uc_stock_adjust_product_stock($product, 0, $order);
    }

    // Add this product to the form values for accurate tax calculations.
    $products = $form_state->getValue('products');
    $products[] = $product;
    $form_state->setValue('products', $products);
  }

  /**
   * Form submit callback: remove a product from an order.
   */
  public function removeProduct($form, FormStateInterface $form_state) {
    $form_state->set('refresh_products', TRUE);

    /** @var \Drupal\uc_order\Entity\Order $order */
    $order = $form['#order'];

    $triggering_element = $form_state->getTriggeringElement();
    $order_product_id = intval($triggering_element['#return_value']);
    /** @var \Drupal\uc_order\Entity\OrderProduct $product */
    $product = $order->products[$order_product_id];

    if (\Drupal::moduleHandler()->moduleExists('uc_stock')) {
      // Replace stock immediately.
      uc_stock_adjust($product->model->value, $product->qty->value);
    }

    $product->delete();
    unset($order->products[$order_product_id]);
    $order->logChanges([$this->t('Removed %title from order.', ['%title' => $product->title->value])]);
  }

  /**
   * AJAX callback to render the order product controls.
   */
  public function ajaxCallback($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#product-controls', trim(drupal_render($form['product_controls']))));
    $status_messages = array('#type' => 'status_messages');
    $response->addCommand(new PrependCommand('#product-controls', drupal_render($status_messages)));

    if ($form_state->get('refresh_products')) {
      $response->addCommand(new ReplaceCommand('#order-edit-products', trim(drupal_render($form['products']))));
      $response->addCommand(new ReplaceCommand('#order-line-items', trim(drupal_render($form['line_items']))));
    }

    // Remove the field so we only refresh the admin comments item-list.
    unset($form['admin_comment_field']);
    $response->addCommand(new ReplaceCommand('#order-pane-admin_comments .item-list', uc_order_pane_admin_comments('edit-theme', $form['#order'], $form, $form_state)));

    return $response;
  }

}
