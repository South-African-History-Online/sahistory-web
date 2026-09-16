<?php

declare(strict_types=1);

namespace Drupal\saho_linkfix\Service;

use Drupal\Core\Database\Connection;

/**
 * Weekly link-rot report from data the site already collects (#397).
 *
 * Two sources, no crawling needed:
 *  - redirect_404: every 404 the site served, with hit counts. Scanner noise
 *    (wp-admin, .php probes, WebDAV, dotfiles) is filtered so the report
 *    shows paths real visitors and search engines asked for.
 *  - linkchecker: outbound and internal links found in content, re-checked
 *    on cron; dead ones (404/410/5xx) are listed with the pages that carry
 *    them so an editor can fix the source.
 *
 * Nothing here writes; the drush command prints Markdown and the weekly
 * GitHub workflow posts it to the tracking issue.
 */
final class LinkRotReport {

  /**
   * Regexes for 404 paths that are attack/probe noise, never link rot.
   */
  public const NOISE = [
    '#^/wp-#i',
    '#^/wordpress#i',
    '#\.(php|jsf|jsp|asp|aspx|cgi|env|sql|bak|zip|rar|tar|gz|7z|ini|log|yml|yaml|json|xml|txt)(\?|$)#i',
    '#^/\.#',
    '#/\.(git|svn|env|aws|ssh|htaccess)#i',
    '#(^|/)(webdav|sling|graphql|solr|jenkins|phpmyadmin|pma|mysql|admin(er)?|xmlrpc|cgi-bin|vendor|node_modules)(/|$)#i',
    '#(^|/)(autodiscover|owa|ews|remote|manager|actuator|telescope|debug|_profiler|console)(/|$)#i',
    '#(^|/)(wlwmanifest|fckeditor|ckfinder|elfinder|filemanager)#i',
  ];

  public function __construct(
    protected readonly Connection $database,
  ) {}

