<?php

declare(strict_types=1);

namespace Drupal\saho_linkfix\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\SynchronizableInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\FileInterface;

/**
 * Points content at the genuine copy of broken archive PDFs.
 *
 * A managed PDF is "broken" when its file is missing, empty, or not a PDF at
 * all (the May-2019 HTML-as-.pdf cohort). Most have a genuine twin with the
 * same name in another archive directory, usually already a file entity of
 * its own. The repair never copies bytes (prod disk is tight); it:
 *  - swap: moves each file-field reference from the broken file entity to the
 *    twin's file entity. File usage follows automatically on entity save.
 *  - repoint: when the twin has no file entity, changes the broken entity's
 *    uri to the twin's path (no duplicate uri is created).
 *
 * Every write is recorded in a rollback list so a run can be reverted.
 */
final class PdfTwinRepair {

  /**
   * Field types whose items reference file entities by target_id.
   */
  private const FILE_FIELD_TYPES = ['file'];

  public function __construct(
    protected readonly Connection $database,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly EntityFieldManagerInterface $entityFieldManager,
    protected readonly ArchivePdfLocator $locator,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Classifies every managed public PDF and plans a repair for broken ones.
   *
   * @param bool $include_unused
   *   Also plan repairs for broken files nothing references.
   *
   * @return array{counts: array<string, int>, rows: array<int, array<string, mixed>>}
   *   counts: status and action tallies. rows: one per broken file with fid,
   *   uri, status, uses (references from current file-field values; not
   *   file_usage, which keeps stale rows for old revisions), twin
   *   (public-relative or NULL), twin_fid, action
   *   (swap|repoint|missing|unused).
   */
  public function scan(bool $include_unused = FALSE): array {
    $usage = $this->currentReferences();
    $files = $this->database->query(
      "SELECT fid, uri FROM {file_managed} WHERE uri LIKE 'public://%' AND LOWER(uri) LIKE '%.pdf' ORDER BY fid"
    );

    $counts = [];
    $rows = [];
    foreach ($files as $file) {
      $relative = substr((string) $file->uri, strlen('public://'));
      $status = $this->locator->diskStatus($relative);
      $counts['status:' . $status] = ($counts['status:' . $status] ?? 0) + 1;
      if ($status === 'ok') {
        continue;
      }
      $uses = (int) ($usage[$file->fid] ?? 0);
      $twin = $this->locator->locateIndexed(basename($relative), $relative);
      $twin_fid = $twin !== NULL ? $this->fidForUri('public://' . $twin) : NULL;

      $action = match (TRUE) {
        $twin === NULL => 'missing',
        $uses === 0 && !$include_unused => 'unused',
        $twin_fid !== NULL => 'swap',
        default => 'repoint',
      };
      $counts['action:' . $action] = ($counts['action:' . $action] ?? 0) + 1;
      $rows[] = [
        'fid' => (int) $file->fid,
        'uri' => (string) $file->uri,
        'status' => $status,
        'uses' => $uses,
        'twin' => $twin,
        'twin_fid' => $twin_fid,
        'action' => $action,
      ];
    }
    ksort($counts);
    return ['counts' => $counts, 'rows' => $rows];
  }

  /**
   * Applies planned swap/repoint rows.
   *
   * @param array $rows
   *   Rows from scan(); rows with other actions are ignored.
   * @param bool $dry_run
   *   Count what would change without saving anything.
   *
   * @return array{stats: array<string, int>, rollback: array<int, array<string, mixed>>}
   *   Stats tallies and the rollback operations for the changes made.
   */
  public function apply(array $rows, bool $dry_run = TRUE): array {
    $stats = [
      'files' => 0,
      'swapped_items' => 0,
      'removed_duplicates' => 0,
      'repointed' => 0,
      'made_permanent' => 0,
      'entities_saved' => 0,
      'unhandled_usage' => 0,
      'errors' => 0,
    ];
    $rollback = [];
    $file_storage = $this->entityTypeManager->getStorage('file');

    foreach ($rows as $row) {
      if (!in_array($row['action'], ['swap', 'repoint'], TRUE)) {
        continue;
      }
      $stats['files']++;
      try {
        if ($row['action'] === 'repoint') {
          $file = $file_storage->load($row['fid']);
          if (!$file instanceof FileInterface) {
            continue;
          }
          $to_uri = 'public://' . $row['twin'];
          $rollback[] = [
            'op' => 'repoint',
            'fid' => (int) $row['fid'],
            'from_uri' => $file->getFileUri(),
            'to_uri' => $to_uri,
            'from_size' => (int) $file->getSize(),
          ];
          if (!$dry_run) {
            $file->setFileUri($to_uri);
            $file->setSize((int) filesize($to_uri));
            $file->save();
          }
          $stats['repointed']++;
          continue;
        }

        // Swap. Make sure the twin cannot be garbage collected with its file.
        $twin = $file_storage->load($row['twin_fid']);
        if (!$twin instanceof FileInterface) {
          continue;
        }
        if (!$twin->isPermanent()) {
          $rollback[] = ['op' => 'permanent', 'fid' => (int) $twin->id()];
          if (!$dry_run) {
            $twin->setPermanent();
            $twin->save();
          }
          $stats['made_permanent']++;
        }

        $usages = $this->database->select('file_usage', 'u')
          ->fields('u', ['module', 'type', 'id'])
          ->condition('u.fid', $row['fid'])
          ->execute()
          ->fetchAll();
        foreach ($usages as $usage) {
          if ($usage->module !== 'file') {
            // e.g. editor module: the reference lives in body HTML, not a field.
            $stats['unhandled_usage']++;
            continue;
          }
          $storage = $this->entityTypeManager->hasDefinition($usage->type) ? $this->entityTypeManager->getStorage($usage->type) : NULL;
          $entity = $storage?->load($usage->id);
          if (!$entity instanceof ContentEntityInterface) {
            $stats['unhandled_usage']++;
            continue;
          }
          $ops = $this->swapInEntity($entity, (int) $row['fid'], (int) $row['twin_fid'], $stats);
          if (!$ops) {
            continue;
          }
          array_push($rollback, ...$ops);
          if (!$dry_run) {
            $this->saveQuietly($entity);
          }
          $stats['entities_saved']++;
          $storage->resetCache([$usage->id]);
        }
      }
      catch (\Throwable $e) {
        $stats['errors']++;
        $this->loggerFactory->get('saho_linkfix')->error('PDF twin repair failed for fid @fid: @msg', [
          '@fid' => $row['fid'],
          '@msg' => $e->getMessage(),
        ]);
      }
    }
    return ['stats' => $stats, 'rollback' => $rollback];
  }

  /**
   * Reverts operations recorded by apply(), newest first.
   *
   * @return array<string, int>
   *   Stats tallies.
   */
  public function revert(array $rollback, bool $dry_run = TRUE): array {
    $stats = ['reverted' => 0, 'skipped' => 0, 'errors' => 0];
    $file_storage = $this->entityTypeManager->getStorage('file');
    foreach (array_reverse($rollback) as $op) {
      try {
        switch ($op['op']) {
          case 'repoint':
            $file = $file_storage->load($op['fid']);
            if (!$file instanceof FileInterface || $file->getFileUri() !== $op['to_uri']) {
              $stats['skipped']++;
              break;
            }
            if (!$dry_run) {
              $file->setFileUri($op['from_uri']);
              $file->setSize($op['from_size']);
              $file->save();
            }
            $stats['reverted']++;
            break;

          case 'permanent':
            $file = $file_storage->load($op['fid']);
            if (!$file instanceof FileInterface) {
              $stats['skipped']++;
              break;
            }
            if (!$dry_run) {
              $file->setTemporary();
              $file->save();
            }
            $stats['reverted']++;
            break;

          case 'swap':
          case 'remove':
            $storage = $this->entityTypeManager->getStorage($op['entity_type']);
            $entity = $storage->load($op['entity_id']);
            if (!$entity instanceof ContentEntityInterface || !$entity->hasTranslation($op['langcode'])) {
              $stats['skipped']++;
              break;
            }
            $translation = $entity->getTranslation($op['langcode']);
            $items = $translation->get($op['field']);
            if ($op['op'] === 'swap') {
              $item = $items->get($op['delta']);
              if ($item === NULL || (int) $item->get('target_id')->getValue() !== (int) $op['to_fid']) {
                $stats['skipped']++;
                break;
              }
              $item->set('target_id', $op['from_fid']);
            }
            else {
              $items->appendItem($op['item']);
            }
            if (!$dry_run) {
              $this->saveQuietly($entity);
            }
            $storage->resetCache([$op['entity_id']]);
            $stats['reverted']++;
            break;

          default:
            $stats['skipped']++;
        }
      }
      catch (\Throwable $e) {
        $stats['errors']++;
        $this->loggerFactory->get('saho_linkfix')->error('PDF twin rollback failed: @msg', ['@msg' => $e->getMessage()]);
      }
    }
    return $stats;
  }

  /**
   * Swaps one file id for another across all file fields and translations.
   *
   * If the entity already references the twin in the same field, the broken
   * item is removed instead of duplicating the twin.
   *
   * @return array<int, array<string, mixed>>
   *   Rollback operations; empty when nothing changed.
   */
  protected function swapInEntity(ContentEntityInterface $entity, int $from, int $to, array &$stats): array {
    $ops = [];
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $translation = $entity->getTranslation($langcode);
      foreach ($translation->getFieldDefinitions() as $field_name => $definition) {
        if (!in_array($definition->getType(), self::FILE_FIELD_TYPES, TRUE)) {
          continue;
        }
        // Shared (untranslatable) fields are handled on the default language.
        if (!$definition->isTranslatable() && !$translation->isDefaultTranslation()) {
          continue;
        }
        $items = $translation->get($field_name);
        $fids = array_map('intval', array_column($items->getValue(), 'target_id'));
        if (!in_array($from, $fids, TRUE)) {
          continue;
        }
        $has_twin = in_array($to, $fids, TRUE);
        // Walk backwards so removals do not shift pending deltas.
        for ($delta = count($fids) - 1; $delta >= 0; $delta--) {
          if ($fids[$delta] !== $from) {
            continue;
          }
          $base = [
            'entity_type' => $entity->getEntityTypeId(),
            'entity_id' => $entity->id(),
            'langcode' => $langcode,
            'field' => $field_name,
          ];
          if ($has_twin) {
            $ops[] = $base + ['op' => 'remove', 'item' => $items->get($delta)->getValue()];
            $items->removeItem($delta);
            $stats['removed_duplicates']++;
          }
          else {
            $ops[] = $base + ['op' => 'swap', 'delta' => $delta, 'from_fid' => $from, 'to_fid' => $to];
            $items->get($delta)->set('target_id', $to);
            $stats['swapped_items']++;
          }
        }
      }
    }
    return $ops;
  }

