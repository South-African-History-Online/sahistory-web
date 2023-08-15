<?php

namespace Drupal\glazed_builder\Controller;

interface AjaxControllerInterface{

  /**
   * AJAX callback: Handles various operations for frontend drag and drop builder
   */
  public function ajaxCallback();

  /**
   * Callback to handle AJAX file uploads
   */
  public function fileUpload();
}
