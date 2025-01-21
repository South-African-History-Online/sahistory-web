<?php

namespace Drupal\tdih\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'This Day in History' block.
 *
 * @Block(
 *   id = "tdih_block",
 *   admin_label = @Translation("This Day in History"),
 *   category = @Translation("SAHO")
 * )
 */
class TDIHBlock extends BlockBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TDIHBlock instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $nodes = $this->loadNodes();

    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    $rendered_nodes = [];
    foreach ($nodes as $node) {
      $rendered_nodes[] = $view_builder->view($node, 'tdih_teaser');
    }

    return [
      '#theme' => 'tdih_block',
      '#nodes_rendered' => $rendered_nodes,
      '#cache' => [
        'contexts' => ['user.permissions', 'url.path'],
        'tags' => ['node_list'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Loads up to 5 published 'event' nodes. Customize as needed.
   *
   * @return \Drupal\node\NodeInterface[]
   */
  protected function loadNodes() {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'event')
      ->condition('status', NodeInterface::PUBLISHED)
      ->range(0, 5)
      ->execute();

  return [
    '#markup' => $this->t('Hello from TDIHBlock!'),
  ];
}
}
