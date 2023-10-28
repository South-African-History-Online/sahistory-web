<?php

namespace Drupal\glazed_builder\Service\Handler;

use Drupal\Core\Asset\AttachedAssets;

interface ViewHandlerInterface {

  /**
   * Retrieve a view for a given ID
   *
   * @param string $display_id
   *   The ID of the view to retrieve
   * @param string $display_id
   *   The ID of the display to retrieve
   * @param \Drupal\Core\Asset\AttachedAssets $assets
   *   Any retrieved libraries and/or settings should be attached to this
   *
   * @return string
   *   The HTML of the retrieved view
   */
  public function getView($viewId, $exp_input, $displayId, $data, AttachedAssets $assets);
}
