<?php

declare(strict_types=1);

namespace Drupal\saho_classroom;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\node\NodeInterface;
use Drupal\pathauto\PathautoGeneratorInterface;

/**
 * Reproducibly syncs presentation decks from module JSON files into nodes.
 *
 * The slide-schema JSON files under the module's content/ directory are the
 * source of truth. syncAll() creates or updates one "presentation" node per
 * deck, keyed by a deterministic UUID derived from the deck id, so the same
 * decks reproduce identically on every environment (local, staging,
 * production) when run from a deploy hook.
 *
 * Taxonomy references are resolved by term name, never by tid, so the sync is
 * independent of environment-specific term ids. AI-drafted decks are created
 * unpublished; the editorial publish state of an existing node is preserved on
 * update so SME review decisions made in the CMS are never overwritten.
 */
final class DeckSync {

  /**
   * Fixed namespace for deriving a deterministic node UUID from a deck id.
   */
  private const UUID_NAMESPACE = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

  /**
   * Logger channel.
   */
  private readonly LoggerChannelInterface $logger;

  /**
   * Absolute path to the saho_classroom module.
   */
  private readonly string $modulePath;

  /**
   * Constructs a DeckSync service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleList
   *   The module extension list.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\pathauto\PathautoGeneratorInterface|null $pathautoGenerator
   *   The pathauto generator, when the module is installed.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    ModuleExtensionList $moduleList,
    LoggerChannelFactoryInterface $loggerFactory,
    private readonly LanguageManagerInterface $languageManager,
    private readonly ?PathautoGeneratorInterface $pathautoGenerator = NULL,
  ) {
    $this->logger = $loggerFactory->get('saho_classroom');
    $this->modulePath = $moduleList->getPath('saho_classroom');
  }

  /**
   * Syncs every *.slides.json deck under the module content/ directory.
   *
   * @return array
   *   Summary counts: created, updated, skipped, and a per-deck action list.
   */
  public function syncAll(): array {
    $summary = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'decks' => []];
    $dir = $this->modulePath . '/content';
    if (!is_dir($dir)) {
      return $summary;
    }
    foreach ($this->findDecks($dir) as $file) {
      try {
        $action = $this->syncFile($file);
        $summary[$action]++;
        $summary['decks'][] = basename($file) . ' => ' . $action;
      }
      catch (\Throwable $e) {
        $summary['skipped']++;
        $summary['decks'][] = basename($file) . ' => skipped (' . $e->getMessage() . ')';
        $this->logger->error('Deck sync failed for @file: @message', [
          '@file' => $file,
          '@message' => $e->getMessage(),
        ]);
      }
    }
    return $summary;
  }

  /**
   * Finds every *.slides.json deck file under a directory, recursively.
   *
   * @param string $dir
   *   Directory to scan.
   *
   * @return string[]
   *   Sorted list of absolute file paths.
   */
  private function findDecks(string $dir): array {
    $found = [];
    // Language-variant files (<name>.<langcode>.slides.json) are translations of
    // a base deck, handled in applyTranslations - not standalone decks.
    $langcodes = array_keys($this->languageManager->getLanguages());
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
      if (!$file->isFile() || !str_ends_with($file->getFilename(), '.slides.json')) {
        continue;
      }
      $stem = substr($file->getFilename(), 0, -strlen('.slides.json'));
      $is_variant = FALSE;
      foreach ($langcodes as $lc) {
        if (str_ends_with($stem, '.' . $lc)) {
          $is_variant = TRUE;
          break;
        }
      }
      if (!$is_variant) {
        $found[] = $file->getPathname();
      }
    }
    sort($found);
    return $found;
  }

  /**
   * Creates or updates one presentation node from a deck JSON file.
   *
   * @param string $path
   *   Absolute path to a *.slides.json file.
   *
   * @return string
   *   Either "created" or "updated".
   */
  public function syncFile(string $path): string {
    $raw = file_get_contents($path);
    $data = json_decode($raw, TRUE, 512, JSON_THROW_ON_ERROR);
    $id = $data['id'] ?? NULL;
    if (!is_string($id) || $id === '') {
      throw new \RuntimeException('Deck is missing a string "id".');
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $uuid = $this->deckUuid($id);
    $existing = $storage->loadByProperties(['uuid' => $uuid]);
    $node = $existing ? reset($existing) : NULL;
    $is_new = $node === NULL;

    if ($is_new) {
      $node = $storage->create([
        'type' => 'presentation',
        'uuid' => $uuid,
        'uid' => 1,
        'langcode' => $data['language'] ?? 'en',
      ]);
    }

    $node->set('title', (string) ($data['title'] ?? $id));
    $node->set('field_slide_schema', $raw);
    $this->setTermRef($node, 'field_caps_topic', 'caps_topic', $data['caps_topic'] ?? NULL);
    $this->setTermRef($node, 'field_classroom_grade', 'classroom_grade', $this->gradeName($data['grade'] ?? NULL));
    $this->setTermRef($node, 'field_classroom_subject', 'classroom_subject', $data['subject'] ?? NULL);
    $this->setTermRef($node, 'field_classroom_resource_type', 'classroom_resource_type', $data['resource_type'] ?? 'Presentation');

    // The committed JSON is the source of truth for publish state, so the same
    // decks reproduce identically on production. AI-drafted decks omit the flag
    // and stay unpublished; an SME approves by setting {"review":{"approved":true}}.
    $approved = ($data['review']['approved'] ?? FALSE) === TRUE;
    $node->set('status', $approved ? NodeInterface::PUBLISHED : NodeInterface::NOT_PUBLISHED);
    $node->save();

    $this->applyTranslations($node, $path);
    $this->refreshAliases($node);

    return $is_new ? 'created' : 'updated';
  }

  /**
   * Regenerates the pathauto alias for a node and each of its translations.
   *
   * Runs on every sync so pretty /classroom/... URLs reproduce on any
   * environment the moment the decks do, including translation aliases for
   * the per-language deck pages. Cheap when nothing changed: pathauto
   * returns early if the alias already matches the pattern.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The base presentation node, translations included.
   */
  private function refreshAliases(NodeInterface $node): void {
    if ($this->pathautoGenerator === NULL) {
      return;
    }
    foreach (array_keys($node->getTranslationLanguages()) as $langcode) {
      try {
        $this->pathautoGenerator->updateEntityAlias($node->getTranslation($langcode), 'bulkupdate');
      }
      catch (\Throwable $e) {
        $this->logger->warning('Alias refresh failed for @title (@langcode): @message', [
          '@title' => $node->label(),
          '@langcode' => $langcode,
          '@message' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * Attaches <deck>.<langcode>.slides.json variants as node translations.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The base (source-language) presentation node.
   * @param string $basePath
   *   Absolute path to the base deck's .slides.json file.
   */
  private function applyTranslations(NodeInterface $node, string $basePath): void {
    $dir = dirname($basePath);
    $stem = basename($basePath, '.slides.json');
    $source = $node->language()->getId();
    $changed = FALSE;
    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      if ($langcode === $source) {
        continue;
      }
      $file = $dir . '/' . $stem . '.' . $langcode . '.slides.json';
      if (!is_file($file)) {
        continue;
      }
      try {
        $raw = file_get_contents($file);
        $data = json_decode($raw, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      catch (\Throwable $e) {
        $this->logger->error('Translation @file invalid: @m', ['@file' => $file, '@m' => $e->getMessage()]);
        continue;
      }
      $translation = $node->hasTranslation($langcode)
        ? $node->getTranslation($langcode)
        : $node->addTranslation($langcode);
      $translation->set('title', (string) ($data['title'] ?? $node->getTitle()));
      $translation->set('field_slide_schema', $raw);
      $translation->set('status', $node->isPublished());
      $changed = TRUE;
    }
    if ($changed) {
      $node->save();
    }
  }

  /**
   * Sets an entity-reference field to a term matched by name, if it exists.
   */
  private function setTermRef(NodeInterface $node, string $field, string $vid, ?string $name): void {
    if (!$node->hasField($field) || $name === NULL || $name === '') {
      return;
    }
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => $vid, 'name' => $name]);
    if ($terms) {
      $node->set($field, ['target_id' => reset($terms)->id()]);
    }
    else {
      $this->logger->warning('Deck sync: no @vid term named "@name"; @field left unset.', [
        '@vid' => $vid,
        '@name' => $name,
        '@field' => $field,
      ]);
    }
  }

  /**
   * Normalises a deck grade value ("9" or "Grade 9") to a grade term name.
   */
  private function gradeName(mixed $grade): ?string {
    if ($grade === NULL || $grade === '') {
      return NULL;
    }
    $grade = (string) $grade;
    return is_numeric($grade) ? 'Grade ' . $grade : $grade;
  }

  /**
   * Derives a deterministic RFC-4122 v5 UUID from a deck id.
   */
  private function deckUuid(string $id): string {
    $ns_hex = str_replace('-', '', self::UUID_NAMESPACE);
    $ns_bin = '';
    for ($i = 0; $i < strlen($ns_hex); $i += 2) {
      $ns_bin .= chr(hexdec(substr($ns_hex, $i, 2)));
    }
    $hash = sha1($ns_bin . $id);
    return sprintf('%08s-%04s-5%03s-%04x-%12s',
      substr($hash, 0, 8),
      substr($hash, 8, 4),
      substr($hash, 13, 3),
      (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
      substr($hash, 20, 12),
    );
  }

}
