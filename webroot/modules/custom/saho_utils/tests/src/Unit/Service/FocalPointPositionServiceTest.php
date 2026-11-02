<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_utils\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\ImageInterface;
use Drupal\crop\CropInterface;
use Drupal\crop\CropStorage;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_utils\Service\FocalPointPositionService;
use Drupal\saho_utils\Service\ImageExtractorService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the focal point to object-position resolver.
 *
 * @coversDefaultClass \Drupal\saho_utils\Service\FocalPointPositionService
 * @group saho_utils
 */
class FocalPointPositionServiceTest extends UnitTestCase {

  /**
   * Builds the service around a crop storage that returns $crop.
   */
  private function service(?CropInterface $crop, bool $crop_module = TRUE, ?ImageExtractorService $extractor = NULL, ?ImageFactory $factory = NULL): FocalPointPositionService {
    $storage = $this->createMock(CropStorage::class);
    $storage->method('getCrop')->willReturn($crop);
    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->method('hasDefinition')->with('crop')->willReturn($crop_module);
    $etm->method('getStorage')->with('crop')->willReturn($storage);
    return new FocalPointPositionService(
      $etm,
      $extractor ?? $this->createMock(ImageExtractorService::class),
      $factory ?? $this->createMock(ImageFactory::class),
    );
  }

  /**
   * Builds a crop whose anchor is at ($x, $y).
   */
  private function crop(int $x, int $y): CropInterface {
    $crop = $this->createMock(CropInterface::class);
    $crop->method('anchor')->willReturn(['x' => $x, 'y' => $y]);
    return $crop;
  }

  /**
   * @covers ::objectPosition
   */
  public function testResolvesAnchorAgainstKnownDimensions(): void {
    $service = $this->service($this->crop(181, 186));
    // 181/602 = 30.07%, 186/745 = 24.97%.
    $this->assertSame('30% 25%', $service->objectPosition('public://bio_pics/hazel.jpg', 602, 745));
  }

  /**
   * @covers ::objectPosition
   */
  public function testNoCropMeansNoPosition(): void {
    $service = $this->service(NULL);
    $this->assertNull($service->objectPosition('public://x.jpg', 100, 100));
  }

  /**
   * @covers ::objectPosition
   */
  public function testWithoutCropModuleNothingIsLookedUp(): void {
    $service = $this->service($this->crop(1, 1), FALSE);
    $this->assertNull($service->objectPosition('public://x.jpg', 100, 100));
  }

  /**
   * @covers ::objectPosition
   */
  public function testDerivativeUrlsAndEmptyUrisAreIgnored(): void {
    $service = $this->service($this->crop(1, 1));
    $derivative = 'https://example.org/sites/default/files/styles/large/public/x.jpg';
    $this->assertNull($service->objectPosition($derivative, 100, 100));
    $this->assertNull($service->objectPosition('', 100, 100));
  }

  /**
   * @covers ::objectPosition
   */
  public function testDimensionsAreReadFromTheImageWhenUnknown(): void {
    $image = $this->createMock(ImageInterface::class);
    $image->method('isValid')->willReturn(TRUE);
    $image->method('getWidth')->willReturn(200);
    $image->method('getHeight')->willReturn(100);
    $factory = $this->createMock(ImageFactory::class);
    $factory->method('get')->with('public://x.jpg')->willReturn($image);
    $service = $this->service($this->crop(50, 75), TRUE, NULL, $factory);
    $this->assertSame('25% 75%', $service->objectPosition('public://x.jpg'));
  }

  /**
   * @covers ::objectPosition
   */
  public function testInvalidImageWithoutDimensionsYieldsNull(): void {
    $image = $this->createMock(ImageInterface::class);
    $image->method('isValid')->willReturn(FALSE);
    $factory = $this->createMock(ImageFactory::class);
    $factory->method('get')->willReturn($image);
    $service = $this->service($this->crop(50, 75), TRUE, NULL, $factory);
    $this->assertNull($service->objectPosition('public://x.jpg'));
  }

  /**
   * @covers ::forEntity
   */
  public function testForEntityUsesTheExtractorForFileAndDimensions(): void {
    $file = $this->createMock(FileInterface::class);
    $file->method('getFileUri')->willReturn('public://pic.jpg');
    $node = $this->createMock(NodeInterface::class);
    $extractor = $this->createMock(ImageExtractorService::class);
    $extractor->method('resolveFile')->with($node, 'field_bio_pic')->willReturn($file);
    $extractor->method('extractImageDimensions')
      ->with($node, 'field_bio_pic')
      ->willReturn(['width' => 400, 'height' => 200]);
    $service = $this->service($this->crop(100, 150), TRUE, $extractor);
    $this->assertSame('25% 75%', $service->forEntity($node, 'field_bio_pic'));
  }

  /**
   * @covers ::forEntity
   */
  public function testForEntityWithoutAnImageFieldYieldsNull(): void {
    $node = $this->createMock(NodeInterface::class);
    $extractor = $this->createMock(ImageExtractorService::class);
    $extractor->method('findImageFieldForEntity')->willReturn(NULL);
    $service = $this->service($this->crop(1, 1), TRUE, $extractor);
    $this->assertNull($service->forEntity($node));
  }

  /**
   * @covers ::format
   * @dataProvider formatProvider
   */
  public function testFormatClampsAndRounds(int $x, int $y, int $w, int $h, ?string $expected): void {
    $this->assertSame($expected, FocalPointPositionService::format($x, $y, $w, $h));
  }

  /**
   * Data for testFormatClampsAndRounds.
   */
  public static function formatProvider(): array {
    return [
      'centre' => [50, 50, 100, 100, '50% 50%'],
      'rounding' => [181, 186, 602, 745, '30% 25%'],
      'clamped above' => [150, 120, 100, 100, '100% 100%'],
      'clamped below' => [-5, -5, 100, 100, '0% 0%'],
      'zero width' => [10, 10, 0, 100, NULL],
    ];
  }

}
