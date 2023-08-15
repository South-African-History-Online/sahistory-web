<?php

namespace Drupal\uc_quote\Plugin\Ubercart\OrderPane;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\EditableOrderPanePluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Get a shipping quote for the order from a quoting module.
 *
 * @UbercartOrderPane(
 *   id = "quotes",
 *   title = @Translation("Shipping quote"),
 *   weight = 7,
 * )
 */
class Quotes extends EditableOrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return array('pos-left');
  }

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $form['quote_button'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Get shipping quotes'),
      '#submit' => array(array($this, 'retrieveQuotes')),
      '#ajax' => array(
        'callback' => array($this, 'replaceOrderQuotes'),
        'wrapper' => 'quote',
        'effect' => 'slide',
        'progress' => array(
          'type' => 'bar',
          'message' => $this->t('Receiving quotes...'),
        ),
      ),
    );
    $form['quotes'] = array(
      '#type' => 'container',
      '#attributes' => array('id' => 'quote'),
      '#tree' => TRUE,
    );

    if ($form_state->get('quote_requested')) {
      // Rebuild form products, from uc_order_edit_form_submit()
      foreach ($form_state->getValue('products') as $product) {
        if (!isset($product['remove']) && intval($product['qty']) > 0) {
          foreach (array('qty', 'title', 'model', 'weight', 'weight_units', 'cost', 'price') as $field) {
            $order->products[$product['order_product_id']]->$field = $product[$field];
          }
        }
      }

      $form['quotes'] += uc_quote_build_quote_form($order);

      $form['quotes']['add_quote'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Apply to order'),
        '#submit' => array(array($this, 'applyQuote')),
        '#ajax' => array(
          'callback' => array($this, 'updateOrderRates'),
          'effect' => 'fade',
          'progress' => array(
            'type' => 'throbber',
            'message' => $this->t('Applying quotes...'),
          ),
        ),
      );
    }

    $form_state->set(['uc_ajax', 'uc_quote', 'delivery][delivery_country'], array(
      'quote' => array($this, 'replaceOrderQuotes'),
    ));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(OrderInterface $order, array &$form, FormStateInterface $form_state) {
  }

  /**
   * Form submission handler to retrieve quotes.
   */
  public function retrieveQuotes($form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $form_state->set('quote_requested', $element['#value'] == $form['quotes']['quote_button']['#value']);
    $form_state->setRebuild();
  }

  /**
   * Ajax callback: Manually applies a shipping quote to an order.
   */
  public function applyQuote($form, FormStateInterface $form_state) {
    if ($form_state->hasValue(['quotes', 'quote_option'])) {
      if ($order = $form_state->get('order')) {
        $quote_option = explode('---', $form_state->getValue(['quotes', 'quote_option']));
        $order->quote['method'] = $quote_option[0];
        $order->quote['accessorials'] = $quote_option[1];
        $method = ShippingQuoteMethod::load($quote_option[0]);
        $label = $method->label();

        $quote_option = $form_state->getValue(['quotes', 'quote_option']);
        $order->quote['rate'] = $form_state->getValue(['quotes', $quote_option, 'rate']);

        $result = db_query("SELECT line_item_id FROM {uc_order_line_items} WHERE order_id = :id AND type = :type", [':id' => $order->id(), ':type' => 'shipping']);
        if ($lid = $result->fetchField()) {
          uc_order_update_line_item($lid,
            $label,
            $order->quote['rate']
          );
          $form_state->set('uc_quote', array(
            'lid' => $lid,
            'title' => $label,
            'amount' => $order->quote['rate'],
          ));
        }
        else {
          uc_order_line_item_add($order->id(), 'shipping',
            $label,
            $order->quote['rate']
          );
        }

        // Save selected shipping
        uc_quote_uc_order_update($order);

        // Update line items.
        $order->line_items = $order->getLineItems();

        // @todo Still needed?
        $form_state->set('order', $order);

        $form_state->setRebuild();
        $form_state->set('quote_requested', FALSE);
      }
    }
  }

  /**
   * Ajax callback to update the quotes on the order edit form.
   */
  public function replaceOrderQuotes($form, FormStateInterface $form_state) {
    return $form['quotes']['quotes'];
  }

  /**
   * Ajax callback for applying shipping rates.
   */
  public function updateOrderRates($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Update shipping line item.
    if ($form_state->has('uc_quote')) {
      $lid = $form_state->get(['uc_quote', 'lid']);
      $form['line_items'][$lid]['title']['#value'] = $form_state->get(['uc_quote', 'title']);
      $form['line_items'][$lid]['amount']['#value'] = $form_state->get(['uc_quote', 'amount']);
    }
    $response->addCommand(new ReplaceCommand('#order-line-items', trim(drupal_render($form['line_items']))));

    // Reset shipping form.
    $response->addCommand(new ReplaceCommand('#quote', trim(drupal_render($form['quotes']['quotes']))));
    $status_messages = array('#type' => 'status_messages');
    $response->addCommand(new PrependCommand('#quote', drupal_render($status_messages)));

    return $response;
  }

}
