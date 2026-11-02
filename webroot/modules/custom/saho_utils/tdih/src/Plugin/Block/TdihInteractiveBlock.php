<?php

namespace Drupal\tdih\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\NodeInterface;
use Drupal\tdih\Excerpt;
use Drupal\tdih\Service\NodeFetcher;
use Drupal\tdih\SouthAfricanDay;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\Cache;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * Ruled "Also on this day" entries shown under the lead on the home aside.
   */
  public const MORE_LIMIT = 3;

  /**
   * Character budget for the lead synopsis.
   */
  public const SYNOPSIS_LENGTH = 220;

  /**
   * People links shown under the lead synopsis.
   */
  public const PEOPLE_LIMIT = 3;

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
   * @param \Symfony\Component\HttpFoundation\RequestStack|null $request_stack
   *   The request stack (for the shareable ?date=MM-DD parameter).
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    NodeFetcher $nodeFetcher,
    FileUrlGeneratorInterface $file_url_generator,
    FormBuilderInterface $form_builder,
    TimeInterface $time,
    ?RequestStack $request_stack = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nodeFetcher = $nodeFetcher;
    $this->fileUrlGenerator = $file_url_generator;
    $this->formBuilder = $form_builder;
    $this->time = $time;
    $this->requestStack = $request_stack;
  }

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|null
   */
  protected $requestStack;

  /**
   * Validates a shareable ?date=MM-DD value.
   *
   * Accepts exactly two-digit month and day (leap day allowed, so 02-29 is
   * a valid pattern); anything else - a year, single digits, junk - is NULL.
   *
   * @param string|null $raw
   *   The raw query value.
   *
   * @return string|null
   *   The normalised MM-DD pattern, or NULL when not a valid day of year.
   */
  public static function parseSharedDate(?string $raw): ?string {
    if ($raw === NULL || !preg_match('/^(\d{2})-(\d{2})$/', trim($raw), $m)) {
      return NULL;
    }
    $month = (int) $m[1];
    $day = (int) $m[2];
    // 2024 is a leap year so 29 February validates; year is otherwise unused.
    if ($month < 1 || $month > 12 || $day < 1 || !checkdate($month, $day, 2024)) {
      return NULL;
    }
    return sprintf('%02d-%02d', $month, $day);
  }

  /**
   * Builds the share URL for a day-of-year on the current page.
   *
   * Form AJAX posts back to the page the form sits on, so the current path
   * is the page both at build time and inside the AJAX callbacks.
   */
  public static function shareUrl(string $month_day_pattern): string {
    $path = \Drupal::service('path.current')->getPath();
    $alias = \Drupal::service('path_alias.manager')->getAliasByPath($path);
    return $alias . '?date=' . $month_day_pattern;
  }

  /**
   * Builds the birthday/day-of-year events render array.
   *
   * Shared by the AJAX callback (with a year: exact matches split out) and
   * the server-side ?date= render (no year: every event on that day).
   *
   * @param string $month_day_pattern
   *   MM-DD.
   * @param int|null $year
   *   The visitor's year, or NULL for a plain day-of-year listing.
   *
   * @return array
   *   A tdih_birthday_events render array.
   */
  public static function buildBirthdayEvents(string $month_day_pattern, ?int $year): array {
    $node_fetcher = \Drupal::service('tdih.node_fetcher');
    $birth_date = $year !== NULL
      ? sprintf('%04d-%s', $year, $month_day_pattern)
      : (new \DateTime('now', new \DateTimeZone('Africa/Johannesburg')))->format('Y') . '-' . $month_day_pattern;

    $nodes = $node_fetcher->loadAllBirthdayEvents($month_day_pattern);
    $exact_match_items = [];
    $same_day_items = [];
    foreach ($nodes as $node) {
      $item = self::buildNodeItems([$node])[0] ?? NULL;
      if (!$item || empty($item['raw_date'])) {
        continue;
      }
      if ($year !== NULL && $item['raw_date'] === $birth_date) {
        $exact_match_items[] = $item;
      }
      elseif (preg_match('/\d{4}-(\d{2})-(\d{2})/', $item['raw_date'], $matches)) {
        if ($matches[1] . '-' . $matches[2] === $month_day_pattern) {
          $same_day_items[] = $item;
        }
      }
    }
    usort($exact_match_items, fn($a, $b) => $a['event_date'] <=> $b['event_date']);
    usort($same_day_items, fn($a, $b) => $a['event_date'] <=> $b['event_date']);

    return [
      '#theme' => 'tdih_birthday_events',
      '#exact_match_events' => $exact_match_items,
      '#same_day_events' => $same_day_items,
      '#birth_date' => $birth_date,
      '#month_day_pattern' => $month_day_pattern,
      '#selected_year' => $year,
      '#share_url' => self::shareUrl($month_day_pattern),
      '#attributes' => [
        'class' => ['tdih-events-container'],
      ],
    ];
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
      $container->get('datetime.time'),
      $container->get('request_stack')
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
      'show_more_link' => TRUE,
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
          ':input[name="settings[show_date_picker]"]' => ['checked' => TRUE],
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

    $form['show_more_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show "View more events from this day" link'),
      '#description' => $this->t('Enable to show the link to the This Day in History page under the events.'),
      '#default_value' => $this->configuration['show_more_link'],
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
    $this->configuration['show_more_link'] = $form_state->getValue('show_more_link');
    $this->configuration['use_todays_date'] = $form_state->getValue('use_todays_date');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // The shareable ?date=MM-DD parameter renders a different aside.
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.query_args:date']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // The register turns over at South African midnight; never below 60 s
    // so a page built just before midnight does not thrash the cache.
    return SouthAfricanDay::secondsUntilMidnight($this->time->getCurrentTime());
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
      $month_day_pattern = sprintf('%02d-%02d', (int) $selected_month, (int) $selected_day);
      $events_html = self::buildBirthdayEvents($month_day_pattern, (int) $selected_year);

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

      // Route through saho_hero_mobile (480w WebP) so the LCP-region TDIH
      // image is ~50 KB instead of the raw 600+ KB jpg. The file_exists
      // guard keeps the missing-file backlog from emitting broken frames.
      $image_url = '';
      if ($node->hasField('field_event_image') && !$node->get('field_event_image')->isEmpty()) {
        /** @var \Drupal\file\FileInterface $file */
        $file = $node->get('field_event_image')->entity;
        if ($file && file_exists($file->getFileUri())) {
          $uri = $file->getFileUri();
          $style = ImageStyle::load('saho_hero_mobile');
          $image_url = $style ? $style->buildUrl($uri) : \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
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
    $lead_event = NULL;
    $more_events = [];
    if ($this->configuration['show_today_history']) {
      // Editorial curation picks the lead: an event ticked "home page
      // feature" with an image. The ruled "Also on this day" entries under
      // it come from the whole day, not just the curated pool - most days
      // carry no, or one, curated event and the aside used to show that
      // single entry next to a feature card twice its height (#460).
      $featured = $this->matchingItems($this->nodeFetcher->loadPotentialEvents($target_date), $target_date);
      $all = $this->matchingItems($this->nodeFetcher->loadAllBirthdayEvents($target_date), $target_date);
      [$lead_event, $more_events] = self::arrangeDay($featured, $all, self::MORE_LIMIT);
      $all_events = $lead_event ? array_merge([$lead_event], $more_events) : [];
    }

    // A shared ?date=MM-DD link renders that day's events server-side and
    // opens the picker on it (R3 #478: shareable birthday result).
    $shared_pattern = NULL;
    $shared_events = [];
    $request = $this->requestStack?->getCurrentRequest();
    if ($request && $this->configuration['show_date_picker']) {
      $shared_pattern = self::parseSharedDate($request->query->get('date'));
      // In day_month mode the picker below pre-fills to the shared date and
      // renders that day's events itself (with its own share link), so a
      // second server-side render here would duplicate the whole list. Only
      // the birthday picker, which does not pre-fill from the shared pattern,
      // needs the events rendered separately.
      if ($shared_pattern !== NULL && $this->configuration['date_picker_mode'] !== 'day_month') {
        $shared_events = self::buildBirthdayEvents($shared_pattern, NULL);
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
      if ($shared_pattern !== NULL) {
        $default_month = substr($shared_pattern, 0, 2);
        $default_day = (int) substr($shared_pattern, 3, 2);
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
      '#lead_event' => $lead_event,
      '#more_events' => $more_events,
      '#target_date' => $target_date,
      '#date_picker_form' => $form,
      '#date_picker_mode' => $this->configuration['date_picker_mode'],
      '#display_mode' => $this->configuration['display_mode'],
      '#show_header_title' => $this->configuration['show_header_title'],
      '#show_today_history' => $show_events,
      '#show_details_button' => $this->configuration['show_details_button'],
      '#show_more_link' => $this->configuration['show_more_link'] ?? TRUE,
      '#use_todays_date' => $this->configuration['use_todays_date'],
      '#picker_open' => $shared_pattern !== NULL ? TRUE : ($this->configuration['picker_open'] ?? NULL),
      '#shared_date_events' => $shared_events,
      '#attached' => [
        'library' => [
          'tdih/tdih-interactive',
        ],
      ],
    ];
  }

  /**
   * Keeps the items whose event date falls on the target month-day.
   *
   * The fetcher's LIKE '%-MM-DD' is a potential match (it also matches a
   * YYYY-MM-DD HH:MM value or a -MM-DD inside a longer string), so the
   * exact month-day is re-checked here.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   Loaded event nodes.
   * @param string $target_date
   *   MM-DD.
   *
   * @return array[]
   *   Item arrays (see buildNodeItem()), keyed by nid.
   */
  protected function matchingItems(array $nodes, string $target_date): array {
    $items = [];
    foreach ($nodes as $node) {
      $item = $this->buildNodeItem($node);
      if (!empty($item['raw_date'])
        && preg_match('/\d{4}-(\d{2})-(\d{2})/', $item['raw_date'], $matches)
        && $matches[1] . '-' . $matches[2] === $target_date) {
        $items[$item['id']] = $item;
      }
    }
    return $items;
  }

  /**
   * Splits a day's events into the lead card and the "Also on" ledger.
   *
   * Lead preference: a curated (home page feature) event with an image,
   * then any curated event, then any event with an image, then the first
   * event. The ledger is everything else in chronological order, capped at
   * $limit; when the day holds more than that the pick rotates (the block
   * is render-cached until midnight, so one shuffle per day).
   *
   * @param array[] $featured
   *   Curated item arrays keyed by nid (may be empty).
   * @param array[] $all
   *   Every item array for the day keyed by nid (superset of $featured).
   * @param int $limit
   *   Maximum ledger entries.
   *
   * @return array
   *   [lead item or NULL, ledger items].
   */
  public static function arrangeDay(array $featured, array $all, int $limit = self::MORE_LIMIT): array {
    // The curated pool is a subset of the day; make sure the lead candidates
    // exist in $all even if the two caches drifted apart for a moment.
    $pool = $all + $featured;
    if (!$pool) {
      return [NULL, []];
    }
    $has_image = static fn (array $item): bool => !empty($item['image']);
    $lead = current(array_filter($featured, $has_image))
      ?: current($featured)
      ?: current(array_filter($pool, $has_image))
      ?: current($pool);
    $lead['featured'] = isset($featured[$lead['id']]);

    $rest = $pool;
    unset($rest[$lead['id']]);
    $rest = array_values($rest);
    if (count($rest) > $limit) {
      shuffle($rest);
      $rest = array_slice($rest, 0, $limit);
    }
    usort($rest, static fn (array $a, array $b): int => $a['event_date'] <=> $b['event_date']);

    return [$lead, $rest];
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

    // Route through saho_hero_mobile (480w WebP) so the LCP-region TDIH
    // image is ~50 KB instead of the raw 600+ KB jpg. The file_exists
    // guard keeps the missing-file backlog from emitting broken frames.
    $image_url = '';
    if ($node->hasField('field_event_image') && !$node->get('field_event_image')->isEmpty()) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $node->get('field_event_image')->entity;
      if ($file && file_exists($file->getFileUri())) {
        $uri = $file->getFileUri();
        $style = ImageStyle::load('saho_hero_mobile');
        $image_url = $style ? $style->buildUrl($uri) : $this->fileUrlGenerator->generateAbsoluteString($uri);
      }
    }

    // Get the body text, strip HTML tags, and decode HTML entities.
    $body_text = '';
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      // Strip all HTML tags and decode HTML entities to prevent them from being
      // displayed as plain text.
      $body_text = html_entity_decode(strip_tags($node->get('body')->processed));
    }

    // The lead card's synopsis: the curated field_synopsis when an editor
    // wrote one, else the opening sentences of the body.
    $synopsis = '';
    if ($node->hasField('field_synopsis') && !$node->get('field_synopsis')->isEmpty()) {
      $synopsis = Excerpt::sentences((string) $node->get('field_synopsis')->value, self::SYNOPSIS_LENGTH);
    }
    if ($synopsis === '' && $body_text !== '') {
      $synopsis = Excerpt::sentences($body_text, self::SYNOPSIS_LENGTH);
    }

    // People in this event (field_people_related_tab), published only.
    $people = [];
    if ($node->hasField('field_people_related_tab') && !$node->get('field_people_related_tab')->isEmpty()) {
      foreach ($node->get('field_people_related_tab')->referencedEntities() as $person) {
        if ($person instanceof NodeInterface && $person->isPublished()) {
          $people[] = [
            'title' => $person->label(),
            'url' => $person->toUrl()->toString(),
          ];
          if (count($people) >= self::PEOPLE_LIMIT) {
            break;
          }
        }
      }
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
      'synopsis' => $synopsis,
      'people' => $people,
      'featured' => FALSE,
    ];
  }

}
