<?php

namespace Drupal\uc_cart\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Provides the shopping cart block.
 *
 * @Block(
 *  id = "uc_cart",
 *  admin_label = @Translation("Shopping cart")
 * )
 */
class CartBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'hide_empty' => FALSE,
      'show_image' => TRUE,
      'collapsible' => TRUE,
      'collapsed' => TRUE,
      'cache' => array(
        'max_age' => array(
          '#value' => 0,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Contents of cart don't depend on the page or user or any other
    // cache context we have available.
    return 0;
  }


  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['hide_empty'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Hide block if cart is empty.'),
      '#default_value' => $this->configuration['hide_empty'],
    );
    $form['show_image'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display the shopping cart icon in the block title.'),
      '#default_value' => $this->configuration['show_image'],
    );
    $form['collapsible'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Make the shopping cart block collapsible by clicking the name or arrow.'),
      '#default_value' => $this->configuration['collapsible'],
    );
    $form['collapsed'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display the shopping cart block collapsed by default.'),
      '#default_value' => $this->configuration['collapsed'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['hide_empty'] = $form_state->getValue('hide_empty');
    $this->configuration['show_image'] = $form_state->getValue('show_image');
    $this->configuration['collapsible'] = $form_state->getValue('collapsible');
    $this->configuration['collapsed'] = $form_state->getValue('collapsed');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cart = \Drupal::service('uc_cart.manager')->get();
    $product_count = count($cart->getContents());

    // Display nothing if the block is set to hide on empty and there are no
    // items in the cart.
    if (!$this->configuration['hide_empty'] || $product_count) {
      $items = array();
      $item_count = 0;
      $total = 0;
      if ($product_count) {
        /** @var \Drupal\uc_cart\CartItemInterface $item */
        foreach ($cart->getContents() as $item) {
          $display_item = \Drupal::moduleHandler()->invoke($item->data->module, 'uc_cart_display', array($item));

          if (count(Element::children($display_item))) {
            $items[] = array(
              'nid' => $display_item['nid']['#value'],
              'qty' => $display_item['qty']['#default_value'],
              // $display_item['title'] can be either #markup or #type => 'link', so render it.
              'title' => drupal_render($display_item['title']),
              'price' => $display_item['#total'],
              'desc' => isset($display_item['description']['#markup']) ? $display_item['description']['#markup'] : FALSE,
            );
            $total += $display_item['#total'];
            $item_count += $display_item['qty']['#default_value'];
          }

        }
      }

      // Build the cart links.
      $summary_links['view-cart'] = array(
        'title' => $this->t('View cart'),
        'url' => Url::fromRoute('uc_cart.cart'),
        'attributes' => array('rel' => ['nofollow']),
      );

      // Only add the checkout link if checkout is enabled.
      if (\Drupal::config('uc_cart.settings')->get('checkout_enabled')) {
        $summary_links['checkout'] = array(
          'title' => $this->t('Checkout'),
          'url' => Url::fromRoute('uc_cart.checkout'),
          'attributes' => array('rel' => ['nofollow']),
        );
      }

      $build['block'] = array(
        '#theme' => 'uc_cart_block',
        '#items' => $items,
        '#item_count' => $item_count,
        '#total' => $total,
        '#summary_links' => $summary_links,
        '#collapsed' => $this->configuration['collapsed'],
      );

      // Add the cart block CSS.
      $build['#attached']['library'][] = 'uc_cart/uc_cart.block.styles';

      // If the block is collapsible, add the appropriate JS.
      if ($this->configuration['collapsible']) {
        $build['#attached']['library'][] = 'uc_cart/uc_cart.block.scripts';
      }

      return $build;
    }
  }

}
