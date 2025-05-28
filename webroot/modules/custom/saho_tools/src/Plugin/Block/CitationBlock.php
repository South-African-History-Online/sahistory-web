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
 *   category = @Translation("SAHO Tools"),
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

    // Add a citation button that will trigger the citation modal.
    $build['citation_button'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Cite This Page'),
      '#attributes' => [
        'class' => ['btn', 'btn-outline-primary', 'btn-sm', 'citation-trigger'],
        'data-citation-trigger' => 'true',
      ],
    ];

    // Attach the citation library.
    $build['#attached']['library'][] = 'saho_tools/citation';

    return $build;
  }

}
