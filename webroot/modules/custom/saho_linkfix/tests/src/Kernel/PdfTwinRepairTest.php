<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_linkfix\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;

/**
 * Broken archive PDFs are swapped for their genuine twin, reversibly.
 *
 * @group saho_linkfix
 */
final class PdfTwinRepairTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'field', 'file', 'entity_test', 'saho_linkfix'];

  /**
   * The service under test.
   *
   * @var \Drupal\saho_linkfix\Service\PdfTwinRepair
   */
  protected $repair;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('entity_test');
    $this->installSchema('file', ['file_usage']);
    FieldStorageConfig::create([
      'field_name' => 'field_doc',
      'entity_type' => 'entity_test',
      'type' => 'file',
      'cardinality' => -1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_doc',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => ['file_extensions' => 'pdf'],
    ])->save();
    $this->repair = $this->container->get('saho_linkfix.pdf_twin_repair');
  }

  /**
   * Write a file under public:// and optionally register a file entity.
   */
  protected function file(string $relative, string $contents, bool $managed = TRUE): ?File {
    $dir = dirname('public://' . $relative);
    \Drupal::service('file_system')->prepareDirectory($dir, 1);
    file_put_contents('public://' . $relative, $contents);
    if (!$managed) {
      return NULL;
    }
    $file = File::create(['uri' => 'public://' . $relative, 'filename' => basename($relative), 'status' => 1]);
    $file->save();
    return $file;
  }

  /**
   * Row of a scan for one file id.
   */
  protected function scanRow(int $fid): ?array {
    foreach ($this->repair->scan()['rows'] as $row) {
      if ($row['fid'] === $fid) {
        return $row;
      }
    }
    return NULL;
  }

  /**
   * HTML-as-.pdf with a managed twin: swap, idempotent re-run, rollback.
   */
  public function testSwapToManagedTwinAndRollback(): void {
    $broken = $this->file('2019-05/abjun73.pdf', '<!DOCTYPE html><html></html>');
    $twin = $this->file('archive-files/AbJun73.pdf', "%PDF-1.6\n");
    $entity = EntityTest::create(['field_doc' => [['target_id' => $broken->id(), 'description' => 'Issue 8']]]);
    $entity->save();

    $row = $this->scanRow((int) $broken->id());
    $this->assertSame('not_pdf', $row['status']);
    $this->assertSame('swap', $row['action']);
    $this->assertSame((int) $twin->id(), $row['twin_fid']);

    // Dry run changes nothing.
    $this->repair->apply([$row], TRUE);
    $this->assertSame((int) $broken->id(), (int) EntityTest::load($entity->id())->get('field_doc')->first()->get('target_id')->getValue());

    $result = $this->repair->apply([$row], FALSE);
    $this->assertSame(0, $result['stats']['errors']);
    $this->assertSame(1, $result['stats']['swapped_items']);
    $reloaded = EntityTest::load($entity->id());
    $this->assertSame((int) $twin->id(), (int) $reloaded->get('field_doc')->first()->get('target_id')->getValue());
    $this->assertSame('Issue 8', $reloaded->get('field_doc')->first()->get('description')->getValue(), 'Item metadata survives the swap.');

    // Re-scan: nothing references the broken file any more.
    $this->assertSame('unused', $this->scanRow((int) $broken->id())['action']);

    $this->repair->revert($result['rollback'], FALSE);
    $this->container->get('entity_type.manager')->getStorage('entity_test')->resetCache();
    $this->assertSame((int) $broken->id(), (int) EntityTest::load($entity->id())->get('field_doc')->first()->get('target_id')->getValue());
  }

  /**
   * An entity that already holds the twin loses the broken duplicate.
   */
  public function testDuplicateIsRemovedNotDoubled(): void {
    $broken = $this->file('2019-05/dup.pdf', '<!DOCTYPE html>');
    $twin = $this->file('archive-files/dup.pdf', "%PDF-1.4\n");
    $entity = EntityTest::create(['field_doc' => [$broken->id(), $twin->id()]]);
    $entity->save();

    $result = $this->repair->apply([$this->scanRow((int) $broken->id())], FALSE);
    $this->assertSame(1, $result['stats']['removed_duplicates']);
    $fids = array_column(EntityTest::load($entity->id())->get('field_doc')->getValue(), 'target_id');
    $this->assertSame([(int) $twin->id()], array_map('intval', $fids));
  }

  /**
   * A twin without a file entity: the broken entity is repointed instead.
   */
  public function testRepointToUnmanagedTwin(): void {
    $broken = $this->file('archivefiles/Acn1.pdf', '%PDF-1.4');
    unlink('public://archivefiles/Acn1.pdf');
    $this->file('archive-files2/Acn1.pdf', "%PDF-1.4\nbody\n", FALSE);
    EntityTest::create(['field_doc' => [$broken->id()]])->save();

    $row = $this->scanRow((int) $broken->id());
    $this->assertSame('missing', $row['status']);
    $this->assertSame('repoint', $row['action']);

    $result = $this->repair->apply([$row], FALSE);
    $this->assertSame(1, $result['stats']['repointed']);
    $this->assertSame('public://archive-files2/Acn1.pdf', File::load($broken->id())->getFileUri());

    $this->repair->revert($result['rollback'], FALSE);
    $this->assertSame('public://archivefiles/Acn1.pdf', File::load($broken->id())->getFileUri());
  }

  /**
   * A genuine PDF whose name contains ".." is not reported as broken.
   */
  public function testDoubleDotFileNameIsOk(): void {
    $file = $this->file('archive-files2/let19710610.745..pdf', "%PDF-1.4\n");
    EntityTest::create(['field_doc' => [$file->id()]])->save();
    $this->assertNull($this->scanRow((int) $file->id()), 'Genuine double-dot PDF is not listed as broken.');
  }

  /**
   * No genuine copy anywhere: reported as missing, never touched.
   */
  public function testNoTwinIsReportedMissing(): void {
    $broken = $this->file('2019-05/lost.pdf', '<!DOCTYPE html>');
    $this->file('archive-files/lost.pdf', '');
    EntityTest::create(['field_doc' => [$broken->id()]])->save();

    $row = $this->scanRow((int) $broken->id());
    $this->assertSame('missing', $row['action']);
    $this->assertSame(0, $this->repair->apply([$row], FALSE)['stats']['files']);
  }

}
