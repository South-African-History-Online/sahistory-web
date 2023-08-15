<?php

namespace Drupal\uc_order;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base order pane plugin implementation.
 */
abstract class OrderPanePluginBase extends PluginBase implements OrderPanePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getClasses() {
    return array('abs-left');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}
