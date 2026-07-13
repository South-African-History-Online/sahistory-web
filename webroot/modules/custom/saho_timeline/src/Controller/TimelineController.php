<?php

namespace Drupal\saho_timeline\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\saho_timeline\Service\TimelineEventService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The /timeline page: server-rendered shell + Svelte app mount.
 *
 * The server renders real content - record header, era sections with top
 * events as plain links, schema.org JSON-LD - so crawlers and no-JS
 * visitors get a genuine page and there is never a spinner-only state.
 * The app mounts over it and takes over once its data arrives.
 *
 * Deliberately loads NO event nodes beyond the handful of era anchors:
 * the old controller hydrated up to 1,000 full nodes per render and
 * embedded 100 more as drupalSettings fallback data.
 */
class TimelineController extends ControllerBase {

  /**
   * Curated era chapters - the single source of truth.
   *
   * The one definition both the server shell and the app consume,
   * replacing the three disagreeing period lists of the old stack.
   * Blurbs are system voice: facts only, editorial can refine (#480).
   */
  protected const ERAS = [
    [
      'id' => 'early',
      'label' => 'Early Encounters',
      'start' => NULL,
      'end' => 1652,
      'blurb' => 'Ibn Battuta to the first Europeans rounding the Cape: trade, exploration and the societies already here.',
    ],
    [
      'id' => 'colonial-cape',
      'label' => 'The Colonial Cape',
      'start' => 1652,
      'end' => 1806,
      'blurb' => 'The Dutch East India Company settlement, slavery at the Cape, and the frontier wars of dispossession.',
    ],
    [
      'id' => 'frontier',
      'label' => 'Frontiers and Dispossession',
      'start' => 1806,
      'end' => 1867,
      'blurb' => 'British rule, the Mfecane, the Great Trek and the destruction of independent African polities.',
    ],
    [
      'id' => 'mineral',
      'label' => 'The Mineral Revolution',
      'start' => 1867,
      'end' => 1910,
      'blurb' => 'Diamonds, gold, migrant labour and the South African War remake the subcontinent.',
    ],
    [
      'id' => 'union',
      'label' => 'Union and Segregation',
      'start' => 1910,
      'end' => 1948,
      'blurb' => 'The white Union of 1910, the 1913 Land Act, and the founding of the ANC and organised resistance.',
    ],
    [
      'id' => 'apartheid',
      'label' => 'Apartheid',
      'start' => 1948,
      'end' => 1994,
      'blurb' => 'Legislated racial rule - and four decades of defiance, armed struggle, exile and mass mobilisation.',
    ],
    [
      'id' => 'democracy',
      'label' => 'Democracy',
      'start' => 1994,
      'end' => NULL,
      'blurb' => 'From the 1994 election onward: the constitution, the TRC, and the unfinished work of transformation.',
    ],
  ];

  /**
   * Events linked per era in the server-rendered shell.
   */
  protected const SHELL_EVENTS_PER_ERA = 5;

  /**
   * The timeline event service.
   *
   * @var \Drupal\saho_timeline\Service\TimelineEventService
   */
  protected $timelineEventService;

  public function __construct(TimelineEventService $timeline_event_service) {
    $this->timelineEventService = $timeline_event_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saho_timeline.event_service'),
    );
  }

  /**
   * Display the timeline page.
   */
  public function display() {
    $index = $this->timelineEventService->getTimelineIndexV2();
    $count = count($index);
    $range = $count > 0
      ? [(int) substr($index[0]->date, 0, 4), (int) substr($index[$count - 1]->date, 0, 4)]
      : [0, 0];

    $eras = $this->buildEraShell($index);

    return [
      '#theme' => 'saho_timeline_app',
      '#count' => $count,
      '#range' => $range,
      '#eras' => $eras,
      '#json_ld' => $this->buildJsonLd($eras),
      '#attached' => [
        'library' => [
          'saho_timeline/timeline-app',
        ],
        'drupalSettings' => [
          'sahoTimeline' => [
            'endpoints' => [
              'index' => '/api/timeline/v2/index',
              'bucket' => '/api/timeline/v2/events',
              'event' => '/api/timeline/v2/event',
            ],
            'eras' => array_map(static fn(array $era) => [
              'id' => $era['id'],
              'label' => $era['label'],
              'start' => $era['start'],
              'end' => $era['end'],
              'blurb' => $era['blurb'],
            ], self::ERAS),
            'decadeCounts' => $this->timelineEventService->getDecadeCounts(),
            'count' => $count,
            'range' => $range,
          ],
        ],
      ],
      // The client parses ?year/?era/?q/#event-NID itself, so this render
      // does NOT vary by query string - one cached page for every entry
      // point.
      '#cache' => [
        'tags' => ['node_list:event'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Builds the server-rendered era sections from the light index.
   *
   * Only the linked anchor events are hydrated - a few dozen nodes, not
   * the corpus - to get real alias URLs for crawlers.
   */
  protected function buildEraShell(array $index): array {
    $storage = $this->entityTypeManager()->getStorage('node');
    $eras = [];

    foreach (self::ERAS as $era) {
      $start = $era['start'] ?? 0;
      $end = $era['end'] ?? 10000;

      $rows = array_values(array_filter($index, static function ($row) use ($start, $end) {
        $year = (int) substr($row->date, 0, 4);
        return $year >= $start && $year < $end;
      }));
      $era['count'] = count($rows);
      $era['events'] = [];

      // Spread the anchors across the era rather than taking the first
      // five: the shell should sketch the whole chapter.
      $picks = [];
      if ($rows !== []) {
        $step = max(1, (int) floor(count($rows) / self::SHELL_EVENTS_PER_ERA));
        for ($i = 0; $i < count($rows) && count($picks) < self::SHELL_EVENTS_PER_ERA; $i += $step) {
          $picks[] = $rows[$i];
        }
      }

      $ids = array_map(static fn($row) => $row->id, $picks);
      $nodes = $ids !== [] ? $storage->loadMultiple($ids) : [];
      foreach ($picks as $row) {
        if (!isset($nodes[$row->id]) || !$nodes[$row->id] instanceof NodeInterface) {
          continue;
        }
        $node = $nodes[$row->id];
        $era['events'][] = [
          'year' => (int) substr($row->date, 0, 4),
          'date' => $row->date,
          'title' => $node->label(),
          'url' => $node->toUrl()->toString(),
        ];
      }

      $eras[] = $era;
    }

    return $eras;
  }

  /**
   * Schema.org ItemList JSON-LD for the shell's anchor events.
   */
  protected function buildJsonLd(array $eras): string {
    $items = [];
    $position = 0;
    foreach ($eras as $era) {
      foreach ($era['events'] as $event) {
        $items[] = [
          '@type' => 'ListItem',
          'position' => ++$position,
          'item' => [
            '@type' => 'Event',
            'name' => Html::decodeEntities($event['title']),
            'startDate' => $event['date'],
            'url' => Url::fromUserInput($event['url'], ['absolute' => TRUE])->toString(),
          ],
        ];
      }
    }

    // JSON_HEX_TAG: this string is printed raw inside a <script
    // type="application/ld+json"> block, so a literal '</script>' in an
    // event title must never survive encoding (script-block breakout =
    // stored XSS from editor-authored titles).
    return json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'ItemList',
      'name' => 'Timeline of South African History',
      'numberOfItems' => $position,
      'itemListElement' => $items,
    ], JSON_HEX_TAG | JSON_HEX_AMP);
  }

}
