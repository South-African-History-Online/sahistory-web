<?php

namespace Drupal\uc_quote\Plugin\Ubercart\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_cart\CheckoutPanePluginBase;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_quote\Entity\ShippingQuoteMethod;

/**
 * Shipping quote checkout pane plugin.
 *
 * @CheckoutPane(
 *   id = "quotes",
 *   title = @Translation("Calculate shipping cost"),
 *   weight = 5,
 *   shippable = TRUE
 * )
 */
class QuotePane extends CheckoutPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $contents['#description'] = $this->t('Shipping quotes are generated automatically when you enter your address and may be updated manually with the button below.');

    $contents['#attached']['library'][] = 'uc_quote/uc_quote.styles';

    $contents['uid'] = array(
      '#type' => 'hidden',
      '#value' => \Drupal::currentUser()->id(),
    );
    $contents['quote_button'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Click to calculate shipping'),
      '#submit' => [[$this, 'paneSubmit']],
      '#weight' => 0,
      '#ajax' => array(
        'effect' => 'slide',
        'progress' => array(
          'type' => 'bar',
          'message' => $this->t('Receiving quotes...'),
        ),
      ),
      // Shipping quotes can be retrieved even if the form doesn't validate.
      '#limit_validation_errors' => array(),
    );
    $contents['quotes'] = array(
      '#type' => 'container',
      '#attributes' => array('id' => 'quote'),
      '#tree' => TRUE,
      '#weight' => 1,
    );

    // If this was an Ajax request, we reinvoke the 'prepare' op to ensure
    // that we catch any changes in panes heavier than this one.
    if ($form_state->getTriggeringElement()) {
      $this->prepare($order, $form, $form_state);
    }
    $contents['quotes'] += $order->quote_form;

    $form_state->set(['uc_ajax', 'uc_quote', 'panes][quotes][quote_button'], array(
      'payment-pane' => '::ajaxReplaceCheckoutPane',
      'quotes-pane' => '::ajaxReplaceCheckoutPane'
    ));
    $form_state->set(['uc_ajax', 'uc_quote', 'panes][quotes][quotes][quote_option'], array(
      'payment-pane' => '::ajaxReplaceCheckoutPane',
    ));

    return $contents;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(OrderInterface $order, array $form, FormStateInterface $form_state) {
    // If a quote was explicitly selected, add it to the order.
    if (isset($form['panes']['quotes']['quotes']['quote_option']['#value']) && isset($form['panes']['quotes']['quotes']['quote_option']['#default_value'])
      && $form['panes']['quotes']['quotes']['quote_option']['#value'] !== $form['panes']['quotes']['quotes']['quote_option']['#default_value']) {
      $quote_option = explode('---', $form_state->getValue(['panes', 'quotes', 'quotes', 'quote_option']));
      $order->quote['method'] = $quote_option[0];
      $order->quote['accessorials'] = $quote_option[1];
      $order->data->uc_quote_selected = TRUE;
    }

    // If the current quote was never explicitly selected, discard it and
    // use the default.
    if (empty($order->data->uc_quote_selected)) {
      unset($order->quote);
    }

    // Ensure that the form builder uses the default value to decide which
    // radio button should be selected.
    $input = $form_state->getUserInput();
    unset($input['panes']['quotes']['quotes']['quote_option']);
    $form_state->setUserInput($input);

    $order->quote_form = uc_quote_build_quote_form($order, !$form_state->get('quote_requested'));

    $default_option = _uc_quote_extract_default_option($order->quote_form);
    if ($default_option) {
      $order->quote['rate'] = $order->quote_form[$default_option]['rate']['#value'];

      $quote_option = explode('---', $default_option);
      $order->quote['method'] = $quote_option[0];
      $order->quote['accessorials'] = $quote_option[1];
      $method = ShippingQuoteMethod::load($quote_option[0]);
      $label = $method->label();

      $result = db_query("SELECT line_item_id FROM {uc_order_line_items} WHERE order_id = :id AND type = :type", [':id' => $order->id(), ':type' => 'shipping']);
      if ($lid = $result->fetchField()) {
        uc_order_update_line_item($lid,
          $label,
          $order->quote['rate']
        );
      }
      else {
        uc_order_line_item_add($order->id(), 'shipping',
          $label,
          $order->quote['rate']
        );
      }
    }
    // If there is no default option, then no valid quote was selected.
    else {
      unset($order->quote);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $this->prepare($order, $form, $form_state);

    if (!isset($order->quote) && \Drupal::config('uc_quote.settings')->get('require_quote')) {
      $form_state->setErrorByName('panes][quotes][quotes][quote_option', $this->t('You must select a shipping option before continuing.'));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function review(OrderInterface $order) {
    $review = array();

    $result = db_query("SELECT * FROM {uc_order_line_items} WHERE order_id = :id AND type = :type", [':id' => $order->id(), ':type' => 'shipping']);
    if ($line_item = $result->fetchAssoc()) {
      $review[] = array('title' => $line_item['title'], 'data' => array('#theme' => 'uc_price', '#price' => $line_item['amount']));
    }

    return $review;
  }

  /**
   * Pane submission handler to trigger quote calculation.
   */
  public function paneSubmit($form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $form_state->set('quote_requested', TRUE);
  }

}
