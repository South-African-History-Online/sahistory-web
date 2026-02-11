<?php

namespace Drupal\Tests\saho_utils\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\saho_utils\Service\ContentExtractorService;
use Drupal\saho_utils\Service\EntityItemBuilderService;
use Drupal\saho_utils\Service\ImageExtractorService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the EntityItemBuilderService.
 *
 * @group saho_utils
 * @coversDefaultClass \Drupal\saho_utils\Service\EntityItemBuilderService
 */
class EntityItemBuilderServiceTest extends UnitTestCase {

  /**
   * The entity item builder service.
   *
   * @var \Drupal\saho_utils\Service\EntityItemBuilderService
   */
  protected $entityItemBuilder;

  /**
   * The image extractor service mock.
   *
   * @var \Drupal\saho_utils\Service\ImageExtractorService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $imageExtractor;

  /**
   * The content extractor service mock.
   *
   * @var \Drupal\saho_utils\Service\ContentExtractorService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $contentExtractor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->imageExtractor = $this->createMock(ImageExtractorService::class);
    $this->contentExtractor = $this->createMock(ContentExtractorService::class);

    $this->entityItemBuilder = new EntityItemBuilderService(
      $this->imageExtractor,
      $this->contentExtractor
    );
  }

  /**
   * @covers ::buildBasicItem
   */
  public function testBuildBasicItem() {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn('123');
    $entity->method('label')->willReturn('Test Article');

    $url = $this->createMock(Url::class);
    $url->method('toString')->willReturn('/node/123');
    $entity->method('toUrl')->willReturn($url);

    $item = $this->entityItemBuilder->buildBasicItem($entity);

    $this->assertIsArray($item);
    $this->assertEquals('123', $item['id']);
    $this->assertEquals('Test Article', $item['title']);
    $this->assertEquals('/node/123', $item['url']);
  }

  /**
   * @covers ::buildItemWithImage
   */
  public function testBuildItemWithImage() {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn('456');
    $entity->method('label')->willReturn('Article with Image');

    $url = $this->createMock(Url::class);
    $url->method('toString')->willReturn('/node/456');
    $entity->method('toUrl')->willReturn($url);

    $this->imageExtractor->method('extractImageUrl')
      ->willReturn('https://example.com/image.jpg');

    $item = $this->entityItemBuilder->buildItemWithImage($entity);

    $this->assertIsArray($item);
    $this->assertEquals('456', $item['id']);
    $this->assertEquals('Article with Image', $item['title']);
    $this->assertEquals('https://example.com/image.jpg', $item['image']);
  }

  /**
   * @covers ::buildItemWithImage
   */
  public function testBuildItemWithImageNoImage() {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn('789');
    $entity->method('label')->willReturn('Article without Image');

    $url = $this->createMock(Url::class);
    $url->method('toString')->willReturn('/node/789');
    $entity->method('toUrl')->willReturn($url);

    $this->imageExtractor->method('extractImageUrl')
      ->willReturn(NULL);

    $item = $this->entityItemBuilder->buildItemWithImage($entity);

    $this->assertIsArray($item);
    $this->assertEquals('789', $item['id']);
    $this->assertNull($item['image']);
  }

  /**
   * @covers ::buildItemWithTeaser
   */
  public function testBuildItemWithTeaser() {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn('101');
    $entity->method('label')->willReturn('Article with Teaser');

    $url = $this->createMock(Url::class);
    $url->method('toString')->willReturn('/node/101');
    $entity->method('toUrl')->willReturn($url);

    $this->imageExtractor->method('extractImageUrl')
      ->willReturn('https://example.com/teaser.jpg');

    $this->contentExtractor->method('extractTeaser')
      ->willReturn('This is a teaser excerpt...');

    $item = $this->entityItemBuilder->buildItemWithTeaser($entity, 150);

    $this->assertIsArray($item);
    $this->assertEquals('101', $item['id']);
    $this->assertEquals('Article with Teaser', $item['title']);
    $this->assertEquals('https://example.com/teaser.jpg', $item['image']);
    $this->assertEquals('This is a teaser excerpt...', $item['teaser']);
  }

  /**
   * @covers ::buildFullItem
   */
  public function testBuildFullItem() {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn('202');
    $entity->method('label')->willReturn('Full Article');

    $url = $this->createMock(Url::class);
    $url->method('toString')->willReturn('/node/202');
    $entity->method('toUrl')->willReturn($url);

    // Use timestamps that work across timezones (noon UTC).
    // 1234526400 = 2009-02-13 12:00:00 UTC.
    // 1234612800 = 2009-02-14 12:00:00 UTC.
    $createdField = $this->createMock('\Drupal\Core\Field\FieldItemListInterface');
    $createdField->method('isEmpty')->willReturn(FALSE);
    $createdField->method('__get')->with('value')->willReturn(1234526400);
    $entity->method('hasField')->willReturnCallback(function ($field) {
      return in_array($field, ['created', 'changed']);
    });
    $entity->method('get')->willReturnCallback(function ($field) use ($createdField) {
      if ($field === 'created') {
        return $createdField;
      }
      if ($field === 'changed') {
        $changedField = $this->createMock('\Drupal\Core\Field\FieldItemListInterface');
        $changedField->method('isEmpty')->willReturn(FALSE);
        $changedField->method('__get')->with('value')->willReturn(1234612800);
        return $changedField;
      }
      return NULL;
    });

    $this->imageExtractor->method('extractImageUrl')
      ->willReturn('https://example.com/full.jpg');

    $this->contentExtractor->method('extractTeaser')
      ->willReturn('Full article teaser');

    $item = $this->entityItemBuilder->buildFullItem($entity);

    $this->assertIsArray($item);
    $this->assertEquals('202', $item['id']);
    $this->assertEquals('Full Article', $item['title']);
    $this->assertEquals('https://example.com/full.jpg', $item['image']);
    $this->assertEquals('Full article teaser', $item['teaser']);
    $this->assertEquals('2009-02-13', $item['created']);
    $this->assertEquals('2009-02-14', $item['changed']);
  }

