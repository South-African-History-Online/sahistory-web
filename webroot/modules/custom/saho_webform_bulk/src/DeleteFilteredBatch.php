<?php

declare(strict_types=1);

namespace Drupal\saho_webform_bulk;

use Drupal\webform\Entity\Webform;

/**
 * Batch callbacks that delete every submission matching a results filter.
 */
final class DeleteFilteredBatch {

  /**
   * Submissions deleted per batch pass.
   */
  public const CHUNK = 50;

  /**
   * Batch operation: delete the next chunk of matching submissions.
   *
   * Re-runs the filter query each pass instead of carrying 100k+ ids in the
   * batch context; because deleted rows drop out of the result set, the lowest
   * ids returned are always fresh work.
   *
   * @param string $webform_id
   *   The webform id.
   * @param string $keys
   *   The search keyword ('' for none).
   * @param string $state
   *   The state filter ('' for none).
   * @param array $context
   *   The batch context.
   */
  public static function process(string $webform_id, string $keys, string $state, array &$context): void {
    $webform = Webform::load($webform_id);
    if (!$webform) {
      $context['finished'] = 1;
      return;
    }
    $query = FilteredSubmissionQuery::forFilter(\Drupal::getContainer(), $webform, $keys, $state);

    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['total'] = $query->total();
      $context['results']['deleted'] = 0;
    }
    $total = (int) $context['sandbox']['total'];

    $ids = $query->ids(self::CHUNK);
    if (!$ids) {
      $context['finished'] = 1;
      return;
    }

    $storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $storage->delete($storage->loadMultiple($ids));
    $context['results']['deleted'] += count($ids);

    $done = (int) $context['results']['deleted'];
    $context['message'] = t('Deleted @done of @total submissions.', [
      '@done' => number_format($done),
      '@total' => number_format($total),
    ]);
    // Never loop past the count taken at the start, even if new matching
    // submissions arrive while the batch runs.
    $context['finished'] = ($total > 0 && $done < $total) ? $done / $total : 1;
  }

  /**
   * Batch finished callback.
   */
  public static function finished(bool $success, array $results, array $operations): void {
    $deleted = (int) ($results['deleted'] ?? 0);
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addStatus(\Drupal::translation()->formatPlural($deleted, 'Deleted @count submission.', 'Deleted @count submissions.'));
    }
    else {
      $messenger->addError(t('The bulk delete stopped early after @count submissions; run it again to continue.', ['@count' => $deleted]));
    }
  }

}
