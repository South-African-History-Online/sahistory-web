<?php

namespace Drupal\Tests\tamper\Unit\Adapter;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\tamper\Adapter\TamperableComplexDataAdapter;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\tamper\Adapter\TamperableComplexDataAdapter
 * @group tamper
 */
class TamperableComplexDataAdapterTest extends UnitTestCase {

  /**
   * Complex data object.
   *
   * @var \Drupal\Core\TypedData\ComplexDataInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $complexData;

  /**
   * Adapter for the complex data.
   *
   * @var \Drupal\tamper\Adapter\TamperableComplexDataAdapter
   */
  protected $adapter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->complexData = $this->createMock(ComplexDataInterface::class);
    $this->adapter = new TamperableComplexDataAdapter($this->complexData);
  }

  /**
   * @covers ::getSource
   */
  public function testGetSource() {
    $this->complexData->expects($this->once())
      ->method('toArray');

    $this->adapter->getSource();
  }

  /**
   * @covers ::getSourceProperty
   */
  public function testGetSourceProperty() {
    $this->complexData->expects($this->once())
      ->method('get')
      ->with('foo');

    $this->adapter->getSourceProperty('foo');
  }

  /**
   * @covers ::setSourceProperty
   */
  public function testSetSourceProperty() {
    $this->complexData->expects($this->once())
      ->method('set')
      ->with('foo', 'bar');

    $this->adapter->setSourceProperty('foo', 'bar');
  }

}
