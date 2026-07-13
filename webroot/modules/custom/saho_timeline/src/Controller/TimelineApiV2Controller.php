<?php

namespace Drupal\saho_timeline\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\NodeInterface;
use Drupal\saho_timeline\Service\TimelineEventService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Timeline API v2: skeleton index + decade buckets + event resolver.
 *
 * Three small, aggressively cacheable endpoints replace the v1
 * everything-in-one-blob API:
 * - /api/timeline/v2/index: columnar skeleton of ALL plottable events.
 * - /api/timeline/v2/events/{bucket}: full cards for one decade.
 * - /api/timeline/v2/event/{node}: one card + its bucket, for deep links.
 *
 * Every response is a CacheableJsonResponse tagged node_list:event (plus
 * node:NID on the resolver) so Page Cache and the edge do the heavy
 * lifting - no hand-rolled cache layer.
 */
class TimelineApiV2Controller extends ControllerBase {

  /**
   * Single-character precision codes carried by the API.
   */
  protected const PRECISION_CODES = [
    'day' => 'd',
    'month' => 'm',
    'year' => 'y',
    'range' => 'r',
    'circa' => 'c',
  ];

  /**
   * Image style for card thumbnails.
   */
  protected const STYLE_THUMB = 'timeline_thumb';

  /**
   * Image style for the detail panel.
   */
  protected const STYLE_CARD = 'timeline_card';

