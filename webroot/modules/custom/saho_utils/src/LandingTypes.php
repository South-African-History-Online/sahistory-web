<?php

declare(strict_types=1);

namespace Drupal\saho_utils;

/**
 * The public record types that may lead an /index/{type} landing.
 *
 * Single source for the landing route allow-list, the landing H1 label
 * (theme: saho_preprocess_views_view__saho_landing) and the per-type SEO
 * title/description. Bundle labels are unreliable here (the event type is
 * labelled "This day in history"), hence the explicit archive vocabulary.
 */
final class LandingTypes {

  /**
   * Bundle => plural landing label. Order is presentation order.
   */
  public const LABELS = [
    'biography' => 'Biographies',
    'article' => 'Articles',
    'event' => 'Events',
    'place' => 'Places',
    'archive' => 'Archive',
    'topic' => 'Topics',
    'upcomingevent' => 'Events',
    'book' => 'Archive',
  ];

  /**
   * Bundle => one-sentence meta description for the landing.
   */
  public const DESCRIPTIONS = [
    'biography' => 'Browse the biographies in the South African History Online archive: lives of activists, leaders, artists and ordinary people who shaped South African history.',
    'article' => 'Browse the articles in the South African History Online archive: researched features on the people, movements and events of South African history.',
    'event' => 'Browse the dated events in the South African History Online archive, from precolonial history to the present day.',
    'place' => 'Browse the places in the South African History Online archive: towns, sites, prisons and landmarks with their histories.',
    'archive' => 'Browse the archive of South African History Online: documents, photographs, books and sources with their provenance.',
    'topic' => 'Browse the topics in the South African History Online archive: themed collections that connect people, places and events.',
    'upcomingevent' => 'Browse the dated events in the South African History Online archive, from precolonial history to the present day.',
    'book' => 'Browse the books and online publications in the South African History Online archive.',
  ];

  /**
   * Returns the landing label for a bundle, or NULL when it is not public.
   */
  public static function label(string $bundle): ?string {
    return self::LABELS[$bundle] ?? NULL;
  }

  /**
   * Returns the meta description for a bundle, or NULL when it is not public.
   */
  public static function description(string $bundle): ?string {
    return self::DESCRIPTIONS[$bundle] ?? NULL;
  }

  /**
   * Route requirement regex allowing only the public bundles.
   */
  public static function routeRequirement(): string {
    return implode('|', array_map('preg_quote', array_keys(self::LABELS)));
  }

}
