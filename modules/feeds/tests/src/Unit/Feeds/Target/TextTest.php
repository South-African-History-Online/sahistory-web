<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\Feeds\Target\Text;
use Drupal\feeds\FeedTypeInterface;
use Drupal\filter\FilterFormatInterface;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Text
 * @group feeds
 */
class TextTest extends FieldTargetTestBase {

  /**
   * The FeedsTarget plugin being tested.
   *
   * @var \Drupal\feeds\Feeds\Target\Text
   */
  protected $target;

  /**
   * A prophesized filter format.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $filter;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->filter = $this->prophesize(FilterFormatInterface::class);
    $this->filter->label()->willReturn('Test filter');

    $method = $this->getMethod(Text::class, 'prepareTarget')->getClosure();
    $configuration = [
      'feed_type' => $this->createMock(FeedTypeInterface::class),
      'target_definition' => $method($this->getMockFieldDefinition()),
    ];

    $this->target = $this->getMockBuilder(Text::class)
      ->setConstructorArgs([
        $configuration,
        'text',
        [],
        $this->createMock(AccountInterface::class),
      ])
      ->setMethods(['getFilterFormats'])
      ->getMock();
    $this->target->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Text::class;
  }

  /**
   * @covers ::prepareValue
   */
  public function testPrepareValue() {
    $method = $this->getProtectedClosure($this->target, 'prepareValue');

    $values = ['value' => 'longstring'];
    $method(0, $values);
    $this->assertSame('longstring', $values['value']);
    $this->assertSame('plain_text', $values['format']);
  }

  /**
   * @covers ::buildConfigurationForm
   */
  public function testBuildConfigurationForm() {
    $this->target->expects($this->once())
      ->method('getFilterFormats')
      ->willReturn(['test_format' => $this->filter->reveal()]);

    $form_state = new FormState();
    $form = $this->target->buildConfigurationForm([], $form_state);
    $this->assertSame(count($form), 1);
  }

  /**
   * @covers ::getSummary
   */
  public function testGetSummary() {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->any())
      ->method('loadByProperties')
      ->with(['status' => '1', 'format' => 'plain_text'])
      ->will($this->onConsecutiveCalls([$this->filter->reveal()], []));

    $manager = $this->createMock(EntityTypeManagerInterface::class);
    $manager->expects($this->exactly(2))
      ->method('getStorage')
      ->will($this->returnValue($storage));

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $manager);
    \Drupal::setContainer($container);

    $this->assertSame('Format: <em class="placeholder">Test filter</em>', (string) current($this->target->getSummary()));
    $this->assertEquals([], $this->target->getSummary());
  }

}
