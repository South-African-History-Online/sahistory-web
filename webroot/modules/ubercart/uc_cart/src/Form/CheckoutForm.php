<?php

namespace Drupal\uc_cart\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\uc_cart\Plugin\CheckoutPaneManager;
use Drupal\uc_store\AjaxAttachTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * The checkout form built up from the enabled checkout panes.
 */
class CheckoutForm extends FormBase {

  use AjaxAttachTrait;

  /**
   * The checkout pane manager.
   *
   * @var \Drupal\uc_cart\Plugin\CheckoutPaneManager
   */
  protected $checkoutPaneManager;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a CheckoutController.
   *
   * @param \Drupal\uc_cart\Plugin\CheckoutPaneManager $checkout_pane_manager
   *   The checkout pane plugin manager.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(CheckoutPaneManager $checkout_pane_manager, SessionInterface $session) {
    $this->checkoutPaneManager = $checkout_pane_manager;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_cart.checkout_pane'),
      $container->get('session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_checkout_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $order = NULL) {
    if ($processed = $form_state->has('order')) {
      $order = $form_state->get('order');
    }
    else {
      $form_state->set('order', $order);
    }

    $form['#attributes']['class'][] = 'uc-cart-checkout-form';
    $form['#attached']['library'][] = 'uc_cart/uc_cart.styles';
    $form['panes'] = array('#tree' => TRUE);

    $filter = array('enabled' => FALSE);

    // If the order isn't shippable, remove panes with shippable == TRUE.
    if (!$order->isShippable() && $this->config('uc_cart.settings')->get('panes.delivery.settings.delivery_not_shippable')) {
      $filter['shippable'] = TRUE;
    }

    $panes = $this->checkoutPaneManager->getPanes($filter);

    // Invoke the 'prepare' op of enabled panes, but only if their 'process' ops
    // have not been invoked on this request (i.e. when rebuilding after AJAX).
    foreach ($panes as $id => $pane) {
      if (!$form_state->get(['panes', $id, 'prepared'])) {
        $pane->prepare($order, $form, $form_state);
        $form_state->set(['panes', $id, 'prepared'], TRUE);
        $processed = FALSE; // Make sure we save the updated order.
      }
    }

    // Load the line items and save the order. We do this after the 'prepare'
    // callbacks of enabled panes have been invoked, because these may have
    // altered the order.
    if (!$processed) {
      $order->line_items = $order->getLineItems();
      $order->save();
    }

    foreach ($panes as $id => $pane) {
      $form['panes'][$id] = $pane->view($order, $form, $form_state);
      $form['panes'][$id] += array(
        '#type' => 'details',
        '#title' => $pane->getTitle(),
        '#id' => $id . '-pane',
        '#open' => TRUE,
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['cancel'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#validate' => array(),
      '#limit_validation_errors' => array(),
      '#submit' => array(array($this, 'cancel')),
    );
    $form['actions']['continue'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Review order'),
      '#button_type' => 'primary',
    );

    $form['#process'][] = array($this, 'ajaxProcessForm');

    $this->session->remove('uc_checkout_review_' . $order->id());
    $this->session->remove('uc_checkout_complete_' . $order->id());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $order = $form_state->get('order');

    // Update the order "changed" time to prevent timeout on ajax requests.
    $order->setChangedTime(REQUEST_TIME);

    // Validate/process the cart panes.  A FALSE value results in failed checkout.
    $form_state->set('checkout_valid', TRUE);
    foreach (Element::children($form_state->getValue('panes')) as $id) {
      $pane = $this->checkoutPaneManager->createInstance($id);
      if ($pane->process($order, $form, $form_state) === FALSE) {
        $form_state->set('checkout_valid', FALSE);
      }
    }

    // Reload line items and save order.
    $order->line_items = $order->getLineItems();
    $order->save();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('checkout_valid') === FALSE) {
      $form_state->setRedirect('uc_cart.checkout');
    }
    else {
      $form_state->setRedirect('uc_cart.checkout_review');
      $this->session->set('uc_checkout_review_' . $form_state->get('order')->id(), TRUE);
    }

    $form_state->set('checkout_valid', NULL);
  }

  /**
   * Submit handler for the "Cancel" button on the checkout form.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $order = $form_state->get('order');
    if ($this->session->get('cart_order') == $order->id()) {
      uc_order_comment_save($order->id(), 0, $this->t('Customer canceled this order from the checkout form.'));
      $this->session->remove('cart_order');
    }

    $this->session->remove('uc_checkout_review_' . $order->id());
    $this->session->remove('uc_checkout_complete_' . $order->id());

    $form_state->setRedirect('uc_cart.cart');
  }

}
