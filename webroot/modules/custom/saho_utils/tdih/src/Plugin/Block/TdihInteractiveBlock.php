<?php

namespace Drupal\tdih\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\tdih\Service\NodeFetcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
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
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

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
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    NodeFetcher $nodeFetcher,
    FileUrlGeneratorInterface $file_url_generator,
    FormBuilderInterface $form_builder,
    TimeInterface $time,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nodeFetcher = $nodeFetcher;
    $this->fileUrlGenerator = $file_url_generator;
    $this->formBuilder = $form_builder;
    $this->time = $time;
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
      $container->get('file_url_generator'),
      $container->get('form_builder'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
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
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
    $this->configuration['show_date_picker'] = $form_state->getValue('show_date_picker');
    $this->configuration['show_today_history'] = $form_state->getValue('show_today_history');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Use the site's configured timezone instead of hardcoding UTC.
    $config = \Drupal::config('system.date');
    $timezone = $config->get('timezone.default') ?: 'UTC';

    // Calculate seconds until midnight in the site's timezone.
    $now = $this->time->getCurrentTime();
    // Calculate the timestamp for midnight tonight in the site's timezone.
    $midnight = new \DateTime('now', new \DateTimeZone($timezone));
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
    $tags[] = 'tdih_interactive_block';

    // Add node_list:event tag to invalidate when event nodes are updated.
    $tags[] = 'node_list:event';

    return $tags;
  }

  /**
   * AJAX callback to update the events display.
   */
  public static function updateEvents(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Get the selected date from the form state.
    $date_value = $form_state->getValue('birthday_date');
    if (!empty($date_value)) {
      // DrupalDateTime automatically uses the site's timezone configuration..
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
        // Use the site's configured timezone instead of hardcoding UTC.
        $config = \Drupal::config('system.date');
        $timezone = $config->get('timezone.default') ?: 'UTC';
        $dt = new \DateTime($raw_date, new \DateTimeZone($timezone));
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

      // Get the body text, strip HTML tags, and decode HTML entities.
      $body_text = '';
      if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
        // Strip all HTML tags and decode HTML entities to prevent them from being displayed as plain text.
        $body_text = html_entity_decode(strip_tags($node->get('body')->processed));
      }

      // Build the item array.
      $items[] = [
        'id' => $node->id(),
        'title' => $node->label(),
        'url' => $node->toUrl()->toString(),
        'event_date' => $event_timestamp,
        'image' => $image_url,
        // Add alt text for accessibility.
        'image_alt' => $node->label(),
        'body' => $body_text,
        // Mark body as safe HTML to ensure proper rendering of images and formatting.
        'body_format' => 'full_html',
      ];
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get today's date using the time service.
    $timestamp = $this->time->getCurrentTime();
    // Use the site's configured timezone instead of hardcoding UTC.
    $config = \Drupal::config('system.date');
    $timezone = $config->get('timezone.default') ?: 'UTC';
    $date = new \DateTime('now', new \DateTimeZone($timezone));
    $month = $date->format('m');
    $day = $date->format('d');

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
      $form = $this->formBuilder->getForm('Drupal\tdih\Form\BirthdayDateForm');
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
      // Use the site's configured timezone instead of hardcoding UTC.
      $config = \Drupal::config('system.date');
      $timezone = $config->get('timezone.default') ?: 'UTC';
      $dt = new \DateTime($raw_date, new \DateTimeZone($timezone));
      $event_timestamp = $dt->getTimestamp();
    }

    // If there's an image field named "field_event_image," generate a URL.
    $image_url = '';
    if ($node->hasField('field_event_image') && !$node->get('field_event_image')->isEmpty()) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $node->get('field_event_image')->entity;
      if ($file) {
        $image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    // Get the body text, strip HTML tags, and decode HTML entities.
    $body_text = '';
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      // Strip all HTML tags and decode HTML entities to prevent them from being displayed as plain text.
      $body_text = html_entity_decode(strip_tags($node->get('body')->processed));
    }

    // Return a data array.
    return [
      'id' => $node->id(),
      'title' => $node->label(),
      'url' => $node->toUrl()->toString(),
      'event_date' => $event_timestamp,
      'image' => $image_url,
      // Add alt text for accessibility.
      'image_alt' => $node->label(),
      'body' => $body_text,
      // Mark body as safe HTML to ensure proper rendering of images and formatting.
      'body_format' => 'full_html',
    ];
  }

}