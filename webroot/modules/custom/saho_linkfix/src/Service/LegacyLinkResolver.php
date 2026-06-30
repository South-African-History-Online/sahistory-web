<?php

declare(strict_types=1);

namespace Drupal\saho_linkfix\Service;

use Drupal\Core\Database\Connection;

/**
 * Resolves a legacy .htm(l) link to a current internal target.
 *
 * The map is built from two authoritative sources left behind by the original
 * Drupal 7 migration:
 *  1. field_old_filename - each node's own original legacy URL. This is the
 *     highest-confidence key: the node literally was that page.
 *  2. the redirect table - curated source path -> target uri pairs.
 *
 * Resolution is layered, highest confidence first:
 *  - exact: the normalised legacy path matches a map key verbatim.
 *  - basename: the file name (e.g. dadoo,y.htm) is unique across the map, so a
 *    relative ../../bios/dadoo,y.htm link can be resolved by file name alone.
 *    Colliding basenames are never auto-resolved.
 *
 * Nothing here writes; it only classifies and maps.
 */
final class LegacyLinkResolver {

  /**
   * Cached map: ['exact' => [key => uri], 'basename' => [base => uri|false]].
   *
   * A basename mapped to FALSE marks a collision (several targets) and is never
   * used for auto-resolution.
   *
   * @var array|null
   */
  protected ?array $map = NULL;

  public function __construct(
    protected readonly Connection $database,
  ) {}

  /**
   * Normalise a legacy href or URL to a comparable lookup key.
   *
   * Strips scheme + host (handles www., v1., bare), leading ../ and ./ steps,
   * a leading slash, URL-encoding and case, plus trailing dots/space.
   */
  public static function normalizeKey(string $url): string {
    $u = trim($url);
    // Strip scheme + host.
    $u = (string) preg_replace('#^https?://[^/]+#i', '', $u);
    // Strip any leading ./ or ../ relative steps.
    $u = (string) preg_replace('#^[./]+#', '', $u);
    $u = ltrim($u, '/');
    $u = strtolower(rawurldecode($u));
    return rtrim($u, ". \t");
  }

  /**
   * Classify a raw href found in body HTML.
   *
   * @return string
   *   One of: 'absolute' (legacy link to the sahistory host, redirect-fixable),
   *   'relative' (../ or bare legacy file, must be rewritten in body),
   *   'external' (some other host, leave alone),
   *   'other' (anchors, mailto, already-internal non-legacy paths).
   */
  public static function classify(string $href): string {
    $h = trim($href);
    if ($h === '' || $h[0] === '#' || stripos($h, 'mailto:') === 0 || stripos($h, 'tel:') === 0) {
      return 'other';
    }
    $is_legacy = (bool) preg_match('~\.html?($|[?\#])~i', $h);
    if (preg_match('#^https?://#i', $h)) {
      if (preg_match('#^https?://([^/]*\.)?(v1\.)?sahistory\.org\.za#i', $h)) {
        return $is_legacy ? 'absolute' : 'other';
      }
      return 'external';
    }
    // Relative or root-relative path.
    if ($is_legacy) {
      return 'relative';
    }
    return 'other';
  }

  /**
   * Resolve a legacy href to an internal uri.
   *
   * @return array|null
   *   ['uri' => 'internal:/node/NN', 'tier' => 'exact'|'basename',
   *    'source_path' => normalised legacy path] or NULL if unmapped.
   */
  public function resolve(string $href): ?array {
    $map = $this->getMap();
    $key = self::normalizeKey($href);
    if ($key === '') {
      return NULL;
    }
    if (isset($map['exact'][$key])) {
      return ['uri' => $map['exact'][$key], 'tier' => 'exact', 'source_path' => $key];
    }
    $base = basename($key);
    if ($base !== '' && !empty($map['basename'][$base])) {
      return ['uri' => $map['basename'][$base], 'tier' => 'basename', 'source_path' => $key];
    }
    return NULL;
  }

  /**
   * Build (once) and return the lookup map.
   */
  public function getMap(): array {
    if ($this->map !== NULL) {
      return $this->map;
    }
    $exact = [];

    // 1. Authoritative: field_old_filename -> node.
    if ($this->database->schema()->tableExists('node__field_old_filename')) {
      $rows = $this->database->query('SELECT entity_id, field_old_filename_value v FROM {node__field_old_filename}');
      foreach ($rows as $r) {
        $key = self::normalizeKey((string) $r->v);
        if ($key !== '') {
          $exact[$key] = 'internal:/node/' . (int) $r->entity_id;
        }
      }
    }

    // 2. Existing redirects (do not override authoritative old_filename keys).
    if ($this->database->schema()->tableExists('redirect')) {
      $rows = $this->database->query('SELECT redirect_source__path p, redirect_redirect__uri u FROM {redirect}');
      foreach ($rows as $r) {
        $key = self::normalizeKey((string) $r->p);
        if ($key !== '' && !isset($exact[$key]) && !empty($r->u)) {
          $exact[$key] = (string) $r->u;
        }
      }
    }

    // 3. Collision-safe basename index over the exact map.
    $basename = [];
    foreach ($exact as $key => $uri) {
      $base = basename($key);
      if ($base === '') {
        continue;
      }
      if (array_key_exists($base, $basename) && $basename[$base] !== $uri) {
        // Collision: several distinct targets share this file name.
        $basename[$base] = FALSE;
        continue;
      }
      $basename[$base] = $uri;
    }

    return $this->map = ['exact' => $exact, 'basename' => $basename];
  }

}