  /**
   * Saves an entity in place: no new revision, no changed-time bump.
   */
  protected function saveQuietly(ContentEntityInterface $entity): void {
    if ($entity instanceof RevisionableInterface && $entity->getEntityType()->isRevisionable()) {
      $entity->setNewRevision(FALSE);
    }
    if ($entity instanceof SynchronizableInterface) {
      $entity->setSyncing(TRUE);
    }
    $entity->save();
  }

  /**
   * Counts references to each file id from current (default revision) values.
   *
   * @return array<int, int>
   *   File id => number of field items referencing it.
   */
  protected function currentReferences(): array {
    $counts = [];
    foreach ($this->entityFieldManager->getFieldMapByFieldType(self::FILE_FIELD_TYPES[0]) as $entity_type_id => $fields) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      if (!$storage instanceof SqlEntityStorageInterface) {
        continue;
      }
      $mapping = $storage->getTableMapping();
      $definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      foreach (array_keys($fields) as $field_name) {
        if (!isset($definitions[$field_name])) {
          continue;
        }
        $table = $mapping->getFieldTableName($field_name);
        $column = $mapping->getFieldColumnName($definitions[$field_name], 'target_id');
        $result = $this->database->query("SELECT $column AS fid, COUNT(*) AS n FROM {" . $table . "} GROUP BY $column");
        foreach ($result as $row) {
          $counts[(int) $row->fid] = ($counts[(int) $row->fid] ?? 0) + (int) $row->n;
        }
      }
    }
    return $counts;
  }

  /**
   * The file entity id for an exact uri, or NULL.
   */
  protected function fidForUri(string $uri): ?int {
    $fid = $this->database->select('file_managed', 'f')
      ->fields('f', ['fid'])
      ->condition('f.uri', $uri)
      ->orderBy('f.fid')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    return $fid === FALSE ? NULL : (int) $fid;
  }

}
