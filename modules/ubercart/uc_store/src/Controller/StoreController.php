<?php

namespace Drupal\uc_store\Controller;

use Drupal\system\Controller\SystemController;

/**
 * Returns responses for Ubercart store routes.
 */
class StoreController extends SystemController {

  /**
   * {@inheritdoc}
   */
  public function overview($link_id = 'uc_store.admin.store') {
    $build['blocks'] = parent::overview($link_id);

    if ($results = $this->moduleHandler()->invokeAll('uc_store_status')) {
      $map = [
        'warning' => REQUIREMENT_WARNING,
        'error' => REQUIREMENT_ERROR,
      ];
      foreach ($results as $message) {
        $requirements[] = [
          'title' => $message['title'],
          'description' => $message['desc'],
          'severity' => isset($map[$message['status']]) ? $map[$message['status']] : REQUIREMENT_INFO,
        ];
      }

      $build['status'] = [
        '#theme' => 'status_report',
        '#prefix' => '<h2>' . $this->t('Store status') . '</h2>',
        '#requirements' => $requirements,
      ];
    }

    return $build;
  }

}
