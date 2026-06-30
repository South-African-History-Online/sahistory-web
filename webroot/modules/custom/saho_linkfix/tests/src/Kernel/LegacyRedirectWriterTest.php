<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_linkfix\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\redirect\Entity\Redirect;

/**
 * Safety invariants for the legacy redirect writer.
 *
 * @group saho_linkfix
 */
final class LegacyRedirectWriterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'link',
    'path_alias',
    'redirect',
    'saho_linkfix',
  ];

  /**
   * The redirect writer under test.
   *
   * @var \Drupal\saho_linkfix\Service\LegacyRedirectWriter
   */
  protected $writer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('redirect');
    $this->installEntitySchema('path_alias');
    $this->writer = $this->container->get('saho_linkfix.redirect_writer');
  }

  /**
   * Count redirects whose source path matches.
   */
  protected function countSource(string $source): int {
    return (int) $this->container->get('entity_type.manager')
      ->getStorage('redirect')->getQuery()
      ->accessCheck(FALSE)
      ->condition('redirect_source.path', $source)
      ->count()
      ->execute();
  }

  /**
   * Creating a redirect from a spec, then idempotency on re-run.
   */
  public function testCreatesAndIsIdempotent(): void {
    $specs = [['source_path' => 'pages/people/bios/dadoo,y.htm', 'uri' => 'internal:/node/42']];

    $first = $this->writer->apply($specs, ['dry_run' => FALSE]);
    $this->assertCount(1, $first['created']);
    $this->assertSame(1, $this->countSource('pages/people/bios/dadoo,y.htm'));

    // Re-running must not create a second redirect for the same source.
    $second = $this->writer->apply($specs, ['dry_run' => FALSE]);
    $this->assertCount(0, $second['created']);
    $this->assertSame(1, $second['stats']['skipped_existing']);
    $this->assertSame(1, $this->countSource('pages/people/bios/dadoo,y.htm'));
  }

  /**
   * An existing curated redirect must never be clobbered.
   */
  public function testDoesNotClobberExisting(): void {
    $existing = Redirect::create([
      'redirect_source' => ['path' => 'pages/x.htm', 'query' => []],
      'redirect_redirect' => ['uri' => 'internal:/curated-target'],
      'status_code' => 301,
      'language' => 'und',
    ]);
    $existing->save();

    $result = $this->writer->apply(
      [['source_path' => 'pages/x.htm', 'uri' => 'internal:/node/99']],
      ['dry_run' => FALSE],
    );

    $this->assertCount(0, $result['created']);
    $this->assertSame(1, $result['stats']['skipped_existing']);
    // Original target intact.
    $reloaded = Redirect::load($existing->id());
    $this->assertStringContainsString('curated-target', $reloaded->getRedirectUrl()->toString());
  }

  /**
   * Dry run creates nothing.
   */
  public function testDryRunWritesNothing(): void {
    $result = $this->writer->apply(
      [['source_path' => 'pages/y.htm', 'uri' => 'internal:/node/1']],
      ['dry_run' => TRUE],
    );
    $this->assertCount(0, $result['created']);
    $this->assertSame(0, $this->countSource('pages/y.htm'));
  }

  /**
   * A duplicate source within one batch creates only one redirect.
   */
  public function testDeduplicatesWithinBatch(): void {
    $specs = [
      ['source_path' => 'pages/z.htm', 'uri' => 'internal:/node/5'],
      ['source_path' => 'pages/z.htm', 'uri' => 'internal:/node/5'],
    ];
    $result = $this->writer->apply($specs, ['dry_run' => FALSE]);
    $this->assertCount(1, $result['created']);
    $this->assertSame(1, $result['stats']['skipped_duplicate']);
  }

  /**
   * Revert removes exactly the redirects a run created.
   */
  public function testRevertRemovesOnlyCreated(): void {
    $created = $this->writer->apply(
      [['source_path' => 'pages/revert-me.htm', 'uri' => 'internal:/node/7']],
      ['dry_run' => FALSE],
    )['created'];
    $this->assertSame(1, $this->countSource('pages/revert-me.htm'));

    $stats = $this->writer->revert($created, ['dry_run' => FALSE]);
    $this->assertSame(1, $stats['removed']);
    $this->assertSame(0, $this->countSource('pages/revert-me.htm'));
  }

}
