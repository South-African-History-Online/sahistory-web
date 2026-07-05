<?php

namespace Drupal\saho_tools\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Citation' Block.
 *
 * @Block(
 *   id = "citation_block",
 *   admin_label = @Translation("Citation Block"),
 *   category = @Translation("All custom"),
 * )
 */
class CitationBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new CitationBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
          $configuration,
          $plugin_id,
          $plugin_definition,
          $container->get('current_route_match')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // One trigger markup for every placement: the citation_button theme
    // hook renders the square ghost .saho-cite-btn (#453). The legacy
    // citation-trigger class stays for anything keyed to it.
    $build['citation_button'] = [
      '#theme' => 'citation_button',
      '#attributes' => [
        'class' => ['citation-trigger'],
      ],
    ];

    // Attach the citation library.
    $build['#attached']['library'][] = 'saho_tools/citation';

    return $build;
  }

}
