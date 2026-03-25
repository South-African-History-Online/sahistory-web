<?php

namespace Drupal\champion_access\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns response for the /champion landing page.
 *
 * All display is handled by the
 * page--path--champion.html.twig template in saho_shop theme.
 */
class ChampionPageController extends ControllerBase {

  /**
   * Returns the champion membership page.
   *
   * @return array
   *   A render array.
   */
  public function page() {
    return [
      '#markup' => '',
      '#cache' => [
        'contexts' => ['url.path', 'user.roles'],
        'max-age' => 3600,
      ],
    ];
  }

}
