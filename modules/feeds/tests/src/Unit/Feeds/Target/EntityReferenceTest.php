<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\ReferenceNotFoundException;
use Drupal\feeds\Feeds\Target\EntityReference;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\FieldTargetDefinition;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\EntityReference
 * @group feeds
 */
class EntityReferenceTest extends EntityReferenceTestBase {

  /**
   * Field manager used in the test.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $this->entityFieldManager->getFieldStorageDefinitions('referenceable_entity_type')->willReturn([]);

    $this->buildContainer();
  }

  /**
   * {@inheritdoc}
   */
  protected function createTargetPluginInstance(array $configuration = []) {
    $configuration += [
      'feed_type' => $this->createMock(FeedTypeInterface::class),
      'target_definition' => $this->createTargetDefinitionMock(),
    ];
    return new EntityReference($configuration, 'entity_reference', [], $this->entityTypeManager->reveal(), $this->entityFieldManager->reveal(), $this->entityFinder->reveal());
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return EntityReference::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function createReferencableEntityType() {
    $referenceable_entity_type = $this->prophesize(EntityTypeInterface::class);
    $referenceable_entity_type->entityClassImplements(ContentEntityInterface::class)->willReturn(TRUE)->shouldBeCalled();
    $referenceable_entity_type->getKey('label')->willReturn('referenceable_entity_type label');

    return $referenceable_entity_type;
  }

  /**
   * @covers ::prepareTarget
   */
  public function testPrepareTarget() {
    $field_definition_mock = $this->getMockFieldDefinition();
    $field_definition_mock->expects($this->once())
      ->method('getSetting')
      ->will($this->returnValue('referenceable_entity_type'));

    $method = $this->getMethod($this->getTargetClass(), 'prepareTarget')->getClosure();
    $this->assertInstanceof(FieldTargetDefinition::class, $method($field_definition_mock));
  }

  /**
   * @covers ::prepareValue
   * @covers ::findEntities
   */
  public function testPrepareValue() {
    $this->entityFinder->findEntities('referenceable_entity_type', 'referenceable_entity_type label', 1, [])
      ->willReturn([12, 13, 14])
      ->shouldBeCalled();

    $method = $this->getProtectedClosure($this->createTargetPluginInstance(), 'prepareValue');
    $values = ['target_id' => 1];
    $method(0, $values);
    $this->assertSame($values, ['target_id' => 12]);
  }

  /**
   * @covers ::prepareValue
   *
   * Tests prepareValue() without passing values.
   */
  public function testPrepareValueEmptyFeed() {
    $method = $this->getProtectedClosure($this->createTargetPluginInstance(), 'prepareValue');
    $values = ['target_id' => ''];
    $this->expectException(EmptyFeedException::class);
    $method(0, $values);
  }

  /**
   * @covers ::prepareValue
   * @covers ::findEntities
   *
   * Tests prepareValue() method without match.
   */
  public function testPrepareValueReferenceNotFound() {
    $this->entityFinder->findEntities('referenceable_entity_type', 'referenceable_entity_type label', 1, [])
      ->willReturn([])
      ->shouldBeCalled();

    $method = $this->getProtectedClosure($this->createTargetPluginInstance(), 'prepareValue');
    $values = ['target_id' => 1];
    $this->expectException(ReferenceNotFoundException::class, "Referenced entity not found for field <em class=\"placeholder\">referenceable_entity_type label</em> with value <em class=\"placeholder\">1</em>.");
    $method(0, $values);
  }

}
