<?php

namespace Drupal\Tests\saho_utils\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\saho_utils\Service\ContentExtractorService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ContentExtractorService.
 *
 * @group saho_utils
 * @coversDefaultClass \Drupal\saho_utils\Service\ContentExtractorService
 */
class ContentExtractorServiceTest extends UnitTestCase {

  /**
   * The content extractor service.
   *
   * @var \Drupal\saho_utils\Service\ContentExtractorService
   */
  protected $contentExtractor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->contentExtractor = new ContentExtractorService();
  }

  /**
   * @covers ::extractTeaser
   */
  public function testExtractTeaserFromBody() {
    $entity = $this->createMock(ContentEntityInterface::class);

    $field_item = $this->createMock(FieldItemInterface::class);
    $field_item->value = '<p>This is a long article with HTML tags and <strong>formatting</strong>. It should be truncated properly at word boundaries to create a nice teaser.</p>';

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('first')->willReturn($field_item);

    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($field_list);

    $teaser = $this->contentExtractor->extractTeaser($entity, 50);

    $this->assertIsString($teaser);
    // 50 + "..."
    $this->assertLessThanOrEqual(53, strlen($teaser));
    $this->assertStringNotContainsString('<p>', $teaser);
    $this->assertStringNotContainsString('<strong>', $teaser);
  }

  /**
   * @covers ::extractTeaser
   */
  public function testExtractTeaserWithCustomLength() {
    $entity = $this->createMock(ContentEntityInterface::class);

    $field_item = $this->createMock(FieldItemInterface::class);
    $field_item->value = 'Short text';

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('first')->willReturn($field_item);

    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($field_list);

    $teaser = $this->contentExtractor->extractTeaser($entity, 20);

    $this->assertEquals('Short text', $teaser);
    $this->assertStringNotContainsString('...', $teaser);
  }

  /**
   * @covers ::extractTeaser
   */
  public function testExtractTeaserNoBodyField() {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('hasField')->willReturn(FALSE);

    $teaser = $this->contentExtractor->extractTeaser($entity);

    $this->assertEquals('', $teaser);
  }

  /**
   * @covers ::extractTeaser
   */
  public function testExtractTeaserEmptyBody() {
    $entity = $this->createMock(ContentEntityInterface::class);

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('first')->willReturn(NULL);

    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($field_list);

    $teaser = $this->contentExtractor->extractTeaser($entity);

    $this->assertEquals('', $teaser);
  }

  /**
   * @covers ::extractTeaser
   */
  public function testExtractTeaserSpecificField() {
    $entity = $this->createMock(ContentEntityInterface::class);

    $field_item = $this->createMock(FieldItemInterface::class);
    $field_item->value = 'Custom field content';

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('first')->willReturn($field_item);

    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($field_list);

    $teaser = $this->contentExtractor->extractTeaser($entity, 150, 'field_description');

    $this->assertEquals('Custom field content', $teaser);
  }

  /**
   * @covers ::extractSummary
   */
  public function testExtractSummaryFromSummaryField() {
    $entity = $this->createMock(ContentEntityInterface::class);

    $field_item = $this->createMock(FieldItemInterface::class);
    $field_item->summary = 'This is the summary';

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('first')->willReturn($field_item);

    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($field_list);

    $summary = $this->contentExtractor->extractSummary($entity);

    $this->assertEquals('This is the summary', $summary);
  }

  /**
   * @covers ::extractSummary
   */
  public function testExtractSummaryFallbackToBody() {
    $entity = $this->createMock(ContentEntityInterface::class);

    $field_item = $this->createMock(FieldItemInterface::class);
    $field_item->summary = '';
    $field_item->value = 'Body content used as summary';

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('first')->willReturn($field_item);

    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($field_list);

    $summary = $this->contentExtractor->extractSummary($entity);

    $this->assertStringContainsString('Body content', $summary);
  }

  /**
   * @covers ::extractBodyText
   */
  public function testExtractBodyTextWithStripping() {
    $entity = $this->createMock(ContentEntityInterface::class);

    $field_item = $this->createMock(FieldItemInterface::class);
    $field_item->value = '<p>HTML <strong>content</strong> here</p>';

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('first')->willReturn($field_item);

    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($field_list);

    $text = $this->contentExtractor->extractBodyText($entity, TRUE);

    $this->assertEquals('HTML content here', $text);
    $this->assertStringNotContainsString('<p>', $text);
  }

  /**
   * @covers ::extractBodyText
   */
  public function testExtractBodyTextWithoutStripping() {
    $entity = $this->createMock(ContentEntityInterface::class);

    $field_item = $this->createMock(FieldItemInterface::class);
    $field_item->value = '<p>HTML <strong>content</strong> here</p>';

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('first')->willReturn($field_item);

    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($field_list);

    $text = $this->contentExtractor->extractBodyText($entity, FALSE);

    $this->assertEquals('<p>HTML <strong>content</strong> here</p>', $text);
    $this->assertStringContainsString('<p>', $text);
  }

  /**
   * @covers ::extractBodyText
   */
  public function testExtractBodyTextNoField() {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('hasField')->willReturn(FALSE);

    $text = $this->contentExtractor->extractBodyText($entity);

    $this->assertEquals('', $text);
  }

}
