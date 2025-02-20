<?php

namespace Drupal\tdih\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tdih\Service\NodeFetcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a TDIH Block with manual override config.
 *
 * @Block(
 *   id = "tdih_block",
 *   admin_label = @Translation("TDIH Block (Plugin)"),
 *   category = @Translation("Custom")
 * )
 */
class TdihBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The NodeFetcher service used to load today's nodes.
   *
   * @var \Drupal\tdih\Service\NodeFetcher
   */
  protected $nodeFetcher;

  /**
   * Constructs a new TdihBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\tdih\Service\NodeFetcher $nodeFetcher
   *   Our custom NodeFetcher service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, NodeFetcher $nodeFetcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nodeFetcher = $nodeFetcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tdih.node_fetcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Default settings for newly placed blocks.
    return [
      'use_manual_override' => FALSE,
      'manual_entity_id' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   *
   * Builds the configuration form used when placing or editing this block.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['use_manual_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Manual Override?'),
      '#description' => $this->t('Enable to manually select a node instead of auto date-based logic.'),
      '#default_value' => $this->configuration['use_manual_override'],
    ];

    $form['manual_entity_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Manual Entity'),
      '#description' => $this->t('Select an event node to display if "Use Manual Override" is checked.'),
      '#target_type' => 'node',
      // Specify the default selection handler for nodes.
      '#selection_handler' => 'default:node',
      // Limit to specific bundles (e.g., 'event').
      '#selection_settings' => [
        'target_bundles' => ['event'],
      ],
      '#default_value' => ($this->configuration['manual_entity_id'])
        ? Node::load($this->configuration['manual_entity_id'])
        : NULL,
      '#states' => [
        'visible' => [
          ':input[name="use_manual_override"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Handles form submissions for this block's configuration.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['use_manual_override'] = $form_state->getValue('use_manual_override');
    $this->configuration['manual_entity_id'] = $form_state->getValue('manual_entity_id');
  }

  /**
   * {@inheritdoc}
   *
   * Builds the render array for the TDIH block.
   */
  public function build() {
    $manual_override = $this->configuration['use_manual_override'];
    $manual_entity_id = $this->configuration['manual_entity_id'];

    $tdih_nodes = [];

    // 1) If "use manual override" is enabled and an entity is chosen, load that node.
    if ($manual_override && $manual_entity_id) {
      $node = Node::load($manual_entity_id);
      if ($node) {
        $tdih_nodes[] = $this->buildNodeItem($node);
      }
    }
    // 2) Otherwise, do automatic selection based on today's date.
    else {
      $today = new \DateTime('now', new \DateTimeZone('UTC'));
      $month = $today->format('m');
      $day = $today->format('d');

      // Use our NodeFetcher to load a set of matching nodes for today's date.
      $nodes = $this->nodeFetcher->loadTodayNodes($month, $day);
      if (!empty($nodes)) {
        $nodes_array = array_values($nodes);
        // Randomly pick one node.
        $selected_node = $nodes_array[array_rand($nodes_array)];
        $tdih_nodes[] = $this->buildNodeItem($selected_node);
      }
    }

    // Return a render array referencing our theme hook "tdih_block".
    return [
      '#theme' => 'tdih_block',
      '#tdih_nodes' => $tdih_nodes,
    ];
  }

  /**
   * Helper function to build an array of item data from a node, including image.
   */
  protected function buildNodeItem(Node $node) {
    // Fetch the value from your event date field, e.g. "field_this_day_in_history_3".
    $raw_date = $node->get('field_this_day_in_history_3')->value;
    $event_timestamp = 0;
    if (!empty($raw_date)) {
      // Convert to a timestamp (assuming UTC storage).
      $dt = new \DateTime($raw_date, new \DateTimeZone('UTC'));
      $event_timestamp = $dt->getTimestamp();
    }

    // If there's an image field named "field_event_image," generate a URL.
    $image_url = '';
    if ($node->hasField('field_event_image') && !$node->get('field_event_image')->isEmpty()) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $node->get('field_event_image')->entity;
      if ($file) {
        $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
      }
    }

    // Return a data array, including the event_date timestamp and image.
    return [
      'id' => $node->id(),
      'title' => $node->label(),
      'url' => $node->toUrl()->toString(),
      'event_date' => $event_timestamp, // <-- Use this in Twig with |date filter
      'image' => $image_url,
      // If you still want the fully rendered 'teaser':
      'rendered' => $this->renderNode($node, 'teaser'),
    ];
  }

  /**
   * Helper function to render a node in a specified view mode.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node to render.
   * @param string $view_mode
   *   The view mode (e.g. 'teaser', 'full').
   *
   * @return array
   *   A render array for the node.
   */
  protected function renderNode(Node $node, $view_mode = 'teaser') {
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    return $view_builder->view($node, $view_mode);
  }

}
