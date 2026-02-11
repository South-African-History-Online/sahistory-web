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
      'date_picker_mode' => 'full',
      'show_today_history' => TRUE,
      'show_details_button' => TRUE,
      'show_header_title' => TRUE,
      'use_todays_date' => FALSE,
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

    $form['show_header_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show "This day in history" header title'),
      '#description' => $this->t('Enable to show the "This day in history" title in the block header. If disabled, only the block title will be used.'),
      '#default_value' => $this->configuration['show_header_title'],
    ];

    $form['show_date_picker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show date picker'),
      '#description' => $this->t('Enable to show the date picker for "What happened on your birthday" feature.'),
      '#default_value' => $this->configuration['show_date_picker'],
    ];

    $form['date_picker_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Date picker mode'),
      '#description' => $this->t('Choose whether to show day/month only or full date picker with year.'),
      '#options' => [
        'day_month' => $this->t('Day and Month only'),
        'full' => $this->t('Full date with Year (birthday mode)'),
      ],
      '#default_value' => $this->configuration['date_picker_mode'],
      '#states' => [
        'visible' => [
          ':input[name="show_date_picker"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['show_today_history'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Today in history section'),
      '#description' => $this->t('Enable to show the "Today in history" section with events from the current date.'),
      '#default_value' => $this->configuration['show_today_history'],
    ];

    $form['show_details_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show "Read more" button'),
      '#description' => $this->t('Enable to show the "Read more" button that links to the displayed event.'),
      '#default_value' => $this->configuration['show_details_button'],
    ];

    $form['use_todays_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Pre-fill date picker with today's date"),
      '#description' => $this->t("Enable to automatically select today's date in the date picker when the block loads. Users can still change the date if desired."),
      '#default_value' => $this->configuration['use_todays_date'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
    $this->configuration['show_header_title'] = $form_state->getValue('show_header_title');
    $this->configuration['show_date_picker'] = $form_state->getValue('show_date_picker');
    $this->configuration['date_picker_mode'] = $form_state->getValue('date_picker_mode');
    $this->configuration['show_today_history'] = $form_state->getValue('show_today_history');
    $this->configuration['show_details_button'] = $form_state->getValue('show_details_button');
    $this->configuration['use_todays_date'] = $form_state->getValue('use_todays_date');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
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
    $tags[] = 'tdih_interactive_block';

    // Add node_list:event tag to invalidate when event nodes are updated.
    $tags[] = 'node_list:event';

    return $tags;
  }

  /**
   * AJAX callback to update the events display for birthday selection.
   */
  public static function updateEvents(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Get the selected date components from the form state.
    $selected_day = $form_state->getValue('birthday_day');
    $selected_month = $form_state->getValue('birthday_month');
    $selected_year = $form_state->getValue('birthday_year');

    if (!empty($selected_day) && !empty($selected_month) && !empty($selected_year)) {
      // Format the date components.
      $day = sprintf('%02d', (int) $selected_day);
      $month = sprintf('%02d', (int) $selected_month);
      $year = (int) $selected_year;

      // Get the NodeFetcher service via dependency injection.
      $node_fetcher = \Drupal::service('tdih.node_fetcher');

      // Create the full birth date and month-day pattern.
      $birth_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
      $month_day_pattern = sprintf('%02d-%02d', $month, $day);

      // Load all events for month-day combination - get ALL events
      // not just those featured on the home page.
      $nodes = $node_fetcher->loadAllBirthdayEvents($month_day_pattern);
      $exact_match_items = [];
      $same_day_items = [];

      // Separate exact date matches from same month-day matches.
      foreach ($nodes as $node) {
        $item = self::buildNodeItems([$node])[0] ?? NULL;
        if ($item && !empty($item['raw_date'])) {
          // Check if this is an exact date match (same year, month, day).
          if ($item['raw_date'] === $birth_date) {
            $exact_match_items[] = $item;
          }
          // Check if this is same month-day but different year.
          elseif (preg_match('/\d{4}-(\d{2})-(\d{2})/', $item['raw_date'], $matches)) {
            $item_month_day = $matches[1] . '-' . $matches[2];
            if ($item_month_day === $month_day_pattern) {
              $same_day_items[] = $item;
            }
          }
        }
      }

      // Sort both arrays chronologically (oldest first).
      usort($exact_match_items, function ($a, $b) {
        return $a['event_date'] <=> $b['event_date'];
      });
      usort($same_day_items, function ($a, $b) {
        return $a['event_date'] <=> $b['event_date'];
      });

      // Build the render array for the birthday events.
      $events_html = [
        '#theme' => 'tdih_birthday_events',
        '#exact_match_events' => $exact_match_items,
        '#same_day_events' => $same_day_items,
        '#birth_date' => $birth_date,
        '#month_day_pattern' => $month_day_pattern,
        '#selected_year' => $year,
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
      $raw_date = $node->get('field_event_date')->value;
      $event_timestamp = 0;
      if (!empty($raw_date)) {
        // Parse date as-is without timezone conversion to avoid shifting.
        // The raw_date should be in YYYY-MM-DD format.
        $dt = new \DateTime($raw_date);
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
        // Strip all HTML tags and decode HTML entities to prevent them from
        // being displayed as plain text.
        $body_text = html_entity_decode(strip_tags($node->get('body')->processed));
      }

      // Build the item array.
      $items[] = [
        'id' => $node->id(),
        'title' => $node->label(),
        'url' => $node->toUrl()->toString(),
        'event_date' => $event_timestamp,
        'raw_date' => $raw_date,
        'image' => $image_url,
        // Add alt text for accessibility.
        'image_alt' => $node->label(),
        'body' => $body_text,
        // Mark body as safe HTML to ensure proper rendering of images
        // and formatting.
        'body_format' => 'full_html',
      ];
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Force South African timezone for accurate "Today in history".
    $sa_timezone = new \DateTimeZone('Africa/Johannesburg');
    $date = new \DateTime('now', $sa_timezone);
    $month = $date->format('m');
    $day = $date->format('d');

    // Load potential events and let Twig filter by exact date.
    $all_events = [];
    $target_date = $month . '-' . $day;

    // Only auto-load events if "show_today_history" is enabled.
    if ($this->configuration['show_today_history']) {
      $nodes = $this->nodeFetcher->loadPotentialEvents($target_date);

      if (!empty($nodes)) {
        // Build node items for rendering and filter by exact date.
        foreach ($nodes as $node) {
          $item = $this->buildNodeItem($node);

          // Check if this item matches target date using strings.
          if (!empty($item['raw_date'])) {
            // Extract MM-DD from YYYY-MM-DD format.
            if (preg_match('/\d{4}-(\d{2})-(\d{2})/', $item['raw_date'], $matches)) {
              $item_date = $matches[1] . '-' . $matches[2];
              if ($item_date === $target_date) {
                $all_events[] = $item;
              }
            }
          }
        }

        // Limit to only one random event for display.
        if (count($all_events) > 1) {
          $random_event = $all_events[array_rand($all_events)];
          $all_events = [$random_event];
        }
      }
    }

    // Build the date picker form if enabled.
    $form = [];
    if ($this->configuration['show_date_picker']) {
      // Pre-fill with today's date if enabled.
      $default_day = NULL;
      $default_month = NULL;
      if ($this->configuration['use_todays_date']) {
        $today = new \DateTime('now', $sa_timezone);
        $default_day = (int) $today->format('d');
        $default_month = $today->format('m');
      }

      if ($this->configuration['date_picker_mode'] === 'day_month') {
        // Use simplified day/month form with optional defaults.
        $form = $this->formBuilder->getForm(
          'Drupal\tdih\Form\DayMonthDateForm',
          $default_day,
          $default_month
        );
      }
      else {
        // Use full birthday form with year.
        $form = $this->formBuilder->getForm('Drupal\tdih\Form\BirthdayDateForm');
      }
    }

    // Determine if events should be shown.
    $show_events = $this->configuration['show_today_history'];

    // Return a render array referencing our theme hook.
    return [
      '#theme' => 'tdih_interactive_block',
      '#all_events' => $all_events,
      '#target_date' => $target_date,
      '#date_picker_form' => $form,
      '#date_picker_mode' => $this->configuration['date_picker_mode'],
      '#display_mode' => $this->configuration['display_mode'],
      '#show_header_title' => $this->configuration['show_header_title'],
      '#show_today_history' => $show_events,
      '#show_details_button' => $this->configuration['show_details_button'],
      '#use_todays_date' => $this->configuration['use_todays_date'],
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
    $raw_date = $node->get('field_event_date')->value;
    $event_timestamp = 0;
    if (!empty($raw_date)) {
      // Parse date as-is without timezone conversion to avoid shifting.
      // The raw_date should be in YYYY-MM-DD format.
      $dt = new \DateTime($raw_date);
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
      // Strip all HTML tags and decode HTML entities to prevent them from being
      // displayed as plain text.
      $body_text = html_entity_decode(strip_tags($node->get('body')->processed));
    }

    // Return a data array.
    return [
      'id' => $node->id(),
      'title' => $node->label(),
      'url' => $node->toUrl()->toString(),
      'event_date' => $event_timestamp,
      'raw_date' => $raw_date,
      'image' => $image_url,
      // Add alt text for accessibility.
      'image_alt' => $node->label(),
      'body' => $body_text,
      // Mark body as safe HTML to ensure proper rendering of images
      // and formatting.
      'body_format' => 'full_html',
    ];
  }

}
