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
 *   admin_label = @Translation("This Day in History")
 * )
 */
class TDIHBlock extends BlockBase
{

    /**
     * The entity type manager service.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructs a new TDIHBlock instance.
     *
     * @param array                                          $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string                                         $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed                                          $plugin_definition
     *   The plugin implementation definition.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->entityTypeManager = $entity_type_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
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
    public function build()
    {
        $nodes = $this->loadNodes();

        // We'll render them in a custom display mode: 'tdih_teaser'.
        $view_builder = $this->entityTypeManager->getViewBuilder('node');

        $rendered_nodes = [];
        foreach ($nodes as $node) {
            // Build the render array for each node in the 'tdih_teaser' display mode.
            $rendered_nodes[] = $view_builder->view($node, 'tdih_teaser');
        }

        return [
        '#theme' => 'tdih_block', // matches the hook_theme key in tdih.theme
        '#nodes_rendered' => $rendered_nodes,
        '#cache' => [
        'contexts' => ['user.permissions', 'url.path'], // example contexts
        'tags' => ['node_list'], // we'll just tag with all node content
        'max-age' => 3600,
        ],
        ];
    }

    /**
     * Loads up to 5 published 'event' nodes. Customize to your needs.
     *
     * @return \Drupal\node\NodeInterface[]
     *   An array of loaded node objects.
     */
    protected function loadNodes()
    {
        $nids = \Drupal::entityQuery('node')
            ->condition('type', 'event')
            ->condition('status', NodeInterface::PUBLISHED)
            ->range(0, 5)
            ->execute();

        return Node::loadMultiple($nids);
    }

}
