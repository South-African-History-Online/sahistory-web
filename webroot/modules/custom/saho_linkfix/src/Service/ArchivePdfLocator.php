<?php

declare(strict_types=1);

namespace Drupal\saho_linkfix\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;

/**
 * Finds the genuine PDF copy of an archive document by its file name.
 *
 * The DISA-era archive PDFs live in several sibling directories under
 * public:// (archive-files, archive-files2..5, archive_files, 2019-05), often
 * as duplicates with different capitalisation. Many copies are broken: the
 * May-2019 migration saved D7 HTML pages under .pdf names (2019-05/ mostly),
 * and records under DC/ or archivefiles/ point at directories that no longer
 * exist on disk. Old inbound links use a bare /archive/NAME.pdf path.
 *
 * Given a file name, this returns the first copy, in a fixed directory order,
 * that is a real PDF (non-empty, starts with the "%PDF-" magic bytes). It is
 * shared by the 404 fallback subscriber and the reference repair command.
 */
final class ArchivePdfLocator {

  /**
   * Directories searched for a genuine copy, most trusted first.
   *
   * 2019-05 is last: it is mostly HTML-as-.pdf, but holds a few real PDFs
   * that exist nowhere else.
   */
  public const DIRECTORIES = [
    'archive-files',
    'archive-files2',
    'archive-files3',
    'archive-files4',
    'archive-files5',
    'archive_files',
    '2019-05',
  ];

  /**
   * Absolute path of the public files directory, resolved lazily.
   */
  protected ?string $publicRoot = NULL;

  /**
   * Lowercase file name => relative paths, built on demand for bulk scans.
   *
   * @var array<string, string[]>|null
   */
  protected ?array $index = NULL;

  public function __construct(
    protected readonly Connection $database,
    protected readonly FileSystemInterface $fileSystem,
  ) {}

  /**
   * Whether a file name is a safe, bare .pdf name (no directory parts).
   */
  public static function isValidName(string $name): bool {
    return $name !== ''
      && strlen($name) <= 255
      && !str_contains($name, '/')
      && !str_contains($name, '\\')
      && !str_contains($name, "\0")
      && $name !== '.pdf'
      && (bool) preg_match('/\.pdf$/i', $name);
  }

  /**
   * Whether a public-relative path is a genuine, non-empty PDF on disk.
   */
  public function isGenuinePdf(string $relative): bool {
    // Reject traversal by path segment, not substring: DISA names such as
    // "art19870000.032.009.762..pdf" legitimately contain "..".
    if (in_array('..', explode('/', str_replace('\\', '/', $relative)), TRUE)) {
      return FALSE;
    }
    $path = $this->publicRoot() . '/' . $relative;
    if (!is_file($path) || filesize($path) === 0) {
      return FALSE;
    }
    $handle = @fopen($path, 'rb');
    if ($handle === FALSE) {
      return FALSE;
    }
    $magic = fread($handle, 5);
    fclose($handle);
    return $magic === '%PDF-';
  }

  /**
   * Classifies the on-disk state of a public-relative path.
   *
   * @return string
   *   'ok', 'missing', 'empty' or 'not_pdf' (e.g. an HTML page saved as .pdf).
   */
  public function diskStatus(string $relative): string {
    $path = $this->publicRoot() . '/' . $relative;
    if (!is_file($path)) {
      return 'missing';
    }
    if (filesize($path) === 0) {
      return 'empty';
    }
    return $this->isGenuinePdf($relative) ? 'ok' : 'not_pdf';
  }

  /**
   * Locates a genuine copy of a PDF by file name, for a single request.
   *
   * Tries the exact name in each directory first (cheap stat calls), then a
   * case-insensitive match on file_managed.filename, whose collation ignores
   * case (crmay87... vs CrMay87...).
   *
   * @param string $name
   *   A bare file name, e.g. "LaSep80.pdf".
   * @param string|null $exclude
   *   A public-relative path that must not be returned (the broken original).
   *
   * @return string|null
   *   A public-relative path such as "archive-files4/LaSep80.pdf", or NULL.
   */
  public function locate(string $name, ?string $exclude = NULL): ?string {
    if (!self::isValidName($name)) {
      return NULL;
    }
    foreach (self::DIRECTORIES as $dir) {
      $candidate = $dir . '/' . $name;
      if ($candidate !== $exclude && $this->isGenuinePdf($candidate)) {
        return $candidate;
      }
    }

    $uris = $this->database->select('file_managed', 'f')
      ->fields('f', ['uri'])
      ->condition('f.filename', $name)
      ->range(0, 20)
      ->execute()
      ->fetchCol();
    $candidates = [];
    foreach ($uris as $uri) {
      $relative = substr((string) $uri, strlen('public://'));
      if (!str_starts_with((string) $uri, 'public://') || strcasecmp(basename($relative), $name) !== 0) {
        continue;
      }
      $dir = dirname($relative);
      $rank = array_search($dir, self::DIRECTORIES, TRUE);
      if ($rank !== FALSE) {
        $candidates[$relative] = $rank;
      }
    }
    asort($candidates);
    foreach (array_keys($candidates) as $candidate) {
      if ($candidate !== $exclude && $this->isGenuinePdf($candidate)) {
        return $candidate;
      }
    }
    return NULL;
  }

  /**
   * Locates a genuine copy using a full directory index (bulk scans).
   *
   * Unlike locate() this also finds copies that have no file_managed record
   * and matches case-insensitively without touching the database.
   */
  public function locateIndexed(string $name, ?string $exclude = NULL): ?string {
    if (!self::isValidName($name)) {
      return NULL;
    }
    $this->index ??= $this->buildIndex();
    foreach ($this->index[strtolower($name)] ?? [] as $candidate) {
      if ($candidate !== $exclude && $this->isGenuinePdf($candidate)) {
        return $candidate;
      }
    }
    return NULL;
  }

  /**
   * Builds a lowercase-name index of every .pdf in the searched directories.
   *
   * @return array<string, string[]>
   *   Lowercase file name => relative paths in directory-preference order.
   */
  protected function buildIndex(): array {
    $index = [];
    foreach (self::DIRECTORIES as $dir) {
      $path = $this->publicRoot() . '/' . $dir;
      if (!is_dir($path)) {
        continue;
      }
      foreach (scandir($path) ?: [] as $entry) {
        if (preg_match('/\.pdf$/i', $entry)) {
          $index[strtolower($entry)][] = $dir . '/' . $entry;
        }
      }
    }
    return $index;
  }

  /**
   * The absolute public files directory.
   */
  protected function publicRoot(): string {
    return $this->publicRoot ??= rtrim((string) $this->fileSystem->realpath('public://'), '/');
  }

}