  /**
   * The timeline event service.
   *
   * @var \Drupal\saho_timeline\Service\TimelineEventService
   */
  protected $timelineEventService;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  public function __construct(TimelineEventService $timeline_event_service, FileUrlGeneratorInterface $file_url_generator) {
    $this->timelineEventService = $timeline_event_service;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saho_timeline.event_service'),
      $container->get('file_url_generator'),
    );
  }

  /**
   * The skeleton: every plottable event as parallel columnar arrays.
   *
   * Columnar because 3.5k+ repeated object keys gzip far worse than four
   * flat arrays. The client parses dates once into typed arrays; this
   * payload is its histogram, minimap and instant title search.
   */
  public function index() {
    $rows = $this->timelineEventService->getTimelineIndexV2();

    $data = [
      'count' => count($rows),
      'range' => [0, 0],
      'nids' => [],
      'dates' => [],
      'precision' => [],
      'titles' => [],
    ];
    foreach ($rows as $row) {
      $data['nids'][] = $row->id;
      $data['dates'][] = $row->date;
      $data['precision'][] = self::PRECISION_CODES[$row->precision] ?? 'd';
      $data['titles'][] = $this->toPlainText($row->title);
    }
    if ($data['count'] > 0) {
      $data['range'] = [
        (int) substr($data['dates'][0], 0, 4),
        (int) substr($data['dates'][$data['count'] - 1], 0, 4),
      ];
    }

    return $this->cacheableResponse($data);
  }

  /**
   * Full cards for one decade bucket.
   *
   * @param string $bucket
   *   Either 'pre1500' or a decade token ('1500'..'2020'), enforced by the
   *   route regex; the range check below rejects tokens outside the corpus.
   */
  public function bucket(string $bucket) {
    if ($bucket !== 'pre1500') {
      $decade = (int) $bucket;
      if ($decade < 1500 || $decade > (int) date('Y') + 10) {
        throw new NotFoundHttpException();
      }
    }

    $rows = $this->timelineEventService->getBucketRows($bucket);
    $events = [];
    foreach ($this->hydrateRows($rows) as $i => $node) {
      $events[] = $this->buildCard($node, $rows[$i]);
    }

    return $this->cacheableResponse([
      'bucket' => $bucket,
      'count' => count($events),
      'events' => $events,
    ]);
  }

  /**
   * Resolves one event for deep links: its card, bucket and relations.
   */
  public function event(NodeInterface $node) {
    if ($node->bundle() !== 'event' || !$node->isPublished()) {
      throw new NotFoundHttpException();
    }
    $date = $this->resolveDate($node);
    if ($date === NULL) {
      // Dateless events have no place on the timeline (yet).
      throw new NotFoundHttpException();
    }

    $card = $this->buildCard($node);
    $card['image'] = $this->imageUrl($node, self::STYLE_CARD);
    $card['related'] = $this->buildRelated($node);

    $response = $this->cacheableResponse([
      'bucket' => TimelineEventService::bucketForDate($date),
      'event' => $card,
    ]);
    $response->addCacheableDependency($node);
    return $response;
  }

  /**
   * Builds the card shape shared by the bucket and resolver endpoints.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   * @param object|null $row
   *   The light index row when already known (saves re-deriving date and
   *   precision from fields).
   */
  protected function buildCard(NodeInterface $node, ?object $row = NULL): array {
    $date = $row->date ?? $this->resolveDate($node) ?? '';
    $precision = $row->precision ?? $this->resolvePrecision($node);

    $body = '';
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = $this->toPlainText($node->get('body')->value);
      if (mb_strlen($body) > 300) {
        $body = mb_substr($body, 0, 300) . '...';
      }
    }

    $ref = NULL;
    if ($node->hasField('field_ref_str') && !$node->get('field_ref_str')->isEmpty()) {
      $ref = $this->toPlainText($node->get('field_ref_str')->value) ?: NULL;
    }

    return [
      'nid' => (int) $node->id(),
      'date' => $date,
      'precision' => self::PRECISION_CODES[$precision] ?? 'd',
      'title' => $this->toPlainText($node->label()),
      'body' => $body,
      'url' => $node->toUrl()->toString(),
      'thumb' => $this->imageUrl($node, self::STYLE_THUMB),
      'ref' => $ref,
    ];
  }

  /**
   * Related people/organisations/topics for the detail panel.
   *
   * Reads the saho_relations-densified *_related_tab reference fields;
   * published targets only, capped per group.
   */
  protected function buildRelated(NodeInterface $node): array {
    $map = [
      'people' => 'field_people_related_tab',
      'organizations' => 'field_organizations_related_tab',
      'topics' => 'field_topics_related_tab',
    ];
    $related = [];
    foreach ($map as $key => $field_name) {
      $related[$key] = [];
      if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
        continue;
      }
      foreach ($node->get($field_name)->referencedEntities() as $target) {
        if (!$target instanceof NodeInterface || !$target->isPublished()) {
          continue;
        }
        $related[$key][] = [
          'nid' => (int) $target->id(),
          'title' => $this->toPlainText($target->label()),
          'url' => $target->toUrl()->toString(),
        ];
        if (count($related[$key]) >= 10) {
          break;
        }
      }
    }
    return array_filter($related);
  }

  /**
   * The plottable date: curated field wins, extracted fallback.
   */
  protected function resolveDate(NodeInterface $node): ?string {
    foreach (['field_event_date', 'field_timeline_date'] as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        $value = $node->get($field_name)->value;
        if (!empty($value)) {
          return $value;
        }
      }
    }
    return NULL;
  }

  /**
   * Precision word for a node (curated dates are 'day' by definition).
   */
  protected function resolvePrecision(NodeInterface $node): string {
    if ($node->hasField('field_event_date') && !$node->get('field_event_date')->isEmpty()) {
      return 'day';
    }
    if ($node->hasField('field_timeline_date_precision') && !$node->get('field_timeline_date_precision')->isEmpty()) {
      return $node->get('field_timeline_date_precision')->value;
    }
    return 'day';
  }

  /**
   * Root-relative image style URL from the event's image fields.
   */
  protected function imageUrl(NodeInterface $node, string $style_name): ?string {
    foreach (['field_event_image', 'field_tdih_image', 'field_image'] as $field_name) {
      if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
        continue;
      }
      $file = $node->get($field_name)->entity;
      if (!$file instanceof FileInterface) {
        continue;
      }
      $style = ImageStyle::load($style_name);
      if ($style && $style->supportsUri($file->getFileUri())) {
        return $this->fileUrlGenerator->transformRelative($style->buildUrl($file->getFileUri()));
      }
      return $this->fileUrlGenerator->transformRelative($this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()));
    }
    return NULL;
  }

  /**
   * Hydrates light rows to nodes in row order, chunked (memory-bounded).
   *
   * @param object[] $rows
   *   Light rows carrying ->id.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Nodes keyed to match the incoming row indexes.
   */
  protected function hydrateRows(array $rows): array {
    $storage = $this->entityTypeManager()->getStorage('node');
    $nodes = [];
    foreach (array_chunk($rows, 100, TRUE) as $chunk) {
      $ids = array_map(static fn($row) => $row->id, $chunk);
      $loaded = $storage->loadMultiple($ids);
      foreach ($chunk as $index => $row) {
        if (isset($loaded[$row->id])) {
          $nodes[$index] = $loaded[$row->id];
        }
      }
      $storage->resetCache(array_values($ids));
    }
    // Keys mirror the incoming row indexes so callers can zip node to row
    // even when an id failed to load.
    return $nodes;
  }

  /**
   * Wraps data in a CacheableJsonResponse tagged for event-list changes.
   */
  protected function cacheableResponse(array $data): CacheableJsonResponse {
    $response = new CacheableJsonResponse($data);
    $metadata = new CacheableMetadata();
    $metadata->setCacheTags(['node_list:event']);
    $metadata->setCacheMaxAge(3600);
    $response->addCacheableDependency($metadata);
    $response->setMaxAge(3600);
    $response->setPublic();
    return $response;
  }

  /**
   * Reduces stored (possibly HTML) text to clean plain text.
   *
   * Entities are decoded before tags are stripped so stored "&lt;em&gt;"
   * cannot survive as live markup; control characters are dropped and
   * whitespace collapsed. Multibyte UTF-8 passes through intact.
   */
  protected function toPlainText(?string $text): string {
    if ($text === NULL || $text === '') {
      return '';
    }
    $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
    if ($clean === FALSE) {
      $clean = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }
    $clean = strip_tags(Html::decodeEntities($clean));
    $clean = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $clean);
    return trim(preg_replace('/\s+/u', ' ', $clean));
  }

}
