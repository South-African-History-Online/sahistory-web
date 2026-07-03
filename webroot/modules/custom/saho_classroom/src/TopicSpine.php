<?php

declare(strict_types=1);

namespace Drupal\saho_classroom;

use Drupal\taxonomy\TermInterface;

/**
 * Immutable value object describing one assembled topic spine.
 *
 * A TopicSpine is the resolved answer to "what is the full lesson unit for
 * this CAPS topic?". It is produced by
 * \Drupal\saho_classroom\TopicSpineInterface::build() and carries no
 * behaviour beyond simple read accessors, so it is safe to cache and hand to
 * a Twig template or normaliser.
 *
 * @see \Drupal\saho_classroom\TopicSpineInterface
 */
final class TopicSpine {

  /**
   * Constructs a TopicSpine.
   *
   * @param \Drupal\taxonomy\TermInterface $topic
   *   The anchoring caps_topic term.
   * @param \Drupal\taxonomy\TermInterface|null $grade
   *   The resolved classroom_grade term, or NULL when unknown.
   * @param \Drupal\taxonomy\TermInterface|null $subject
   *   The resolved classroom_subject term, or NULL when unknown.
   * @param array<string, \Drupal\node\NodeInterface[]> $resourcesByType
   *   Member nodes grouped by classroom_resource_type machine name and ordered
   *   by that vocabulary's term weight. Keys are resource-type machine names
   *   (for example "topic_overview", "presentation", "worksheet"); each value
   *   is the list of nodes of that type that reference $topic.
   */
  public function __construct(
    public readonly TermInterface $topic,
    public readonly ?TermInterface $grade,
    public readonly ?TermInterface $subject,
    public readonly array $resourcesByType,
  ) {}

  /**
   * Whether the spine has no member resources.
   *
   * @return bool
   *   TRUE when the anchor topic is referenced by no resource node.
   */
  public function isEmpty(): bool {
    foreach ($this->resourcesByType as $nodes) {
      if ($nodes !== []) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Total number of member resources across all resource types.
   *
   * @return int
   *   The member count.
   */
  public function count(): int {
    return array_sum(array_map('count', $this->resourcesByType));
  }

  /**
   * Returns every member node in resource-type order as a flat list.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The flattened, ordered member list.
   */
  public function allResources(): array {
    return array_merge([], ...array_values($this->resourcesByType));
  }

}
