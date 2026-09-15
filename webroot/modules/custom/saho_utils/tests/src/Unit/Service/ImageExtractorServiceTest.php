<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_utils\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\saho_utils\Service\ImageExtractorService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the article image fallback chain in ImageExtractorService.
 *
 * @group saho_utils
 * @coversDefaultClass \Drupal\saho_utils\Service\ImageExtractorService
 */
final class ImageExtractorServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  private ImageExtractorService $extractor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $url_generator = $this->createMock(FileUrlGeneratorInterface::class);
    $url_generator->method('generateAbsoluteString')->willReturnCallback(fn(string $uri) => 'https://example.test/' . basename($uri));
    $this->extractor = new ImageExtractorService(
      $this->createMock(EntityTypeManagerInterface::class),
      $url_generator,
    );
  }

  /**
   * Builds a file mock with the given URI.
   */
  private function file(string $uri): FileInterface {
    $file = $this->createMock(FileInterface::class);
    $file->method('getFileUri')->willReturn($uri);
    return $file;
  }

  /**
   * Builds a field item whose 'entity' property resolves to $target.
   */
  private function item(string $type, ?string $target_type, $target): FieldItemInterface {
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->method('getType')->willReturn($type);
    $definition->method('getSetting')->willReturnCallback(fn(string $key) => $key === 'target_type' ? $target_type : NULL);
    $entity_property = $this->createMock(TypedDataInterface::class);
    $entity_property->method('getValue')->willReturn($target);
    $item = $this->createMock(FieldItemInterface::class);
    $item->method('getFieldDefinition')->willReturn($definition);
    $item->method('get')->willReturnCallback(fn(string $name) => $name === 'entity' ? $entity_property : $this->createMock(TypedDataInterface::class));
    return $item;
  }

  /**
   * Builds a field list; NULL item means an empty field.
   */
  private function fieldList(?FieldItemInterface $item): FieldItemListInterface {
    $list = $this->createMock(FieldItemListInterface::class);
    $list->method('isEmpty')->willReturn($item === NULL);
    $list->method('first')->willReturn($item);
    return $list;
  }

  /**
   * Builds a media mock whose source field holds $file (or nothing).
   */
  private function media(?FileInterface $file): MediaInterface {
    $source = $this->createMock(MediaSourceInterface::class);
    $source->method('getConfiguration')->willReturn(['source_field' => 'field_media_image']);
    $media = $this->createMock(MediaInterface::class);
    $media->method('getSource')->willReturn($source);
    $media->method('hasField')->willReturn(TRUE);
    $media->method('get')->willReturn($this->fieldList($file ? $this->item('image', NULL, $file) : NULL));
    return $media;
  }

  /**
   * Builds an article whose fields are given as name => FieldItemList.
   */
  private function article(array $fields): ContentEntityInterface {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('bundle')->willReturn('article');
    $entity->method('hasField')->willReturnCallback(fn(string $name) => isset($fields[$name]));
    $entity->method('get')->willReturnCallback(fn(string $name) => $fields[$name]);
    return $entity;
  }

  /**
   * @covers ::findImageFieldsForContentType
   * @covers ::findImageFieldForContentType
   */
  public function testArticleCandidateChain(): void {
    $this->assertSame(
      ['field_article_image', 'field_image', 'field_main_image'],
      $this->extractor->findImageFieldsForContentType('article')
    );
    $this->assertSame('field_article_image', $this->extractor->findImageFieldForContentType('article'));
    $this->assertSame(['field_bio_pic'], $this->extractor->findImageFieldsForContentType('biography'));
    $this->assertSame(['field_image'], $this->extractor->findImageFieldsForContentType('something_else'));
  }

  /**
   * The legacy upload keeps winning when both fields are populated.
   *
   * @covers ::findImageFieldForEntity
   * @covers ::extractImageUrl
   */
  public function testLegacyUploadLeadsWhenBothPresent(): void {
    $article = $this->article([
      'field_article_image' => $this->fieldList($this->item('image', NULL, $this->file('public://legacy.jpg'))),
      'field_image' => $this->fieldList(NULL),
      'field_main_image' => $this->fieldList($this->item('entity_reference', 'media', $this->media($this->file('public://media.jpg')))),
    ]);
    $this->assertSame('field_article_image', $this->extractor->findImageFieldForEntity($article));
    $this->assertSame('https://example.test/legacy.jpg', $this->extractor->extractImageUrl($article));
  }

  /**
   * A media-only article resolves through the media source field.
   *
   * @covers ::findImageFieldForEntity
   * @covers ::extractImageUrl
   * @covers ::resolveFile
   * @covers ::hasImage
   */
  public function testMediaImageIsTheFallback(): void {
    $article = $this->article([
      'field_article_image' => $this->fieldList(NULL),
      'field_image' => $this->fieldList(NULL),
      'field_main_image' => $this->fieldList($this->item('entity_reference', 'media', $this->media($this->file('public://media.jpg')))),
    ]);
    $this->assertSame('field_main_image', $this->extractor->findImageFieldForEntity($article));
    $this->assertSame('https://example.test/media.jpg', $this->extractor->extractImageUrl($article));
    $this->assertSame('public://media.jpg', $this->extractor->resolveFile($article, 'field_main_image')->getFileUri());
    $this->assertTrue($this->extractor->hasImage($article));
  }

  /**
   * A media item without a source file yields nothing, never an error.
   *
   * @covers ::extractImageUrl
   * @covers ::resolveFile
   */
  public function testMediaWithoutFileYieldsNull(): void {
    $article = $this->article([
      'field_article_image' => $this->fieldList(NULL),
      'field_image' => $this->fieldList(NULL),
      'field_main_image' => $this->fieldList($this->item('entity_reference', 'media', $this->media(NULL))),
    ]);
    $this->assertNull($this->extractor->extractImageUrl($article));
    $this->assertNull($this->extractor->resolveFile($article, 'field_main_image'));
  }

  /**
   * A dangling media reference (target gone) yields NULL.
   *
   * @covers ::extractImageUrl
   */
  public function testDanglingMediaReferenceYieldsNull(): void {
    $article = $this->article([
      'field_article_image' => $this->fieldList(NULL),
      'field_image' => $this->fieldList(NULL),
      'field_main_image' => $this->fieldList($this->item('entity_reference', 'media', NULL)),
    ]);
    $this->assertNull($this->extractor->extractImageUrl($article));
  }

  /**
   * No populated candidate: nothing found, hasImage() is FALSE.
   *
   * @covers ::findImageFieldForEntity
   * @covers ::hasImage
   */
  public function testNoImageAtAll(): void {
    $article = $this->article([
      'field_article_image' => $this->fieldList(NULL),
      'field_image' => $this->fieldList(NULL),
      'field_main_image' => $this->fieldList(NULL),
    ]);
    $this->assertNull($this->extractor->findImageFieldForEntity($article));
    $this->assertFalse($this->extractor->hasImage($article));
    $this->assertNull($this->extractor->extractImageUrl($article));
  }

}
