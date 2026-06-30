<?php

declare(strict_types=1);

namespace Drupal\saho_linkfix\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Creates legacy-path redirects additively and reversibly.
 *
 * For each (source_path -> uri) pair this creates a 301 redirect, but only if
 * no redirect already claims that source. It never edits or deletes an existing
 * redirect, so it cannot clobber curated rules. Every created redirect id is
 * returned so a run can be reverted precisely.
 *
 * This is the non-destructive half of the link fix: most dead body links are
 * absolute URLs to the sahistory host, so a redirect on the legacy path repairs
 * them in place - and repairs external/search-engine inbound links too -
 * without touching a single body field.
 */
final class LegacyRedirectWriter {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Apply a set of redirect specs.
   *
   * @param array $specs
   *   List of ['source_path' => string, 'uri' => 'internal:/node/NN'].
   * @param array $options
   *   Keys: dry_run (bool, default TRUE), status_code (int, default 301).
   *
   * @return array
   *   ['created' => [rid,...], 'stats' => [...], 'rejected' => [...]].
   */
  public function apply(array $specs, array $options = []): array {
    $dry_run = $options['dry_run'] ?? TRUE;
    $status = (int) ($options['status_code'] ?? 301);
    $storage = $this->entityTypeManager->getStorage('redirect');

    $created = [];
    $rejected = [];
    $stats = [
      'considered' => 0,
      'created' => 0,
      'skipped_existing' => 0,
      'skipped_duplicate' => 0,
      'rejected' => 0,
    ];
    // Track sources handled this run so a candidate list with repeats does not
    // create duplicate rules.
    $seen = [];

    foreach ($specs as $spec) {
      $stats['considered']++;
      $source = ltrim((string) ($spec['source_path'] ?? ''), '/');
      $uri = (string) ($spec['uri'] ?? '');
      if ($source === '' || $uri === '') {
        $stats['rejected']++;
        $rejected[] = $spec + ['reason' => 'missing source or uri'];
        continue;
      }
      if (isset($seen[$source])) {
        $stats['skipped_duplicate']++;
        continue;
      }
      $seen[$source] = TRUE;

      if ($this->sourceExists($source)) {
        $stats['skipped_existing']++;
        continue;
      }

      if ($dry_run) {
        $stats['created']++;
        continue;
      }

      $redirect = $storage->create([
        'redirect_source' => ['path' => $source, 'query' => []],
        'redirect_redirect' => ['uri' => $uri],
        'status_code' => $status,
        'language' => 'und',
      ]);
      $redirect->save();
      $created[] = (int) $redirect->id();
      $stats['created']++;
    }

    if (!$dry_run && $created) {
      $this->loggerFactory->get('saho_linkfix')
        ->info('Created @n legacy redirects.', ['@n' => count($created)]);
    }

    return ['created' => $created, 'stats' => $stats, 'rejected' => $rejected];
  }

  /**
   * Remove redirects created by a previous run, if still present.
   */
  public function revert(array $rids, array $options = []): array {
    $dry_run = $options['dry_run'] ?? TRUE;
    $storage = $this->entityTypeManager->getStorage('redirect');
    $stats = ['requested' => count($rids), 'removed' => 0, 'missing' => 0];
    foreach ($rids as $rid) {
      $redirect = $storage->load((int) $rid);
      if (!$redirect) {
        $stats['missing']++;
        continue;
      }
      if (!$dry_run) {
        $redirect->delete();
      }
      $stats['removed']++;
    }
    return $stats;
  }

  /**
   * Whether any redirect already claims this source path.
   */
  protected function sourceExists(string $source): bool {
    $ids = $this->entityTypeManager->getStorage('redirect')->getQuery()
      ->accessCheck(FALSE)
      ->condition('redirect_source.path', $source)
      ->range(0, 1)
      ->execute();
    return !empty($ids);
  }

}
