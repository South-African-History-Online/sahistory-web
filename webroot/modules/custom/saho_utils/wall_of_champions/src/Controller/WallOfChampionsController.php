<?php

namespace Drupal\wall_of_champions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\wall_of_champions\Service\ChampionWallService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Wall of Champions page.
 */
class WallOfChampionsController extends ControllerBase {

  /**
   * The champion wall service.
   *
   * @var \Drupal\wall_of_champions\Service\ChampionWallService
   */
  protected ChampionWallService $championWall;

  /**
   * Constructs a WallOfChampionsController object.
   *
   * @param \Drupal\wall_of_champions\Service\ChampionWallService $champion_wall
   *   The champion wall service.
   */
  public function __construct(ChampionWallService $champion_wall) {
    $this->championWall = $champion_wall;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('wall_of_champions.champion_wall')
    );
  }

  /**
   * Renders the Wall of Champions page.
   *
   * @return array
   *   Render array.
   */
  public function page() {
    $champions = $this->championWall->getChampions();

    return [
      '#theme' => 'wall_of_champions_page',
      '#champions' => $champions,
      '#total_count' => count($champions),
      '#cache' => [
        'max-age' => 3600,
        'contexts' => ['url'],
        // user_list covers role grants and opt-in changes (both save the
        // user); commerce_order_list covers the "champion since" date.
        'tags' => ['user_list', 'commerce_order_list'],
      ],
    ];
  }

}
