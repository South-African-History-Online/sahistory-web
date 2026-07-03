<?php

declare(strict_types=1);

namespace Drupal\saho_classroom;

use Drupal\taxonomy\TermInterface;

/**
 * Assembles the "topic spine" for a single CAPS topic.
 *
 * The topic spine is the organising idea of the Classroom 2.0 content model
 * (epic #433, content-model issue #435). Every classroom resource type shares
 * one entity-reference field, field_caps_topic, that points at a term in the
 * caps_topic vocabulary. A single CAPS topic (for example
 * "Grade 12 - Term 2 - The Cold War") therefore acts as the spine that links
 * together every resource produced for that topic:
 *
 * - the topic overview (a legacy article/archive node, kept in place and typed
 *   only through field_classroom_resource_type),
 * - the presentation deck,
 * - one or more worksheets,
 * - classroom activities,
 * - the quiz/assessment,
 * - and any classroom_clip videos.
 *
 * A TopicSpine gathers those members, resolves the topic's grade and subject,
 * and orders the members by the classroom_resource_type vocabulary so the UI
 * can render a coherent teaching sequence from a single anchor term. This is
 * what lets a teacher land on one CAPS topic and see the full lesson unit
 * instead of five disconnected node lists.
 *
 * Implementations MUST be read-only: building a spine never writes to content.
 *
 * @see \Drupal\saho_classroom\TopicSpine
 * @see \Drupal\saho_classroom\TopicSpineBuilder
 */
interface TopicSpineInterface {

  /**
   * Machine name of the vocabulary that anchors a spine.
   */
  public const ANCHOR_VID = 'caps_topic';

  /**
   * The shared entity-reference field every resource type carries.
   *
   * All member bundles reference the caps_topic term through this one field,
   * which is what makes cross-bundle assembly a single query.
   */
  public const ANCHOR_FIELD = 'field_caps_topic';

  /**
   * Field holding the grade/phase reference (classroom_grade vocabulary).
   */
  public const GRADE_FIELD = 'field_classroom_grade';

  /**
   * Field holding the subject reference (classroom_subject vocabulary).
   */
  public const SUBJECT_FIELD = 'field_classroom_subject';

  /**
   * Field holding the resource-type reference (classroom_resource_type vocab).
   *
   * Used both to order a spine's members and to type legacy article/archive
   * nodes that keep their original bundle.
   */
  public const RESOURCE_TYPE_FIELD = 'field_classroom_resource_type';

  /**
   * Node bundles that may participate in a spine, in default display order.
   *
   * The first-class classroom bundles come first; the two legacy bundles
   * (article, archive) join because existing classroom material is retagged
   * in place rather than migrated to new bundles (see the module
   * post_update). Ordering here is only a fallback: a member's position is
   * normally decided by its field_classroom_resource_type term weight.
   *
   * @var string[]
   */
  public const RESOURCE_BUNDLES = [
    'presentation',
    'worksheet',
    'activity',
    'quiz',
    'classroom_clip',
    'article',
    'archive',
  ];

  /**
   * Builds the spine for a CAPS topic term.
   *
   * @param \Drupal\taxonomy\TermInterface $topic
   *   A term from the caps_topic vocabulary.
   *
   * @return \Drupal\saho_classroom\TopicSpine
   *   The resolved spine. May be empty when no resource references the topic.
   *
   * @throws \InvalidArgumentException
   *   When $topic is not a caps_topic term.
   */
  public function build(TermInterface $topic): TopicSpine;

  /**
   * Builds the spine for a CAPS topic term id.
   *
   * Convenience wrapper for callers (blocks, controllers, JSON:API resources)
   * that hold only a term id.
   *
   * @param int $topic_tid
   *   Term id in the caps_topic vocabulary.
   *
   * @return \Drupal\saho_classroom\TopicSpine|null
   *   The spine, or NULL when the term does not exist or is the wrong vocab.
   */
  public function buildByTid(int $topic_tid): ?TopicSpine;

  /**
   * Lists the CAPS topic terms that belong to a grade/phase.
   *
   * Powers grade landing pages: each returned topic can then be expanded into
   * its spine. Respects the caps_topic term weight so curriculum order is
   * preserved.
   *
   * @param \Drupal\taxonomy\TermInterface $grade
   *   A term from the classroom_grade vocabulary (a grade or a phase parent).
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   The caps_topic terms under that grade/phase, in curriculum order.
   */
  public function topicsForGrade(TermInterface $grade): array;

}
