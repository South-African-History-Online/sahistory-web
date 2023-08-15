<?php

namespace Drupal\uc_credit\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ensures sensitive checkout session data doesn't persist on other pages.
 */
class SessionDataSubscriber implements EventSubscriberInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new SessionDataSubscriber.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Ensures sensitive checkout session data doesn't persist on other pages.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $request = $event->getRequest();
    $session = $request->getSession();
    if (!$session->has('sescrd')) {
      return;
    }

    // Only POSTs to the checkout and review pages can persist the data.
    $allowed_routes = ['uc_cart.checkout', 'uc_cart.checkout_review'];
    if ($request->isMethod('POST') && in_array($this->routeMatch->getRouteName(), $allowed_routes)) {
      return;
    }

    $session->remove('sescrd');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Must run before \Symfony\Component\HttpKernel\EventListener\SaveSessionListener.
    return [KernelEvents::RESPONSE => ['onKernelResponse']];
  }

}
