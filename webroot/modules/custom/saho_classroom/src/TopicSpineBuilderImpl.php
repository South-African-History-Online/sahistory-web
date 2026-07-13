<?php

declare(strict_types=1);

namespace Drupal\saho_classroom;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Default, working topic-spine assembler (issue #435 / #445).
 *
 * This is the functional implementation of {@see TopicSpineInterface}. It is a
 * sibling of the earlier design sketch \Drupal\saho_classroom\TopicSpineBuilder
 * (which documents the intended algorithm in comments): that stub is left in
 * place untouched and this class carries the real query/grouping logic so the
 * two can coexist while the module is still disabled.
 *
 * Assembly is a single cross-bundle entity query: every resource bundle shares
 * the field_caps_topic reference, so one query keyed on the anchor term gathers
 * the whole lesson unit. Members are then bucketed by their
 * field_classroom_resource_type term and ordered by that term's weight, which
 * is what turns five disconnected node lists into a teaching sequence.
 *
 * The builder is strictly read-only: it never writes content.
 *
 * Intended wiring (see saho_classroom.services.yml):
 * @code
 * services:
 *   saho_classroom.topic_spine:
 *     class: Drupal\saho_classroom\TopicSpineBuilderImpl
 *     arguments: ['@entity_type.manager']
 * @endcode
 *
 * @see \Drupal\saho_classroom\TopicSpineInterface
 * @see \Drupal\saho_classroom\TopicSpine
 */
final class TopicSpineBuilderImpl implements TopicSpineInterface {

  /**
   * Weight offset applied to bundle-based fallback buckets.
   *
   * Members that carry no resource-type term are grouped by their node bundle
   * and pushed after every properly typed bucket, preserving the declared
   * RESOURCE_BUNDLES order among themselves.
   */
  private const FALLBACK_WEIGHT_BASE = 1000;

  /**
   * Constructs a TopicSpineBuilderImpl.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager, used read-only to load terms and query nodes.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function build(TermInterface $topic): TopicSpine {
    if ($topic->bundle() !== self::ANCHOR_VID) {
      throw new \InvalidArgumentException(sprintf(
        'A topic spine can only be built from a "%s" term, but "%s" was given.',
        self::ANCHOR_VID,
        $topic->bundle(),
      ));
    }

    $node_storage = $this->entityTypeManager->getStorage('node');
    $nids = $node_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition(self::ANCHOR_FIELD, $topic->id())
      ->condition('type', self::RESOURCE_BUNDLES, 'IN')
      ->condition('status', NodeInterface::PUBLISHED)
      ->sort('created', 'ASC')
      ->execute();

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $nids ? $node_storage->loadMultiple($nids) : [];

    $resources_by_type = $this->groupByResourceType($nodes);
    $grade = $this->resolveTerm($topic, $nodes, self::GRADE_FIELD);
    $subject = $this->resolveTerm($topic, $nodes, self::SUBJECT_FIELD);

    return new TopicSpine($topic, $grade, $subject, $resources_by_type);
  }

  /**
   * {@inheritdoc}
   */
  public function buildByTid(int $topic_tid): ?TopicSpine {
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($topic_tid);
    if (!$term instanceof TermInterface || $term->bundle() !== self::ANCHOR_VID) {
      return NULL;
    }
    return $this->build($term);
  }

  /**
   * {@inheritdoc}
   */
  public function topicsForGrade(TermInterface $grade): array {
    /** @var \Drupal\taxonomy\TermInterface[] $tree */
    $tree = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree(self::ANCHOR_VID, 0, NULL, TRUE);

    $matched = [];
    $grade_field_present = FALSE;
    foreach ($tree as $topic) {
      if (!$topic->hasField(self::GRADE_FIELD)) {
        continue;
      }
      $grade_field_present = TRUE;
      foreach ($topic->get(self::GRADE_FIELD) as $item) {
        if ((int) $item->target_id === (int) $grade->id()) {
          $matched[] = $topic;
          break;
        }
      }
    }

    // Fallback: when caps_topic terms carry no grade reference at all, the
    // grade linkage lives elsewhere in the model and we cannot filter here, so
    // return the whole weight-ordered tree rather than an empty list.
    if (!$grade_field_present) {
      return array_values($tree);
    }

    return $matched;
  }

  /**
   * Groups member nodes by resource type and orders them by term weight.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   The member nodes, already ordered by creation date.
   *
   * @return array<string, \Drupal\node\NodeInterface[]>
   *   Nodes keyed by resource-type machine key, buckets in teaching order.
   */
  private function groupByResourceType(array $nodes): array {
    $buckets = [];
    foreach ($nodes as $node) {
      [$key, $weight] = $this->resourceTypeKey($node);
      if (!isset($buckets[$key])) {
        $buckets[$key] = ['weight' => $weight, 'nodes' => []];
      }
      $buckets[$key]['nodes'][] = $node;
    }

    // Order buckets by resource-type weight, then key for a stable tie-break.
    uksort($buckets, function (string $a, string $b) use ($buckets): int {
      return [$buckets[$a]['weight'], $a] <=> [$buckets[$b]['weight'], $b];
    });

    $resources_by_type = [];
    foreach ($buckets as $key => $bucket) {
      $resources_by_type[$key] = $bucket['nodes'];
    }
    return $resources_by_type;
  }

  /**
   * Resolves the grouping key and sort weight for one member node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The member node.
   *
   * @return array{0: string, 1: int}
   *   A tuple of [machine key, sort weight]. The key comes from the node's
   *   resource-type term when present; otherwise it falls back to the node
   *   bundle, weighted to sort after every typed bucket.
   */
  private function resourceTypeKey(NodeInterface $node): array {
    if ($node->hasField(self::RESOURCE_TYPE_FIELD) && !$node->get(self::RESOURCE_TYPE_FIELD)->isEmpty()) {
      $term = $node->get(self::RESOURCE_TYPE_FIELD)->entity;
      if ($term instanceof TermInterface) {
        return [$this->machineKey($term->getName()), (int) $term->getWeight()];
      }
    }

    $position = array_search($node->bundle(), self::RESOURCE_BUNDLES, TRUE);
    $offset = $position === FALSE ? count(self::RESOURCE_BUNDLES) : (int) $position;
    return [$node->bundle(), self::FALLBACK_WEIGHT_BASE + $offset];
  }

  /**
   * Resolves a referenced term, preferring the topic then the modal member.
   *
   * The topic term is consulted first (a caps_topic term may carry its own
   * grade/subject reference). When it does not, the most common value across
   * the member nodes is used, which keeps the spine's grade/subject sensible
   * even before the taxonomy is fully wired.
   *
   * @param \Drupal\taxonomy\TermInterface $topic
   *   The anchoring caps_topic term.
   * @param \Drupal\node\NodeInterface[] $nodes
   *   The member nodes.
   * @param string $field
   *   The reference field name to resolve.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The resolved term, or NULL when neither source provides one.
   */
  private function resolveTerm(TermInterface $topic, array $nodes, string $field): ?TermInterface {
    $own = $this->referencedTerm($topic, $field);
    if ($own instanceof TermInterface) {
      return $own;
    }

    $counts = [];
    $terms = [];
    foreach ($nodes as $node) {
      $term = $this->referencedTerm($node, $field);
      if (!$term instanceof TermInterface) {
        continue;
      }
      $tid = (int) $term->id();
      $counts[$tid] = ($counts[$tid] ?? 0) + 1;
      $terms[$tid] = $term;
    }
    if ($counts === []) {
      return NULL;
    }

    // Descending sort keeps ties in first-seen order, so the earliest created
    // member wins a tie deterministically.
    arsort($counts);
    return $terms[array_key_first($counts)];
  }

  /**
   * Returns the first term referenced by a field, if any.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to read.
   * @param string $field
   *   The reference field name.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The referenced term, or NULL when the field is absent or empty.
   */
  private function referencedTerm(FieldableEntityInterface $entity, string $field): ?TermInterface {
    if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      return NULL;
    }
    $term = $entity->get($field)->entity;
    return $term instanceof TermInterface ? $term : NULL;
  }

  /**
   * Derives a stable machine key from a resource-type term name.
   *
   * Taxonomy terms have no machine name, so the spine keys its buckets on a
   * lower-cased, underscore-collapsed slug of the term label (for example
   * "Topic overview" becomes "topic_overview").
   *
   * @param string $name
   *   The term label.
   *
   * @return string
   *   The machine key.
   */
  private function machineKey(string $name): string {
    $key = strtolower($name);
    $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? '';
    $key = trim($key, '_');
    return $key !== '' ? $key : 'unclassified';
  }

}
