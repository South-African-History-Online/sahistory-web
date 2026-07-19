<?php

namespace Drupal\saho_tools\Schema;

/**
 * Classifies provenance source URLs as provider pages or persistent IDs.
 *
 * A persistent identifier (ARK, DOI, Handle, PURL) is recognised by its HOST
 * only: provider pages such as Calisphere legitimately embed "ark:/" in their
 * path, and those must stay classified as ordinary source links. The same
 * host list drives both the archive Record details panel (saho.theme) and the
 * schema.org identifier mapping, so it lives here once.
 *
 * Pure static and dependency-free so it is unit-testable and callable from
 * theme code.
 */
final class ProvenanceIds {

  /**
   * Hosts that serve persistent identifiers.
   */
  public const PERSISTENT_HOSTS = [
    'ark.cdlib.org',
    'n2t.net',
    'doi.org',
    'dx.doi.org',
    'hdl.handle.net',
    'purl.org',
  ];

  /**
   * Maps persistent hosts to their identifier scheme name.
   */
  private const SCHEME_BY_HOST = [
    'ark.cdlib.org' => 'ark',
    'n2t.net' => 'ark',
    'doi.org' => 'doi',
    'dx.doi.org' => 'doi',
    'hdl.handle.net' => 'handle',
    'purl.org' => 'purl',
  ];

  /**
   * Decides whether a source URL is a persistent identifier.
   *
   * @param string $uri
   *   The link URI.
   * @param string $title
   *   Optional link title; editors labelling a link "Persistent link" flag it
   *   explicitly regardless of host.
   *
   * @return bool
   *   TRUE for ARK/DOI/Handle/PURL links (or persistent-titled links).
   */
  public static function isPersistent(string $uri, string $title = ''): bool {
    if ($title !== '' && stripos($title, 'persistent') !== FALSE) {
      return TRUE;
    }
    return self::propertyId($uri) !== NULL;
  }

  /**
   * Returns the identifier scheme for a persistent URL, or NULL.
   *
   * @param string $uri
   *   The link URI.
   *
   * @return string|null
   *   One of 'ark', 'doi', 'handle', 'purl' - or NULL for ordinary URLs.
   */
  public static function propertyId(string $uri): ?string {
    $host = parse_url($uri, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
      return NULL;
    }
    $host = strtolower($host);
    if (str_starts_with($host, 'www.')) {
      $host = substr($host, 4);
    }
    return self::SCHEME_BY_HOST[$host] ?? NULL;
  }

}