  /**
   * @covers ::buildFullItem
   */
  public function testBuildFullItemWithOptions() {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn('303');
    $entity->method('label')->willReturn('Custom Options Article');

    $url = $this->createMock(Url::class);
    $url->method('toString')->willReturn('/node/303');
    $entity->method('toUrl')->willReturn($url);

    // Mock created field with timezone-safe timestamp.
    // 1234526400 = 2009-02-13 12:00:00 UTC.
    $createdField = $this->createMock('\Drupal\Core\Field\FieldItemListInterface');
    $createdField->method('isEmpty')->willReturn(FALSE);
    $createdField->method('__get')->with('value')->willReturn(1234526400);
    $entity->method('hasField')->willReturnCallback(function ($field) {
      return $field === 'created';
    });
    $entity->method('get')->willReturnCallback(function ($field) use ($createdField) {
      if ($field === 'created') {
        return $createdField;
      }
      return NULL;
    });

    $this->imageExtractor->method('extractImageUrl')
      ->willReturn('https://example.com/custom.jpg');

    $this->contentExtractor->method('extractTeaser')
      ->willReturn('Custom teaser with specific length');

    $options = [
      'teaser_length' => 100,
      'image_style' => 'thumbnail',
    ];

    $item = $this->entityItemBuilder->buildFullItem($entity, $options);

    $this->assertIsArray($item);
    $this->assertEquals('303', $item['id']);
    $this->assertEquals('Custom teaser with specific length', $item['teaser']);
  }

  /**
   * @covers ::buildMultipleItems
   */
  public function testBuildMultipleItemsBasic() {
    $entity1 = $this->createMock(ContentEntityInterface::class);
    $entity1->method('id')->willReturn('1');
    $entity1->method('label')->willReturn('Article 1');
    $url1 = $this->createMock(Url::class);
    $url1->method('toString')->willReturn('/node/1');
    $entity1->method('toUrl')->willReturn($url1);

    $entity2 = $this->createMock(ContentEntityInterface::class);
    $entity2->method('id')->willReturn('2');
    $entity2->method('label')->willReturn('Article 2');
    $url2 = $this->createMock(Url::class);
    $url2->method('toString')->willReturn('/node/2');
    $entity2->method('toUrl')->willReturn($url2);

    $entities = [1 => $entity1, 2 => $entity2];

    $items = $this->entityItemBuilder->buildMultipleItems($entities, 'basic');

    $this->assertIsArray($items);
    $this->assertCount(2, $items);
    $this->assertEquals('1', $items[0]['id']);
    $this->assertEquals('2', $items[1]['id']);
  }

  /**
   * @covers ::buildMultipleItems
   */
  public function testBuildMultipleItemsWithImage() {
    $entity1 = $this->createMock(ContentEntityInterface::class);
    $entity1->method('id')->willReturn('10');
    $entity1->method('label')->willReturn('Image Article 1');
    $url1 = $this->createMock(Url::class);
    $url1->method('toString')->willReturn('/node/10');
    $entity1->method('toUrl')->willReturn($url1);

    $entity2 = $this->createMock(ContentEntityInterface::class);
    $entity2->method('id')->willReturn('20');
    $entity2->method('label')->willReturn('Image Article 2');
    $url2 = $this->createMock(Url::class);
    $url2->method('toString')->willReturn('/node/20');
    $entity2->method('toUrl')->willReturn($url2);

    $this->imageExtractor->method('extractImageUrl')
      ->willReturnOnConsecutiveCalls(
        'https://example.com/img1.jpg',
        'https://example.com/img2.jpg'
      );

    $entities = [10 => $entity1, 20 => $entity2];

    $items = $this->entityItemBuilder->buildMultipleItems($entities, 'with_image');

    $this->assertIsArray($items);
    $this->assertCount(2, $items);
    $this->assertEquals('https://example.com/img1.jpg', $items[0]['image']);
    $this->assertEquals('https://example.com/img2.jpg', $items[1]['image']);
  }

  /**
   * @covers ::buildMultipleItems
   */
  public function testBuildMultipleItemsEmpty() {
    $items = $this->entityItemBuilder->buildMultipleItems([]);

    $this->assertIsArray($items);
    $this->assertEmpty($items);
  }

  /**
   * @covers ::buildCustomItem
   */
  public function testBuildCustomItem() {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('id')->willReturn('999');
    $entity->method('label')->willReturn('Custom Item');

    $url = $this->createMock(Url::class);
    $url->method('toString')->willReturn('/node/999');
    $entity->method('toUrl')->willReturn($url);

    $fields = ['id', 'title'];
    $item = $this->entityItemBuilder->buildCustomItem($entity, $fields);

    $this->assertIsArray($item);
    $this->assertCount(2, $item);
    $this->assertEquals('999', $item['id']);
    $this->assertEquals('Custom Item', $item['title']);
    $this->assertArrayNotHasKey('url', $item);
  }

}
