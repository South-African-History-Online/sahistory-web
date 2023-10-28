<?php

namespace Drupal\uc_order\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets the response to a page that triggres the print dialog.
 *
 * @Action(
 *   id = "uc_order_print_action",
 *   label = @Translation("Print invoice"),
 *   type = "uc_order"
 * )
 */
class PrintInvoiceAction extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Constructs a new DeleteNode object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('event_dispatcher'));
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\uc_order\OrderInterface $object */
    return $object->access('view', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($order = NULL) {
    $build = array(
      '#theme' => 'uc_order_invoice',
      '#order' => $order,
      '#op' => 'print',
      '#prefix' => '<div style="page-break-after: always;">',
      '#suffix' => '</div>',
    );

    $output = '<html><head><title>Invoice</title></head>';
    $output .= '<body onload="print();">';
    $output .= drupal_render($build);
    $output .= '</body></html>';
    $response = new Response($output);

    $listener = function($event) use ($response) {
      $event->setResponse($response);
    };
    // Add the listener to the event dispatcher.
    $this->dispatcher->addListener(KernelEvents::RESPONSE, $listener);
  }

}