  /**
   * Whether a 404 path is probe noise according to the patterns.
   */
  public static function isNoise(string $path, array $patterns = self::NOISE): bool {
    foreach ($patterns as $pattern) {
      if (preg_match($pattern, $path)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Whether a 404 path looks like a URL built from a JS null/undefined.
   *
   * These are bugs in the site's own scripts, not content rot; they are
   * called out separately so nobody tries to "redirect" /people/null.
   */
  public static function isNullBug(string $path): bool {
    return (bool) preg_match('#(^|/)(null|undefined|NaN)(/|$|\?)#', $path);
  }

  /**
   * Top unresolved 404 paths that are not scanner noise.
   *
   * @return array[]
   *   Rows: path, count, last (Y-m-d), null_bug (bool).
   */
  public function notFound(int $limit = 30, int $min_count = 5): array {
    if (!$this->database->schema()->tableExists('redirect_404')) {
      return [];
    }
    // Over-fetch, then filter noise in PHP so the regexes stay in one place.
    $rows = $this->database->select('redirect_404', 'r')
      ->fields('r', ['path', 'count', 'timestamp'])
      ->condition('r.resolved', 0)
      ->condition('r.count', $min_count, '>=')
      ->orderBy('r.count', 'DESC')
      ->range(0, $limit * 8)
      ->execute();
    $out = [];
    foreach ($rows as $row) {
      $path = (string) $row->path;
      if (self::isNoise($path)) {
        continue;
      }
      $out[] = [
        'path' => $path,
        'count' => (int) $row->count,
        'last' => date('Y-m-d', (int) $row->timestamp),
        'null_bug' => self::isNullBug($path),
      ];
      if (count($out) >= $limit) {
        break;
      }
    }
    return $out;
  }

  /**
   * Dead links known to linkchecker, with the pages that carry them.
   *
   * @return array[]
   *   Rows: url, code, fail_count, last_check (Y-m-d), pages (int),
   *   sample (array of "entity_type/id" strings, at most 3).
   */
  public function brokenLinks(int $limit = 30): array {
    $schema = $this->database->schema();
    if (!$schema->tableExists('linkchecker_link')) {
      return [];
    }
    $rows = $this->database->select('linkchecker_link', 'l')
      ->fields('l', ['lid', 'url', 'code', 'fail_count', 'last_check', 'parent_entity_type_id', 'parent_entity_id'])
      ->condition('l.status', 1)
      ->condition('l.code', [404, 410], 'IN')
      ->orderBy('l.fail_count', 'DESC')
      ->orderBy('l.url')
      ->execute();
    $by_url = [];
    foreach ($rows as $row) {
      $url = (string) $row->url;
      $by_url[$url] ??= [
        'url' => $url,
        'code' => (int) $row->code,
        'fail_count' => (int) $row->fail_count,
        'last_check' => $row->last_check ? date('Y-m-d', (int) $row->last_check) : '',
        'pages' => 0,
        'sample' => [],
      ];
      $by_url[$url]['pages']++;
      if ($row->parent_entity_id && count($by_url[$url]['sample']) < 3) {
        $by_url[$url]['sample'][] = (string) $row->parent_entity_type_id . '/' . (int) $row->parent_entity_id;
      }
    }
    usort($by_url, static function (array $a, array $b): int {
      return [$b['pages'], $b['fail_count']] <=> [$a['pages'], $a['fail_count']];
    });
    return array_slice(array_values($by_url), 0, $limit);
  }

  /**
   * Totals for the summary line.
   *
   * @return array
   *   Keys: unresolved_404, broken_links.
   */
  public function totals(): array {
    $totals = ['unresolved_404' => 0, 'broken_links' => 0];
    $schema = $this->database->schema();
    if ($schema->tableExists('redirect_404')) {
      $totals['unresolved_404'] = (int) $this->database->query('SELECT COUNT(*) FROM {redirect_404} WHERE resolved = 0')->fetchField();
    }
    if ($schema->tableExists('linkchecker_link')) {
      $totals['broken_links'] = (int) $this->database->query('SELECT COUNT(DISTINCT url) FROM {linkchecker_link} WHERE status = 1 AND code IN (404, 410)')->fetchField();
    }
    return $totals;
  }

  /**
   * Renders the Markdown report from prepared data (pure; unit-tested).
   */
  public static function render(array $not_found, array $broken, array $totals, string $base_url = 'https://sahistory.org.za', string $generated = ''): string {
    $lines = [];
    $lines[] = '## Link-rot report' . ($generated !== '' ? ' - ' . $generated : '');
    $lines[] = '';
    $lines[] = sprintf(
      '%s unresolved 404 paths in the log; %s distinct dead links in content (linkchecker).',
      number_format($totals['unresolved_404'] ?? 0),
      number_format($totals['broken_links'] ?? 0),
    );
    $lines[] = '';

    $bugs = array_filter($not_found, static fn(array $r): bool => $r['null_bug']);
    $rot = array_filter($not_found, static fn(array $r): bool => !$r['null_bug']);

    if ($bugs !== []) {
      $lines[] = '### Site-script bugs (URLs built from null/undefined)';
      $lines[] = '';
      $lines[] = 'Fix the script, do not redirect these.';
      $lines[] = '';
      $lines[] = '| hits | path | last seen |';
      $lines[] = '|---:|---|---|';
      foreach ($bugs as $r) {
        $lines[] = sprintf('| %s | `%s` | %s |', number_format($r['count']), $r['path'], $r['last']);
      }
      $lines[] = '';
    }

    $lines[] = '### Most requested missing pages';
    $lines[] = '';
    if ($rot === []) {
      $lines[] = 'None above the threshold.';
    }
    else {
      $lines[] = '| hits | path | last seen |';
      $lines[] = '|---:|---|---|';
      foreach ($rot as $r) {
        $lines[] = sprintf('| %s | [`%s`](%s%s) | %s |', number_format($r['count']), $r['path'], $base_url, $r['path'], $r['last']);
      }
    }
    $lines[] = '';

    $lines[] = '### Dead links in content';
    $lines[] = '';
    if ($broken === []) {
      $lines[] = 'None reported by linkchecker.';
    }
    else {
      $lines[] = '| code | pages | link | found on |';
      $lines[] = '|---:|---:|---|---|';
      foreach ($broken as $b) {
        $where = implode(', ', array_map(
          static fn(string $ref): string => sprintf('[%s](%s/%s)', $ref, $base_url, $ref),
          $b['sample']
        ));
        $lines[] = sprintf('| %d | %d | `%s` | %s |', $b['code'], $b['pages'], mb_strimwidth($b['url'], 0, 90, '...'), $where);
      }
    }
    $lines[] = '';
    $lines[] = '_Scanner noise (wp-admin, .php probes, WebDAV, dotfiles) is filtered; see `LinkRotReport::NOISE`._';
    return implode("\n", $lines) . "\n";
  }

}
