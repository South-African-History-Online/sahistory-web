<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\feeds\Exception\ReferenceNotFoundException;
use Drupal\feeds\Feeds\Target\ConfigEntityReference;
use Drupal\feeds\FeedTypeInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\ConfigEntityReference
 * @group feeds
 */
class ConfigEntityReferenceTest extends ConfigEntityReferenceTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->buildContainer();
  }

  /**
   * {@inheritdoc}
   */
  protected function createTargetPluginInstance(array $configuration = []) {
    $configuration += [
      'feed_type' => $this->createMock(FeedTypeInterface::class),
      'target_definition' => $this->createTargetDefinitionMock(),
      'reference_by' => 'id',
    ];
    return new ConfigEntityReference($configuration, 'config_entity_reference', [], $this->entityTypeManager->reveal(), $this->entityFinder->reveal(), $this->transliteration->reveal(), $this->typedConfigManager->reveal());
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return ConfigEntityReference::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function createReferencableEntityType() {
    $referenceable_entity_type = $this->prophesize(ConfigEntityTypeInterface::class);
    $referenceable_entity_type->entityClassImplements(ConfigEntityInterface::class)->willReturn(TRUE)->shouldBeCalled();
    $referenceable_entity_type->getKey('label')->willReturn('label');
    $referenceable_entity_type->getConfigPrefix()->willReturn('foo.foo');

    return $referenceable_entity_type;
  }

  /**
   * Tests finding an entity by ID.
   *
   * @covers ::prepareValue
   * @covers ::findEntity
   */
  public function testPrepareValue() {
    $this->entityFinder->findEntities($this->getReferencableEntityTypeId(), 'id', 'foo')
      ->willReturn(['foo'])
      ->shouldBeCalled();

    $method = $this->getProtectedClosure($this->createTargetPluginInstance(), 'prepareValue');
    $values = ['target_id' => 'foo'];
    $method(0, $values);
    $this->assertSame($values, ['target_id' => 'foo']);
  }

  /**
   * Tests prepareValue() method without match.
   *
   * @covers ::prepareValue
   * @covers ::findEntity
   */
  public function testPrepareValueReferenceNotFound() {
    $this->entityFinder->findEntities($this->getReferencableEntityTypeId(), 'id', 'bar')
      ->willReturn([])
      ->shouldBeCalled();

    $method = $this->getProtectedClosure($this->createTargetPluginInstance(), 'prepareValue');
    $values = ['target_id' => 'bar'];
    $this->expectException(ReferenceNotFoundException::class);
    $this->expectExceptionMessage("Referenced entity not found for field <em class=\"placeholder\">id</em> with value <em class=\"placeholder\">bar</em>.");
    $method(0, $values);
  }

  /**
   * @covers ::getSummary
   */
  public function testGetSummary() {
    $expected = [
      'Reference by: <em class="placeholder">ID</em>',
    ];
    $summary = $this->createTargetPluginInstance()->getSummary();
    foreach ($summary as $key => $value) {
      $summary[$key] = (string) $value;
    }
    $this->assertEquals($expected, $summary);
  }

  /**
   * @covers ::getSummary
   */
  public function testGetSummaryNoReferenceBySet() {
    $target_plugin = $this->createTargetPluginInstance([
      'reference_by' => NULL,
    ]);

    $expected = [
      'Please select a field to reference by.',
    ];
    $summary = $target_plugin->getSummary();
    foreach ($summary as $key => $value) {
      $summary[$key] = (string) $value;
    }
    $this->assertEquals($expected, $summary);
  }

}
