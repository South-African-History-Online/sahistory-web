<?php

namespace Drupal\tdih\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tdih\Service\NodeFetcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides an interactive TDIH Block with date picker.
 *
 * @Block(
 *   id = "tdih_interactive_block",
 *   admin_label = @Translation("TDIH Interactive Block"),
 *   category = @Translation("All custom")
 * )
 */
class TdihInteractiveBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The NodeFetcher service used to load nodes for specific dates.
   *
   * @var \Drupal\tdih\Service\NodeFetcher
   */
  protected $nodeFetcher;

  /**
   * Constructs a new TdihInteractiveBlock instance.
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
    return [
      'max_items' => 5,
      'display_mode' => 'compact',
      'show_date_picker' => TRUE,
      'show_today_history' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['max_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of items'),
      '#description' => $this->t('The maximum number of events to display.'),
      '#default_value' => $this->configuration['max_items'],
      '#min' => 1,
      '#max' => 20,
    ];

    $form['display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display mode'),
      '#description' => $this->t('How to display the events.'),
      '#options' => [
        'compact' => $this->t('Compact (title and date only)'),
        'full' => $this->t('Full (with image and description)'),
      ],
      '#default_value' => $this->configuration['display_mode'],
    ];

    $form['show_date_picker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show date picker'),
      '#description' => $this->t('Enable to show the date picker for "What happened on your birthday" feature.'),
      '#default_value' => $this->configuration['show_date_picker'],
    ];

    $form['show_today_history'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Today in history section'),
      '#description' => $this->t('Enable to show the "Today in history" section with events from the current date.'),
      '#default_value' => $this->configuration['show_today_history'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['max_items'] = $form_state->getValue('max_items');
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
    $this->configuration['show_date_picker'] = $form_state->getValue('show_date_picker');
    $this->configuration['show_today_history'] = $form_state->getValue('show_today_history');
  }

  /**
   * AJAX callback to update the events display.
   */
  public static function updateEvents(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Get the selected date from the form state.
    $date_value = $form_state->getValue('birthday_date');
    if (!empty($date_value)) {
      $date = new DrupalDateTime($date_value);
      $month = $date->format('m');
      $day = $date->format('d');

      // Get the NodeFetcher service.
      $node_fetcher = \Drupal::service('tdih.node_fetcher');

      // Load nodes for the selected date.
      $nodes = $node_fetcher->loadDateNodes($month, $day, 10);

      // Build the render array for the events.
      $events_html = [
        '#theme' => 'tdih_events',
        '#tdih_nodes' => self::buildNodeItems($nodes),
        '#attributes' => [
          'class' => ['tdih-events-container'],
        ],
      ];

      // Replace the events container with the new content.
      $response->addCommand(new ReplaceCommand('.tdih-events-container', \Drupal::service('renderer')->render($events_html)));
    }

    return $response;
  }

  /**
   * Helper function to build node items for rendering.
   *
   * @param array $nodes
   *   Array of node objects.
   *
   * @return array
   *   Array of node data for rendering.
   */
  public static function buildNodeItems(array $nodes) {
    $items = [];

    foreach ($nodes as $node) {
      // Get the event date.
      $raw_date = $node->get('field_this_day_in_history_3')->value;
      $event_timestamp = 0;
      if (!empty($raw_date)) {
        // Convert to a timestamp (assuming UTC storage).
        $dt = new \DateTime($raw_date, new \DateTimeZone('UTC'));
        $event_timestamp = $dt->getTimestamp();
      }

      // Get the image URL if available.
      $image_url = '';
      if ($node->hasField('field_event_image') && !$node->get('field_event_image')->isEmpty()) {
        /** @var \Drupal\file\FileInterface $file */
        $file = $node->get('field_event_image')->entity;
        if ($file) {
          $file_url_generator = \Drupal::service('file_url_generator');
          $image_url = $file_url_generator->generateAbsoluteString($file->getFileUri());
        }
      }

      // Build the item array.
      $items[] = [
        'id' => $node->id(),
        'title' => $node->label(),
        'url' => $node->toUrl()->toString(),
        'event_date' => $event_timestamp,
        'image' => $image_url,
        'body' => $node->hasField('body') ? $node->get('body')->value : '',
      ];
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get today's date.
    $today = new \DateTime('now', new \DateTimeZone('UTC'));
    $month = $today->format('m');
    $day = $today->format('d');

    // Load nodes for today's date if the Today in history section is enabled.
    $tdih_nodes = [];
    if ($this->configuration['show_today_history']) {
      $nodes = $this->nodeFetcher->loadTodayNodes($month, $day);

      if (!empty($nodes)) {
        // Build the node items for rendering.
        foreach ($nodes as $node) {
          $tdih_nodes[] = $this->buildNodeItem($node);
        }
      }
    }

    // Build the date picker form if enabled.
    $form = [];
    if ($this->configuration['show_date_picker']) {
      $form = \Drupal::formBuilder()->getForm('Drupal\tdih\Form\BirthdayDateForm');
    }

    // Return a render array referencing our theme hook.
    return [
      '#theme' => 'tdih_interactive_block',
      '#tdih_nodes' => $tdih_nodes,
      '#date_picker_form' => $form,
      '#display_mode' => $this->configuration['display_mode'],
      '#show_today_history' => $this->configuration['show_today_history'],
      '#attached' => [
        'library' => [
          'tdih/tdih-interactive',
        ],
      ],
    ];
  }

  /**
   * Helper function to build an array of item data from node, including image.
   */
  protected function buildNodeItem($node) {
    // Fetch the value from your event date field.
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
        $file_url_generator = \Drupal::service('file_url_generator');
        $image_url = $file_url_generator->generateAbsoluteString($file->getFileUri());
      }
    }

    // Return a data array.
    return [
      'id' => $node->id(),
      'title' => $node->label(),
      'url' => $node->toUrl()->toString(),
      'event_date' => $event_timestamp,
      'image' => $image_url,
      'body' => $node->hasField('body') ? $node->get('body')->value : '',
    ];
  }

}