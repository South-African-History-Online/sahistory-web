<?php

namespace Drupal\glazed_builder\Service\Handler;

use Drupal\Core\Asset\AttachedAssets;

interface BlockHandlerInterface {

  /**
   * Generate a block given it's module and delta
   *
   * @param array $blockInfo
   *   An array of info providing information on how the
   *   block should be loaded. Keys:
   *   - type: Will always be block
   *   - provider: Either 'content_block' or 'plugin', depending on the block type
   *   - uuid (content_block only): The UUID of the content block
   *   - id (plugin block only): The ID of the plugin
   * @param \Drupal\Core\Asset\AttachedAssets $assets
   *   Any retrieved libraries and/or settings should be attached to this
   * @param array $data
   *    Element data.
   *
   * @return string
   *   The HTML of the retrieved block
   */
  public function getBlock($blockInfo, AttachedAssets $assets, array $data);
}
