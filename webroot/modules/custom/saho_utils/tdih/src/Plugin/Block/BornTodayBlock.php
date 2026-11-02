<?php

declare(strict_types=1);

namespace Drupal\tdih\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_utils\Service\EntityItemBuilderService;
use Drupal\tdih\Excerpt;
use Drupal\tdih\Service\NodeFetcher;
use Drupal\tdih\SouthAfricanDay;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Born on this day: biographies whose birth date is today's month-day.
 *
 * A quiet second register under This Day in History on the home aside. It
 * brings the biography register onto the front page on the same "today"
 * footing as the events, from field_drupal_birth_date (date-only).
 *
 * @Block(
 *   id = "tdih_born_today",
 *   admin_label = @Translation("TDIH Born on this day"),
 *   category = @Translation("All custom")
 * )
 */
final class BornTodayBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Portraits shown; the rest of the day sits behind the register link.
   */
  public const LIMIT = 3;

  /**
   * Character budget for the one-line description under each name.
   */
  public const LINE_LENGTH = 90;

  /**
   * Constructs the block.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\tdih\Service\NodeFetcher $nodeFetcher
   *   The TDIH node fetcher.
   * @param \Drupal\saho_utils\Service\EntityItemBuilderService $itemBuilder
   *   The shared item builder (portrait derivatives).
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly NodeFetcher $nodeFetcher,
    private readonly EntityItemBuilderService $itemBuilder,
    private readonly TimeInterface $time,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tdih.node_fetcher'),
      $container->get('saho_utils.entity_item_builder'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'limit' => self::LIMIT,
      'portraits_only' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);
    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Biographies shown'),
      '#min' => 1,
      '#max' => 6,
      '#default_value' => $this->configuration['limit'],
    ];
    $form['portraits_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prefer biographies with a portrait'),
      '#description' => $this->t('Biographies without a picture only fill the row when there are not enough with one.'),
      '#default_value' => $this->configuration['portraits_only'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $this->configuration['limit'] = (int) $form_state->getValue('limit');
    $this->configuration['portraits_only'] = (bool) $form_state->getValue('portraits_only');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $month_day = SouthAfricanDay::monthDay($this->time->getCurrentTime());
    $items = [];
    foreach ($this->nodeFetcher->loadBirthdaysOnDay($month_day) as $node) {
      $item = $this->buildItem($node, $month_day);
      if ($item !== NULL) {
        $items[] = $item;
      }
    }
    $people = self::pick($items, (int) $this->configuration['limit'], (bool) $this->configuration['portraits_only']);

    if (!$people) {
      // Nothing born today: render nothing rather than an empty register.
      // The cache metadata still applies so tomorrow's page rebuilds.
      return [
        '#cache' => [
          'tags' => $this->getCacheTags(),
          'max-age' => $this->getCacheMaxAge(),
        ],
      ];
    }

    return [
      '#theme' => 'tdih_born_today',
      '#people' => $people,
      '#month_day' => $month_day,
      // No foot link: the names are the links and the main nav carries the
      // biography register; the aside stays within the feature's height.
      '#more_url' => NULL,
      '#attached' => ['library' => ['tdih/tdih-born-today']],
    ];
  }

  /**
   * Chooses which biographies fill the row.
   *
   * Portraits first (the row is a strip of faces), then the rest; within
   * each group the pick rotates once a day when the day holds more than
   * the limit, and the final row reads oldest birth first.
   *
   * @param array[] $items
   *   Item arrays from buildItem().
   * @param int $limit
   *   Maximum items.
   * @param bool $portraits_first
   *   Whether to prefer items with an image.
   *
   * @return array[]
   *   The chosen items in birth-year order.
   */
  public static function pick(array $items, int $limit, bool $portraits_first = TRUE): array {
    $limit = max(1, $limit);
    $with = array_values(array_filter($items, static fn (array $i): bool => !empty($i['image'])));
    $without = array_values(array_filter($items, static fn (array $i): bool => empty($i['image'])));
    $ordered = $portraits_first ? [$with, $without] : [$items];

    $chosen = [];
    foreach ($ordered as $group) {
      $room = $limit - count($chosen);
      if ($room <= 0) {
        break;
      }
      if (count($group) > $room) {
        shuffle($group);
        $group = array_slice($group, 0, $room);
      }
      $chosen = array_merge($chosen, $group);
    }
    usort($chosen, static fn (array $a, array $b): int => strcmp($a['birth_date'], $b['birth_date']));
    return $chosen;
  }

  /**
   * Builds the row item for a biography.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The biography.
   * @param string $month_day
   *   Today's MM-DD; the LIKE match is re-checked against the exact date.
   *
   * @return array|null
   *   Item array, or NULL when the birth date does not fall on the day.
   */
  private function buildItem(NodeInterface $node, string $month_day): ?array {
    $parsed = self::parseBirth(
      (string) ($node->get('field_drupal_birth_date')->value ?? ''),
      $node->hasField('field_dob') ? (string) ($node->get('field_dob')->value ?? '') : '',
    );
    if ($parsed === NULL || $parsed['month_day'] !== $month_day) {
      return NULL;
    }
    $birth = $parsed['date'];

    // saho_thumbnail: the same 400x225 WebP derivative the featured
    // biography block emits, so the portrait is a cache hit, not a new
    // derivative per biography per day.
    $item = $this->itemBuilder->buildItemWithImage($node, 'field_bio_pic', 'saho_thumbnail');

    $line = '';
    if ($node->hasField('field_synopsis') && !$node->get('field_synopsis')->isEmpty()) {
      $line = Excerpt::sentences((string) $node->get('field_synopsis')->value, self::LINE_LENGTH);
    }
    if ($line === '' && $node->hasField('field_position') && !$node->get('field_position')->isEmpty()) {
      $line = Excerpt::sentences((string) $node->get('field_position')->value, self::LINE_LENGTH);
    }

    return [
      'id' => $node->id(),
      'title' => $node->label(),
      'url' => $node->toUrl()->toString(),
      'image' => $item['image'] ?? '',
      'image_position' => $item['image_position'] ?? NULL,
      'birth_date' => $birth,
      'year' => $parsed['year'],
      'line' => $line,
    ];
  }

  /**
   * Resolves a biography's birth day from its two date fields.
   *
   * The datetime field wins; otherwise the legacy free-text field_dob is
   * accepted in its day-precision spellings ("2 November 1908",
   * "02-November-1908", "2 Nov 1908"). Year-only or "c. 1852" values
   * carry no day and yield NULL.
   *
   * @param string $iso
   *   The field_drupal_birth_date value (YYYY-MM-DD or empty).
   * @param string $text
   *   The field_dob value (free text or empty).
   *
   * @return array|null
   *   ['date' => 'YYYY-MM-DD', 'year' => 'YYYY', 'month_day' => 'MM-DD'],
   *   or NULL when no day-precision birth date exists.
   */
  public static function parseBirth(string $iso, string $text): ?array {
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $iso, $m)) {
      return ['date' => "$m[1]-$m[2]-$m[3]", 'year' => $m[1], 'month_day' => "$m[2]-$m[3]"];
    }
    $text = trim($text);
    if ($text === '' || !preg_match('/^(\d{1,2})[\s-]+([A-Za-z]+)\.?[\s-]+(\d{4})$/u', $text, $m)) {
      return NULL;
    }
    $month = \DateTimeImmutable::createFromFormat('!M', ucfirst(strtolower(substr($m[2], 0, 3))));
    if (!$month || !checkdate((int) $month->format('n'), (int) $m[1], (int) $m[3])) {
      return NULL;
    }
    $mm = $month->format('m');
    $dd = sprintf('%02d', (int) $m[1]);
    return ['date' => "$m[3]-$mm-$dd", 'year' => $m[3], 'month_day' => "$mm-$dd"];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return SouthAfricanDay::secondsUntilMidnight($this->time->getCurrentTime());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), ['node_list:biography']);
  }

}
