<?php

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\Component\Utility\Html;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\Plugin\migrate\process\Dom;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the dom process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\Dom
 */
class DomTest extends MigrateProcessTestCase {

  /**
   * @covers ::__construct
   */
  public function testConfigMethodEmpty() {
    $configuration = [];
    $value = '<p>A simple paragraph.</p>';
    $this->setExpectedException(\InvalidArgumentException::class, 'The "method" must be set.');
    (new Dom($configuration, 'dom', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::__construct
   */
  public function testConfigMethodInvalid() {
    $configuration['method'] = 'invalid';
    $value = '<p>A simple paragraph.</p>';
    $this->setExpectedException(\InvalidArgumentException::class, 'The "method" must be "import" or "export".');
    (new Dom($configuration, 'dom', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::import
   */
  public function testImportNonRoot() {
    $configuration['method'] = 'import';
    $value = '<p>A simple paragraph.</p>';
    $document = (new Dom($configuration, 'dom', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertTrue($document instanceof \DOMDocument);
  }

  /**
   * @covers ::import
   */
  public function testImportNonRootInvalidInput() {
    $configuration['method'] = 'import';
    $value = [1, 1];
    $this->setExpectedException(MigrateException::class, 'Cannot import a non-string value.');
    (new Dom($configuration, 'dom', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::export
   */
  public function testExportNonRoot() {
    $configuration['method'] = 'export';
    $partial = '<p>A simple paragraph.</p>';
    $document = Html::load($partial);
    $value = (new Dom($configuration, 'dom', []))
      ->transform($document, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($value, $partial);
  }

  /**
   * @covers ::export
   */
  public function testExportNonRootInvalidInput() {
    $configuration['method'] = 'export';
    $this->setExpectedException(MigrateException::class, 'Cannot export a "string".');
    (new Dom($configuration, 'dom', []))
      ->transform('string is not DOMDocument', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
