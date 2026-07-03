<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_classroom\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\saho_classroom\TopicSpineBuilderImpl;
use Drupal\saho_classroom\TopicSpineInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Kernel coverage for the topic-spine assembler.
 *
 * Exercises the real cross-bundle query, resource-type grouping/ordering,
 * grade + subject resolution and the guard/lookup helpers, using the shared
 * field_caps_topic anchor that defines the Classroom 2.0 content model.
 *
 * @coversDefaultClass \Drupal\saho_classroom\TopicSpineBuilderImpl
 *
 * @group saho_classroom
 */
final class TopicSpineBuilderTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'taxonomy',
    'node',
    'saho_classroom',
  ];

  /**
   * The system under test.
   */
  protected TopicSpineInterface $builder;

  /**
   * The anchoring CAPS topic term.
   */
  protected Term $topic;

  /**
   * A second CAPS topic term, used to prove isolation between spines.
   */
  protected Term $otherTopic;

  /**
   * The Grade 10 classroom_grade term.
   */
  protected Term $grade;

  /**
   * The History classroom_subject term.
   */
  protected Term $subject;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'filter', 'node', 'taxonomy']);

    // An admin user so published-content access checks pass deterministically;
    // unpublished exclusion is asserted through the explicit status filter.
    $this->setUpCurrentUser([], ['access content'], TRUE);

    foreach ([
      TopicSpineInterface::ANCHOR_VID,
      'classroom_resource_type',
      'classroom_grade',
      'classroom_subject',
    ] as $vid) {
      Vocabulary::create(['vid' => $vid, 'name' => $vid])->save();
    }

    foreach (['presentation', 'worksheet'] as $bundle) {
      NodeType::create(['type' => $bundle, 'name' => $bundle])->save();
    }

    $this->createTermReference(TopicSpineInterface::ANCHOR_FIELD, TopicSpineInterface::ANCHOR_VID, -1);
    $this->createTermReference(TopicSpineInterface::RESOURCE_TYPE_FIELD, 'classroom_resource_type', 1);
    $this->createTermReference(TopicSpineInterface::GRADE_FIELD, 'classroom_grade', 1);
    $this->createTermReference(TopicSpineInterface::SUBJECT_FIELD, 'classroom_subject', 1);

    $this->grade = $this->createTerm('classroom_grade', 'Grade 10');
    $this->subject = $this->createTerm('classroom_subject', 'History');
    $this->topic = $this->createTerm(TopicSpineInterface::ANCHOR_VID, 'Apartheid legislation in the 1950s');
    $this->otherTopic = $this->createTerm(TopicSpineInterface::ANCHOR_VID, 'The Cold War');

    $this->builder = new TopicSpineBuilderImpl($this->container->get('entity_type.manager'));
  }

  /**
   * @covers ::build
   */
  public function testBuildGroupsAndOrdersMembers(): void {
    $overview = $this->createTerm('classroom_resource_type', 'Topic overview', 0);
    $presentationType = $this->createTerm('classroom_resource_type', 'Presentation', 1);
    $worksheetType = $this->createTerm('classroom_resource_type', 'Worksheet', 2);

    // Created out of teaching order to prove weight-based ordering wins.
    $this->createNode('worksheet', $this->topic, $worksheetType);
    $this->createNode('presentation', $this->topic, $presentationType);
    $this->createNode('presentation', $this->topic, $overview);
    // Excluded: different topic.
    $this->createNode('presentation', $this->otherTopic, $presentationType);
    // Excluded: unpublished.
    $this->createNode('presentation', $this->topic, $presentationType, FALSE);

    $spine = $this->builder->build($this->topic);

    $this->assertFalse($spine->isEmpty());
    $this->assertSame(3, $spine->count());
    $this->assertSame(
      ['topic_overview', 'presentation', 'worksheet'],
      array_keys($spine->resourcesByType),
    );
    $this->assertCount(1, $spine->resourcesByType['presentation']);
    $this->assertSame($this->grade->id(), $spine->grade?->id());
    $this->assertSame($this->subject->id(), $spine->subject?->id());
    $this->assertCount(3, $spine->allResources());
  }

  /**
   * @covers ::build
   */
  public function testBuildRejectsNonTopicTerm(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->builder->build($this->grade);
  }

  /**
   * @covers ::build
   */
  public function testBuildReturnsEmptySpineForUnreferencedTopic(): void {
    $spine = $this->builder->build($this->otherTopic);
    $this->assertTrue($spine->isEmpty());
    $this->assertSame(0, $spine->count());
  }

  /**
   * @covers ::buildByTid
   */
  public function testBuildByTid(): void {
    $this->assertNull($this->builder->buildByTid(999999));
    // A non-topic term id resolves to NULL rather than throwing.
    $this->assertNull($this->builder->buildByTid((int) $this->grade->id()));
    $this->assertInstanceOf(
      'Drupal\\saho_classroom\\TopicSpine',
      $this->builder->buildByTid((int) $this->topic->id()),
    );
  }

  /**
   * @covers ::topicsForGrade
   */
  public function testTopicsForGradeFallsBackToWeightOrderedTree(): void {
    // The caps_topic terms carry no grade reference in this fixture, so the
    // builder returns the whole weight-ordered vocabulary tree.
    $topics = $this->builder->topicsForGrade($this->grade);
    $this->assertCount(2, $topics);
    $ids = array_map(static fn ($term) => (int) $term->id(), $topics);
    $this->assertContains((int) $this->topic->id(), $ids);
    $this->assertContains((int) $this->otherTopic->id(), $ids);
  }

  /**
   * Creates an entity-reference field on both member node bundles.
   *
   * @param string $field_name
   *   The field machine name.
   * @param string $target_vid
   *   The target vocabulary id.
   * @param int $cardinality
   *   Field cardinality (-1 for unlimited).
   */
  private function createTermReference(string $field_name, string $target_vid, int $cardinality): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => $cardinality,
      'settings' => ['target_type' => 'taxonomy_term'],
    ])->save();

    foreach (['presentation', 'worksheet'] as $bundle) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'bundle' => $bundle,
        'settings' => [
          'handler' => 'default',
          'handler_settings' => ['target_bundles' => [$target_vid => $target_vid]],
        ],
      ])->save();
    }
  }

  /**
   * Creates and saves a taxonomy term.
   *
   * @param string $vid
   *   The vocabulary id.
   * @param string $name
   *   The term name.
   * @param int $weight
   *   The term weight.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The saved term.
   */
  private function createTerm(string $vid, string $name, int $weight = 0): Term {
    $term = Term::create(['vid' => $vid, 'name' => $name, 'weight' => $weight]);
    $term->save();
    return $term;
  }

  /**
   * Creates and saves a member node referencing the topic.
   *
   * @param string $bundle
   *   The node bundle.
   * @param \Drupal\taxonomy\Entity\Term $topic
   *   The CAPS topic term to reference.
   * @param \Drupal\taxonomy\Entity\Term $resource_type
   *   The resource-type term to reference.
   * @param bool $published
   *   Whether the node is published.
   *
   * @return \Drupal\node\NodeInterface
   *   The saved node.
   */
  private function createNode(string $bundle, Term $topic, Term $resource_type, bool $published = TRUE): NodeInterface {
    $node = Node::create([
      'type' => $bundle,
      'title' => $bundle . ' for ' . $topic->getName(),
      'status' => $published ? NodeInterface::PUBLISHED : NodeInterface::NOT_PUBLISHED,
      TopicSpineInterface::ANCHOR_FIELD => [['target_id' => $topic->id()]],
      TopicSpineInterface::RESOURCE_TYPE_FIELD => [['target_id' => $resource_type->id()]],
      TopicSpineInterface::GRADE_FIELD => [['target_id' => $this->grade->id()]],
      TopicSpineInterface::SUBJECT_FIELD => [['target_id' => $this->subject->id()]],
    ]);
    $node->save();
    return $node;
  }

}
