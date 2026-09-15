<?php

declare(strict_types=1);

namespace Drupal\saho_linkfix\Service;

use Drupal\Core\Database\Connection;

/**
 * Finds hot-linked images in body HTML and decides what to do with each.
 *
 * Three classes of <img src="http..."> (issue #456):
 *  - self_present: the host is one of SAHO's own past or present hostnames
 *    (sahistory.org.za, www., v1., the old dev host sahoseventhree.dd) and the
 *    file exists under the docroot. Safe to rewrite to the site-relative path,
 *    which kills mixed-content and host-rot risk with zero visual change.
 *  - self_missing: same hosts, but the file is not on disk. Reported for the
 *    image-restoration backlog; never rewritten (a relative 404 is no better).
 *  - external: a third-party host. Inventory only - migrating those means
 *    downloading and rights-checking, an editorial job.
 *
 * Nothing here writes; BodyLinkRewriter performs the replacements.
 */
final class HotlinkResolver {

  /**
   * Hostnames that are, or were, this site: apex, www, v1, staging, old dev.
   */
  private const SELF_HOST_PATTERN = '#^https?://(www\.)?((v1|staging)\.)?sahistory\.org\.za(?::\d+)?$'
    . '|^https?://(www\.)?sahoseventhree\.dd(?::\d+)?$#i';

  public function __construct(
    protected readonly Connection $database,
    protected readonly string $appRoot,
  ) {}

  /**
   * Classifies an absolute image URL.
   *
   * @return string
   *   'self' for SAHO's own hostnames, 'external' otherwise, 'other' when
   *   the value is not an absolute http(s) URL at all.
   */
  public static function classify(string $src): string {
    $parts = parse_url(trim($src));
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
      return 'other';
    }
    if (!in_array(strtolower($parts['scheme']), ['http', 'https'], TRUE)) {
      return 'other';
    }
    $origin = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    return preg_match(self::SELF_HOST_PATTERN, $origin) ? 'self' : 'external';
  }

  /**
   * The site-relative path of a self-hosted URL (host stripped, query kept).
   *
   * Returns NULL for anything that is not a self-hosted absolute URL.
   */
  public static function localPath(string $src): ?string {
    if (self::classify($src) !== 'self') {
      return NULL;
    }
    $parts = parse_url(trim($src));
    $path = $parts['path'] ?? '/';
    if ($path === '' || $path[0] !== '/') {
      $path = '/' . $path;
    }
    return $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
  }

  /**
   * Whether a site-relative path points at a file present under the docroot.
   */
  public function fileExists(string $local_path): bool {
    $path = rawurldecode(strtok($local_path, '?') ?: '');
    if ($path === '' || str_contains($path, '..')) {
      return FALSE;
    }
    return is_file($this->appRoot . $path);
  }

  /**
   * Scans published node bodies for hot-linked images.
   *
   * @return array[]
   *   One row per distinct (nid, src): nid, bundle, src, kind
   *   (self_present|self_missing|external), host, to (the rewrite target for
   *   self_present, else NULL).
   */
  public function scan(): array {
    $rows = $this->database->query(
      'SELECT b.entity_id nid, n.type bundle, b.body_value html FROM {node__body} b INNER JOIN {node_field_data} n ON n.nid = b.entity_id AND n.status = 1 WHERE b.body_value LIKE :needle',
      [':needle' => '%<img%src="http%']
    );
    $candidates = [];
    foreach ($rows as $row) {
      foreach (self::extractImageSources((string) $row->html) as $src) {
        $kind = self::classify($src);
        if ($kind === 'other') {
          continue;
        }
        $host = parse_url($src, PHP_URL_HOST) ?: '';
        $to = NULL;
        if ($kind === 'self') {
          $local = self::localPath($src);
          $kind = ($local !== NULL && $this->fileExists($local)) ? 'self_present' : 'self_missing';
          $to = $kind === 'self_present' ? $local : NULL;
        }
        $candidates[$row->nid . '|' . $src] = [
          'nid' => (int) $row->nid,
          'bundle' => (string) $row->bundle,
          'src' => $src,
          'kind' => $kind,
          'host' => $host,
          'to' => $to,
        ];
      }
    }
    return array_values($candidates);
  }

  /**
   * Extracts absolute http(s) image sources from HTML (double-quoted src).
   *
   * @return string[]
   *   Distinct src values in document order.
   */
  public static function extractImageSources(string $html): array {
    if (!preg_match_all('#<img\b[^>]*\bsrc="(https?://[^"]+)"#i', $html, $m)) {
      return [];
    }
    return array_values(array_unique($m[1]));
  }

}
