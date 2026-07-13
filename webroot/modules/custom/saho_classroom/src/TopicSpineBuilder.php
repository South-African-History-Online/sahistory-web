<?php

declare(strict_types=1);

namespace Drupal\saho_classroom;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Default topic-spine assembler.
 *
 * This is an API sketch for issue #435: the method bodies describe the
 * intended algorithm in comments and are not yet implemented, so the class is
 * a stub that documents the shape rather than working code. It is deliberately
 * not wired as a service yet (the module is not enabled). When the module is
 * activated the intended wiring is:
 *
 * @code
 * # saho_classroom.services.yml
 * services:
 *   saho_classroom.topic_spine:
 *     class: Drupal\saho_classroom\TopicSpineBuilder
 *     arguments: ['@entity_type.manager']
 * @endcode
 *
 * @see \Drupal\saho_classroom\TopicSpineInterface
 */
final class TopicSpineBuilder implements TopicSpineInterface {

  /**
   * Constructs a TopicSpineBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager, used read-only to load terms and query nodes.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   *
   * Intended algorithm:
   * 1. Guard: assert $topic->bundle() === self::ANCHOR_VID, else throw
   *    \InvalidArgumentException.
   * 2. Resolve grade/subject: prefer the topic's own reference fields if the
   *    caps_topic vocabulary carries them; otherwise derive from the caps_topic
   *    hierarchy (each topic sits under a "Grade N" parent) and fall back to the
   *    modal field_classroom_grade/field_classroom_subject value across members.
   * 3. Query members once: an entity query over node, condition
   *    self::ANCHOR_FIELD == $topic->id(), bundle IN self::RESOURCE_BUNDLES,
   *    status = published, access-checked. One query spans every resource type
   *    because they all share the anchor field.
   * 4. Group + order: bucket the loaded nodes by their
   *    self::RESOURCE_TYPE_FIELD term and sort the buckets by that term's
   *    weight (Topic overview, Presentation, Worksheet, Activity, Quiz,
   *    Clip, ...), so the spine reads as a teaching sequence.
   * 5. Return a new TopicSpine($topic, $grade, $subject, $resourcesByType).
   *
   * Cacheability: callers should add the topic term's cache tags plus a
   * node_list:<bundle> tag per member bundle so new resources invalidate the
   * spine.
   */
  public function build(TermInterface $topic): TopicSpine {
    throw new \LogicException(__METHOD__ . '() is a #435 design sketch and is not yet implemented.');
  }

  /**
   * {@inheritdoc}
   *
   * Intended algorithm: load the term via the taxonomy_term storage; return
   * NULL when it is missing or its bundle is not self::ANCHOR_VID; otherwise
   * delegate to build().
   */
  public function buildByTid(int $topic_tid): ?TopicSpine {
    throw new \LogicException(__METHOD__ . '() is a #435 design sketch and is not yet implemented.');
  }

  /**
   * {@inheritdoc}
   *
   * Intended algorithm: from the classroom_grade term, collect the matching
   * caps_topic parent(s), load their children via the taxonomy_term storage
   * loadTree() (which already returns weight order), and return them. A grade
   * passed at phase level (Intermediate/Senior/FET) fans out to every child
   * grade first.
   */
  public function topicsForGrade(TermInterface $grade): array {
    throw new \LogicException(__METHOD__ . '() is a #435 design sketch and is not yet implemented.');
  }

}
