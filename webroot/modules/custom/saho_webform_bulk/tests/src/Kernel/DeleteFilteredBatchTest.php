<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_webform_bulk\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\saho_webform_bulk\DeleteFilteredBatch;
use Drupal\saho_webform_bulk\FilteredSubmissionQuery;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests the filtered bulk delete against Webform's own search filter.
 *
 * @group saho_webform_bulk
 */
final class DeleteFilteredBatchTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'path',
    'path_alias',
    'field',
    'webform',
    'saho_webform_bulk',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('path_alias');
    $this->installSchema('webform', ['webform']);
    $this->installConfig('webform');
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');
  }

  /**
   * Deletes only the submissions matching the keyword, across batch chunks.
   */
  public function testDeletesOnlyMatchingSubmissionsInChunks(): void {
    $webform = Webform::create([
      'id' => 'bulk_test',
      'title' => 'Bulk test',
      'elements' => "message:\n  '#type': textfield\n  '#title': Message\n",
    ]);
    $webform->save();
    $other = Webform::create(['id' => 'other', 'title' => 'Other', 'elements' => "message:\n  '#type': textfield\n"]);
    $other->save();

    $spam = DeleteFilteredBatch::CHUNK * 2 + 3;
    for ($i = 0; $i < $spam; $i++) {
      $this->createSubmission($webform->id(), "cheap pills offer $i");
    }
    for ($i = 0; $i < 4; $i++) {
      $this->createSubmission($webform->id(), "genuine enquiry $i");
    }
    // Same keyword on another webform must be left alone.
    $this->createSubmission($other->id(), 'cheap pills elsewhere');

    $query = FilteredSubmissionQuery::forFilter($this->container, $webform, 'pills', '');
    $this->assertSame($spam, $query->total());
    $this->assertCount(DeleteFilteredBatch::CHUNK, $query->ids(DeleteFilteredBatch::CHUNK));

    $context = ['sandbox' => [], 'results' => [], 'finished' => 0];
    $passes = 0;
    do {
      DeleteFilteredBatch::process($webform->id(), 'pills', '', $context);
      $passes++;
    } while ($context['finished'] < 1 && $passes < 10);

    $this->assertSame(3, $passes, 'Two full chunks plus a partial one.');
    $this->assertSame($spam, $context['results']['deleted']);
    $this->assertSame(0, FilteredSubmissionQuery::forFilter($this->container, $webform, 'pills', '')->total());
    $this->assertSame(4, FilteredSubmissionQuery::forFilter($this->container, $webform, '', '')->total(), 'Genuine enquiries survive.');
    $this->assertSame(1, FilteredSubmissionQuery::forFilter($this->container, $other, '', '')->total(), 'Other webform untouched.');
  }

  /**
   * An empty filter targets every submission of the webform, and only it.
   */
  public function testEmptyFilterMeansWholeWebform(): void {
    $webform = Webform::create(['id' => 'all_test', 'title' => 'All', 'elements' => "message:\n  '#type': textfield\n"]);
    $webform->save();
    $other = Webform::create(['id' => 'keep', 'title' => 'Keep', 'elements' => "message:\n  '#type': textfield\n"]);
    $other->save();
    for ($i = 0; $i < 5; $i++) {
      $this->createSubmission($webform->id(), "row $i");
    }
    $this->createSubmission($other->id(), 'row kept');

    $context = ['sandbox' => [], 'results' => [], 'finished' => 0];
    DeleteFilteredBatch::process($webform->id(), '', '', $context);

    $this->assertSame(1, $context['finished']);
    $this->assertSame(5, $context['results']['deleted']);
    $this->assertSame(0, FilteredSubmissionQuery::forFilter($this->container, $webform, '', '')->total());
    $this->assertSame(1, FilteredSubmissionQuery::forFilter($this->container, $other, '', '')->total());
  }

  /**
   * Creates one completed submission with a message value.
   */
  private function createSubmission(string $webform_id, string $message): void {
    $submission = WebformSubmission::create([
      'webform_id' => $webform_id,
      'data' => ['message' => $message],
    ]);
    $submission->in_draft = FALSE;
    $submission->setCompletedTime(time());
    $submission->save();
  }

}
