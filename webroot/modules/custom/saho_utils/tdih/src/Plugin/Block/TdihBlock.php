<?php

namespace Drupal\tdih\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Drupal\tdih\Service\NodeFetcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a This Day In History Block with manual override config.
 *
 * Displays historical events that occurred on today's date, or allows
 * administrators to manually select a specific event to display.
 *
 * @Block(
 *   id = "tdih_block",
 *   admin_label = @Translation("TDIH Block"),
 *   category = @Translation("All custom")
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    NodeFetcher $nodeFetcher,
    EntityTypeManagerInterface $entity_type_manager,
    TimeInterface $time,
    FileUrlGeneratorInterface $file_url_generator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nodeFetcher = $nodeFetcher;
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tdih.node_fetcher'),
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
      $container->get('file_url_generator')
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
      'button_block_id' => NULL,
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
        ? $this->entityTypeManager->getStorage('node')->load($this->configuration['manual_entity_id'])
        : NULL,
      '#states' => [
        'visible' => [
          ':input[name="use_manual_override"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['button_block_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Button Block'),
      '#description' => $this->t('Select a button block to display at the bottom of the TDIH block. Leave empty for no button.'),
      '#target_type' => 'block_content',
      '#selection_handler' => 'default:block_content',
      '#selection_settings' => [
        'target_bundles' => ['button'],
      ],
      '#default_value' => ($this->configuration['button_block_id'])
        ? $this->entityTypeManager->getStorage('block_content')->load($this->configuration['button_block_id'])
        : NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Handles form submissions for this block's configuration.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['use_manual_override'] = $form_state->getValue('use_manual_override');
    $this->configuration['manual_entity_id'] = $form_state->getValue('manual_entity_id');
    $this->configuration['button_block_id'] = $form_state->getValue('button_block_id');
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

    try {
      // 1) If "use manual override" is enabled and an entity is chosen,
      // load that node.
      if ($manual_override && $manual_entity_id) {
        $node = $this->entityTypeManager->getStorage('node')->load($manual_entity_id);
        if ($node) {
          $tdih_nodes[] = $this->buildNodeItem($node);
        }
      }
      // 2) Otherwise, do automatic selection based on today's date.
      else {
        // Force South African timezone for consistent TDIH functionality.
        $sa_timezone = new \DateTimeZone('Africa/Johannesburg');
        $today = new \DateTime('now', $sa_timezone);
        $month = $today->format('m');
        $day = $today->format('d');

        // Use our NodeFetcher to load a set of matching nodes for today's date.
        $target_date = $month . '-' . $day;
        $nodes = $this->nodeFetcher->loadPotentialEvents($target_date);

        // Filter nodes by exact date match and front page feature using simple
        // string operations.
        $filtered_nodes = [];
        foreach ($nodes as $node) {
          // Only process nodes that are featured on front page.
          if ($node->hasField('field_home_page_feature') && $node->get('field_home_page_feature')->value) {
            if ($node->hasField('field_this_day_in_history_3') && !$node->get('field_this_day_in_history_3')->isEmpty()) {
              $raw_date = $node->get('field_this_day_in_history_3')->value;
              if (!empty($raw_date)) {
                // Extract MM-DD from YYYY-MM-DD format.
                if (preg_match('/\d{4}-(\d{2})-(\d{2})/', $raw_date, $matches)) {
                  $item_date = $matches[1] . '-' . $matches[2];
                  if ($item_date === $target_date) {
                    $filtered_nodes[] = $node;
                  }
                }
              }
            }
          }
        }

        if (!empty($filtered_nodes)) {
          // Randomly pick one node from the filtered results.
          $selected_node = $filtered_nodes[array_rand($filtered_nodes)];
          $tdih_nodes[] = $this->buildNodeItem($selected_node);
        }
      }
    }
    catch (\Exception $e) {
      // Silently handle exceptions.
    }
    // Prepare the render array.
    $build = [
      '#theme' => 'tdih_block',
      '#tdih_nodes' => $tdih_nodes,
      // Add cache metadata to ensure the block updates at midnight.
      '#cache' => [
        'contexts' => $this->getCacheContexts(),
        'tags' => $this->getCacheTags(),
        'max-age' => $this->getCacheMaxAge(),
      ],
    ];

    // Add the button block if configured.
    if (!empty($this->configuration['button_block_id'])) {
      try {
        $button_block = $this->entityTypeManager->getStorage('block_content')
          ->load($this->configuration['button_block_id']);
        if ($button_block) {
          $view_builder = $this->entityTypeManager->getViewBuilder('block_content');
          $build['#button_block'] = $view_builder->view($button_block);
          // Add the button block's cache tags.
          $build['#cache']['tags'][] = 'block_content:' . $button_block->id();
        }
      }
      catch (\Exception $e) {
        // Silently handle exceptions.
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    if ($this->configuration['use_manual_override'] && $this->configuration['manual_entity_id']) {
      // If using manual override, use default cache max age.
      return Cache::PERMANENT;
    }

    // Force South African timezone for consistent TDIH functionality.
    $sa_timezone = new \DateTimeZone('Africa/Johannesburg');

    // Calculate seconds until midnight in South African timezone.
    $now = $this->time->getCurrentTime();
    // Calculate the timestamp for midnight tonight in SA timezone.
    $midnight = new \DateTime('now', $sa_timezone);
    $midnight->setTime(0, 0, 0);
    $midnight->modify('+1 day');
    $seconds_until_midnight = $midnight->getTimestamp() - $now;

    // Return seconds until midnight, or minimum 60 seconds to prevent
    // excessive cache rebuilds if we're very close to midnight.
    return max($seconds_until_midnight, 60);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();

    // Add cache tags for the block itself.
    $tags[] = 'tdih_block';

    // If using manual override, add the node's cache tag.
    if ($this->configuration['use_manual_override'] && $this->configuration['manual_entity_id']) {
      $tags[] = 'node:' . $this->configuration['manual_entity_id'];
    }
    else {
      // Add node_list:event tag to invalidate when event nodes are updated.
      $tags[] = 'node_list:event';
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    // Add user.permissions context to differentiate between anonymous and
    // authenticated users if needed.
    $contexts[] = 'user.permissions';

    return $contexts;
  }

  /**
   * Helper function to build an array of item data from node, including image.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity to build data from.
   *
   * @return array
   *   An array of data for the template, including id, title, url, event_date,
   *   image, and rendered node.
   */
  protected function buildNodeItem($node) {
    try {
      // Fetch the value from your event date field, e.g.
      // "field_this_day_in_history_3".
      $event_date = NULL;
      if ($node->hasField('field_this_day_in_history_3') && !$node->get('field_this_day_in_history_3')->isEmpty()) {
        $raw_date = $node->get('field_this_day_in_history_3')->value;

        if (!empty($raw_date)) {
          // Create DateTime at noon to avoid timezone boundary issues.
          // This prevents date shifting across different timezones.
          $event_date = new \DateTime($raw_date . ' 12:00:00',
            new \DateTimeZone('Africa/Johannesburg'));
        }
      }

      // If there's an image field named "field_event_image," generate a URL.
      $image_url = '';
      $image_alt = $node->label();
      if ($node->hasField('field_event_image') && !$node->get('field_event_image')->isEmpty()) {
        /** @var \Drupal\file\FileInterface $file */
        $file = $node->get('field_event_image')->entity;
        if ($file instanceof FileInterface) {
          // Get file URL generator service through dependency injection.
          $image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());

          // Try to get alt text from the image field if available.
          $image_item = $node->get('field_event_image')->first();
          // Access the alt text property safely.
          if ($image_item && !empty($image_item->getValue()['alt'])) {
            $image_alt = $image_item->getValue()['alt'];
          }
        }
      }
    }
    catch (\Exception $e) {
      // Silently handle exceptions.
    }

    // Get the body text, strip HTML tags, and decode HTML entities.
    $body_text = '';
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      // Strip all HTML tags and decode HTML entities to prevent them from being
      // displayed as plain text.
      $body_text = html_entity_decode(strip_tags($node->get('body')->value));
    }

    // Return a data array, including the event_date DateTime object
    // and image with alt text.
    return [
      'id' => $node->id(),
      'title' => strip_tags($node->label()),
      'url' => $node->toUrl()->toString(),
      // Use this in Twig with |date filter.
      'event_date' => $event_date,
      'image' => $image_url ?? '',
      'image_alt' => $image_alt ?? strip_tags($node->label()),
      'body' => $body_text,
      // If you still want the fully rendered 'teaser':
      'rendered' => $this->renderNode($node, 'teaser'),
    ];
  }

  /**
   * Helper function to render a node in a specified view mode.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to render.
   * @param string $view_mode
   *   The view mode (e.g. 'teaser', 'full').
   *
   * @return array
   *   A render array for the node.
   */
  protected function renderNode($node, $view_mode = 'teaser') {
    try {
      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      return $view_builder->view($node, $view_mode);
    }
    catch (\Exception $e) {
      return [];
    }
  }

}
