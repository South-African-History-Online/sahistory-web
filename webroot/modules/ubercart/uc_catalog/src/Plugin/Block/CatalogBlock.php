<?php

namespace Drupal\uc_catalog\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\uc_catalog\TreeNode;

/**
 * Provides the product catalog block.
 *
 * @Block(
 *   id = "uc_catalog",
 *   admin_label = @Translation("Catalog")
 * )
 */
class CatalogBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'link_title' => FALSE,
      'expanded' => FALSE,
      'product_count' => TRUE,
      'visibility' => array(
        'path' => array(
          'pages' => 'admin*',
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'view catalog');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['link_title'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Make the block title a link to the top-level catalog page.'),
      '#default_value' => $this->configuration['link_title'],
    );
    $form['expanded'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Always expand categories.'),
      '#default_value' => $this->configuration['expanded'],
    );
    $form['product_count'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display product counts.'),
      '#default_value' => $this->configuration['product_count'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['link_title'] = $form_state->getValue('link_title');
    $this->configuration['expanded'] = $form_state->getValue('expanded');
    $this->configuration['product_count'] = $form_state->getValue('product_count');

    // @todo Remove when catalog block theming is fully converted.
    $catalog_config = \Drupal::configFactory()->getEditable('uc_catalog.settings');

    $catalog_config
      ->set('expand_categories', $form_state->getValue('expanded'))
      ->set('block_nodecount', $form_state->getValue('product_count'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the vocabulary tree information.
    $vid = \Drupal::config('uc_catalog.settings')->get('vocabulary');
    $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);

    // Then convert it into an actual tree structure.
    $seq = 0;
    $menu_tree = new TreeNode();
    foreach ($tree as $knot) {
      $seq++;
      $knot->sequence = $seq;
      $knothole = new TreeNode($knot);
      // Begin at the root of the tree and find the proper place.
      $menu_tree->add_child($knothole);
    }

    $build['content'] = array(
      '#theme' => 'uc_catalog_block',
      '#menu_tree' => $menu_tree,
    );

    $build['#attached']['library'][] = 'uc_catalog/uc_catalog.styles';

    return $build;
  }

}
