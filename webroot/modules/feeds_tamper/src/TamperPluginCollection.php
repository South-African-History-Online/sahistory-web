<?php

namespace Drupal\feeds_tamper;

use Drupal\tamper\TamperPluginCollection as TamperPluginCollectionBase;

/**
 * Collection of Tamper plugins for a specific Feeds importer.
 */
class TamperPluginCollection extends TamperPluginCollectionBase {

  /**
   * The key within the plugin configuration that contains the plugin ID.
   *
   * @var string
   */
  protected $pluginKey = 'plugin';

  /**
   * Provides uasort() callback to sort plugins.
   */
  public function sortHelper($aID, $bID) {
    $a = $this->get($aID)->getSetting('weight');
    $b = $this->get($bID)->getSetting('weight');

    if ($a != $b) {
      return ($a < $b) ? -1 : 1;
    }

    return parent::sortHelper($aID, $bID);
  }

}
