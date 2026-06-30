<?php

declare(strict_types=1);

namespace Drupal\saho_linkfix\Drush\Commands;

use Drupal\Core\Database\Connection;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\saho_linkfix\Service\BodyLinkRewriter;
use Drupal\saho_linkfix\Service\LegacyLinkResolver;
use Drupal\saho_linkfix\Service\LegacyRedirectWriter;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands for the SAHO legacy link fixer.
 *
 * Pipeline: scan -> (redirects | rewrite). Nothing writes to the database
 * unless --apply is passed; every command defaults to a dry run.
 */
final class SahoLinkfixCommands extends DrushCommands {

  public function __construct(
    protected readonly LegacyLinkResolver $resolver,
    protected readonly LegacyRedirectWriter $redirectWriter,
    protected readonly BodyLinkRewriter $bodyRewriter,
    protected readonly Connection $database,
    protected readonly AliasManagerInterface $aliasManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('saho_linkfix.resolver'),
      $container->get('saho_linkfix.redirect_writer'),
      $container->get('saho_linkfix.body_rewriter'),
      $container->get('database'),
      $container->get('path_alias.manager'),
    );
  }

  /**
   * Scan all node bodies for legacy links and resolve them to targets.
   */
  #[CLI\Command(name: 'saho:linkfix-scan', aliases: ['slfs'])]
  #[CLI\Option(name: 'out', description: 'Candidates JSON output path.')]
  #[CLI\Option(name: 'gaps', description: 'Unmapped legacy links CSV output path.')]
  public function scan(
    array $options = [
      'out' => 'public://saho_linkfix_work/candidates.json',
      'gaps' => 'public://saho_linkfix_work/gaps.csv',
    ],
  ): void {
    $this->ensureDir(dirname($options['out']));
    $candidates = [];
    $gaps = [];
    $stats = ['absolute' => 0, 'relative' => 0, 'external' => 0, 'mapped' => 0, 'unmapped' => 0];

    $last = -1;
    do {
      $rows = $this->database->select('node__body', 'b')
        ->fields('b', ['entity_id', 'body_value'])
        ->condition('b.entity_id', $last, '>')
        ->orderBy('b.entity_id', 'ASC')
        ->range(0, 500)
        ->execute()
        ->fetchAll();
      foreach ($rows as $row) {
        $last = (int) $row->entity_id;
        if (stripos($row->body_value, '.htm') === FALSE) {
          continue;
        }
        if (!preg_match_all('#href=["\']([^"\']+)["\']#i', $row->body_value, $m)) {
          continue;
        }
        foreach (array_unique($m[1]) as $href) {
          $kind = LegacyLinkResolver::classify($href);
          if (!in_array($kind, ['absolute', 'relative'], TRUE)) {
            if ($kind === 'external') {
              $stats['external']++;
            }
            continue;
          }
          $stats[$kind]++;
          $resolved = $this->resolver->resolve($href);
          if ($resolved === NULL) {
            $stats['unmapped']++;
            $gaps[] = [$row->entity_id, $kind, $href];
            continue;
          }
          $stats['mapped']++;
          $candidates[] = [
            'nid' => (int) $row->entity_id,
            'href' => $href,
            'kind' => $kind,
            'source_path' => $resolved['source_path'],
            'uri' => $resolved['uri'],
            'tier' => $resolved['tier'],
          ];
        }
      }
    } while (count($rows) === 500);

    $this->writeJson($options['out'], $candidates);
    $this->writeCsv($options['gaps'], ['nid', 'kind', 'href'], $gaps);

    $this->io()->title('Legacy link scan');
    foreach ($stats as $k => $v) {
      $this->io()->writeln(sprintf('  %-12s %d', $k, $v));
    }
    $this->logger()->success(sprintf(
      '%d mapped candidates -> %s ; %d gaps -> %s',
      count($candidates),
      $options['out'],
      count($gaps),
      $options['gaps'],
    ));
  }

  /**
   * Create redirects for absolute legacy links. Dry run by default.
   */
  #[CLI\Command(name: 'saho:linkfix-redirects', aliases: ['slfr'])]
  #[CLI\Option(name: 'in', description: 'Candidates JSON path.')]
  #[CLI\Option(name: 'apply', description: 'Actually create redirects.')]
  #[CLI\Option(name: 'rollback-out', description: 'Where to write created redirect ids.')]
  public function redirects(
    array $options = [
      'in' => 'public://saho_linkfix_work/candidates.json',
      'apply' => FALSE,
      'rollback-out' => 'public://saho_linkfix_work/redirects_rollback.json',
    ],
  ): void {
    $candidates = $this->readJson($options['in']);
    // Redirects fix absolute legacy links; dedupe by legacy source path.
    $specs = [];
    foreach ($candidates as $c) {
      if (($c['kind'] ?? '') !== 'absolute') {
        continue;
      }
      $specs[$c['source_path']] = ['source_path' => $c['source_path'], 'uri' => $c['uri']];
    }
    $specs = array_values($specs);

    $result = $this->redirectWriter->apply($specs, ['dry_run' => !$options['apply']]);
    $this->io()->title($options['apply'] ? 'REDIRECTS (APPLY)' : 'REDIRECTS (DRY RUN)');
    foreach ($result['stats'] as $k => $v) {
      $this->io()->writeln(sprintf('  %-18s %d', $k, $v));
    }
    if ($options['apply'] && $result['created']) {
      $this->writeJson($options['rollback-out'], $result['created']);
      $this->logger()->success(sprintf('Rollback (rids) written to %s', $options['rollback-out']));
    }
  }

  /**
   * Rewrite relative legacy links inside body text. Dry run by default.
   */
  #[CLI\Command(name: 'saho:linkfix-rewrite', aliases: ['slfw'])]
  #[CLI\Option(name: 'in', description: 'Candidates JSON path.')]
  #[CLI\Option(name: 'apply', description: 'Actually rewrite bodies.')]
  #[CLI\Option(name: 'rollback-out', description: 'Where to write the body snapshot rollback.')]
  public function rewrite(
    array $options = [
      'in' => 'public://saho_linkfix_work/candidates.json',
      'apply' => FALSE,
      'rollback-out' => 'public://saho_linkfix_work/rewrite_rollback.json',
    ],
  ): void {
    $candidates = $this->readJson($options['in']);
    // Rewrites fix relative legacy links; group replacements per node.
    $jobs = [];
    foreach ($candidates as $c) {
      if (($c['kind'] ?? '') !== 'relative') {
        continue;
      }
      $nid = (int) $c['nid'];
      $jobs[$nid]['nid'] = $nid;
      $jobs[$nid]['field'] = 'body';
      $jobs[$nid]['replacements'][] = ['from' => $c['href'], 'to' => $this->uriToPath($c['uri'])];
    }
    $jobs = array_values($jobs);

    $result = $this->bodyRewriter->apply($jobs, ['dry_run' => !$options['apply']]);
    $this->io()->title($options['apply'] ? 'REWRITE (APPLY)' : 'REWRITE (DRY RUN)');
    foreach ($result['stats'] as $k => $v) {
      $this->io()->writeln(sprintf('  %-18s %d', $k, $v));
    }
    if ($options['apply'] && $result['applied']) {
      $this->writeJson($options['rollback-out'], $result['applied']);
      $this->logger()->success(sprintf('Body rollback written to %s', $options['rollback-out']));
    }
  }

  /**
   * Revert a redirect run (delete created redirects). Dry run by default.
   */
  #[CLI\Command(name: 'saho:linkfix-redirects-rollback', aliases: ['slfrb'])]
  #[CLI\Option(name: 'in', description: 'Created redirect ids JSON path.')]
  #[CLI\Option(name: 'apply', description: 'Actually delete.')]
  public function redirectsRollback(
    array $options = [
      'in' => 'public://saho_linkfix_work/redirects_rollback.json',
      'apply' => FALSE,
    ],
  ): void {
    $stats = $this->redirectWriter->revert($this->readJson($options['in']), ['dry_run' => !$options['apply']]);
    $this->printStats($options['apply'] ? 'REDIRECT ROLLBACK' : 'REDIRECT ROLLBACK (DRY RUN)', $stats);
  }

  /**
   * Revert a body rewrite run. Dry run by default.
   */
  #[CLI\Command(name: 'saho:linkfix-rewrite-rollback', aliases: ['slfwb'])]
  #[CLI\Option(name: 'in', description: 'Body snapshot rollback JSON path.')]
  #[CLI\Option(name: 'apply', description: 'Actually restore bodies.')]
  public function rewriteRollback(
    array $options = [
      'in' => 'public://saho_linkfix_work/rewrite_rollback.json',
      'apply' => FALSE,
    ],
  ): void {
    $stats = $this->bodyRewriter->revert($this->readJson($options['in']), ['dry_run' => !$options['apply']]);
    $this->printStats($options['apply'] ? 'BODY ROLLBACK' : 'BODY ROLLBACK (DRY RUN)', $stats);
  }

  /**
   * Convert a link uri to a site-relative path (alias when available).
   */
  protected function uriToPath(string $uri): string {
    $path = match (TRUE) {
      str_starts_with($uri, 'internal:') => substr($uri, strlen('internal:')),
      str_starts_with($uri, 'entity:node/') => '/node/' . preg_replace('/\D/', '', substr($uri, strlen('entity:node/'))),
      str_starts_with($uri, 'base:') => '/' . ltrim(substr($uri, strlen('base:')), '/'),
      default => $uri,
    };
    if (str_starts_with($path, '/')) {
      return $this->aliasManager->getAliasByPath($path);
    }
    return $path;
  }

  /**
   * Print a stats block under a title.
   */
  protected function printStats(string $title, array $stats): void {
    $this->io()->title($title);
    foreach ($stats as $k => $v) {
      $this->io()->writeln(sprintf('  %-18s %d', $k, $v));
    }
  }

  /**
   * Ensure a work directory exists and is not web-accessible.
   *
   * The artifacts (candidates.json, gaps.csv, and especially the body rollback
   * file, which holds pre-rewrite HTML) live under the public files directory,
   * so a deny-all .htaccess is written to keep them off the web.
   */
  protected function ensureDir(string $dir): void {
    if (!is_dir($dir)) {
      mkdir($dir, 0775, TRUE);
    }
    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
      file_put_contents($htaccess, implode("\n", [
        '# Deny all web access to link-fix work artifacts.',
        '<IfModule mod_authz_core.c>',
        '  Require all denied',
        '</IfModule>',
        '<IfModule !mod_authz_core.c>',
        '  Deny from all',
        '</IfModule>',
        '',
      ]));
    }
  }

  /**
   * Read a JSON array from disk.
   */
  protected function readJson(string $path): array {
    if (!is_file($path)) {
      throw new \RuntimeException("File not found: $path");
    }
    $data = json_decode((string) file_get_contents($path), TRUE, 512, JSON_THROW_ON_ERROR);
    return is_array($data) ? $data : [];
  }

  /**
   * Write pretty JSON to disk.
   */
  protected function writeJson(string $path, array $data): void {
    $this->ensureDir(dirname($path));
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
  }

  /**
   * Write a CSV file.
   */
  protected function writeCsv(string $path, array $header, array $rows): void {
    $this->ensureDir(dirname($path));
    $fh = fopen($path, 'w');
    fputcsv($fh, $header);
    foreach ($rows as $row) {
      fputcsv($fh, $row);
    }
    fclose($fh);
  }

}
