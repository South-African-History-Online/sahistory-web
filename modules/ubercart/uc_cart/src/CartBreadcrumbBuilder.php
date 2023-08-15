<?php

namespace Drupal\uc_cart;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Provides a custom breadcrumb builder for the cart page.
 */
class CartBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'uc_cart.cart'
      && \Drupal::config('uc_cart.settings')->get('breadcrumb_text');
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $config = \Drupal::config('uc_cart.settings');
    $text = $config->get('breadcrumb_text');

    $links[] = Link::createFromRoute($this->t('Home'), '<front>');
    $links[] = Link::fromTextAndUrl($text, Url::fromUri('internal:/' . $config->get('breadcrumb_url'), ['absolute' => TRUE]));

    $breadcrumb = new Breadcrumb();
    $breadcrumb->setLinks($links);

    return $breadcrumb;
  }

}
