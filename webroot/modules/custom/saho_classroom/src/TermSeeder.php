<?php

declare(strict_types=1);

namespace Drupal\saho_classroom;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Idempotently ensures the classroom taxonomy terms exist from a manifest.
 *
 * The classroom vocabularies are config (reproducible), but their terms are
 * content and would not otherwise reach a fresh environment. This service reads
 * content/taxonomy-manifest.json and creates any missing term (matched by name
 * within its vocabulary), then wires parent relationships - so DeckSync's
 * resolve-by-name works identically on local, staging and production.
 *
 * Safe alongside other seeding: existing terms are left untouched, so it is a
 * no-op wherever the terms already exist.
 */
final class TermSeeder {

  /**
   * Logger channel.
   */
  private readonly LoggerChannelInterface $logger;

  /**
   * Absolute path to the saho_classroom module.
   */
  private readonly string $modulePath;

  /**
   * Constructs a TermSeeder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleList
   *   The module extension list.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    ModuleExtensionList $moduleList,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('saho_classroom');
    $this->modulePath = $moduleList->getPath('saho_classroom');
  }

  /**
   * Ensures every term in the manifest exists, with parents wired.
   *
   * @return array
   *   Summary counts: created, existing.
   */
  public function seed(): array {
    $summary = ['created' => 0, 'existing' => 0];
    $path = $this->modulePath . '/content/taxonomy-manifest.json';
    if (!is_file($path)) {
      return $summary;
    }
    $manifest = json_decode(file_get_contents($path), TRUE, 512, JSON_THROW_ON_ERROR);
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Pass 1: ensure every term exists (parents may not exist yet).
    foreach ($manifest as $vid => $terms) {
      foreach ($terms as $row) {
        if ($this->firstTerm($storage, $vid, $row['name'])) {
          $summary['existing']++;
          continue;
        }
        $storage->create([
          'vid' => $vid,
          'name' => $row['name'],
          'weight' => $row['weight'] ?? 0,
        ])->save();
        $summary['created']++;
      }
    }

    // Pass 2: wire parents now that all terms exist.
    foreach ($manifest as $vid => $terms) {
      foreach ($terms as $row) {
        if (empty($row['parent'])) {
          continue;
        }
        $term = $this->firstTerm($storage, $vid, $row['name']);
        $parent = $this->firstTerm($storage, $vid, $row['parent']);
        if ($term && $parent && (int) $term->get('parent')->target_id !== (int) $parent->id()) {
          $term->set('parent', $parent->id());
          $term->save();
        }
      }
    }

    return $summary;
  }

  /**
   * Loads the first term matching a name within a vocabulary.
   */
  private function firstTerm(EntityStorageInterface $storage, string $vid, string $name): ?TermInterface {
    $terms = $storage->loadByProperties(['vid' => $vid, 'name' => $name]);
    return $terms ? reset($terms) : NULL;
  }

}
