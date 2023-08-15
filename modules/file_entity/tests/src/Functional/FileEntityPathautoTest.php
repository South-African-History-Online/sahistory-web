<?php

namespace Drupal\Tests\file_entity\Functional;

use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Tests Pathauto support.
 *
 * @dependencies pathauto
 *
 * @group file_entity
 */
class FileEntityPathautoTest extends FileEntityTestBase {

  use PathAliasTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('pathauto');

  /**
   * Tests Pathauto support.
   */
  public function testPathauto() {
    $pattern = PathautoPattern::create([
      'id' => mb_strtolower($this->randomMachineName()),
      'type' => 'canonical_entities:file',
      'pattern' => '/files/[file:name]',
      'weight' => 0,
    ]);
    $pattern->save();

    $file = $this->createFileEntity(['filename' => 'example.png']);

    $this->assertPathAliasExists('/files/examplepng', NULL, NULL, 'file alias exists');
  }

}
