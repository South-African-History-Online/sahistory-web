<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_classroom\Unit;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Guards the translation-dedupe pair on every classroom listing view.
 *
 * Presentations carry 11 language translations, and a node-based View
 * without a language filter returns one row per translation (the hub once
 * showed the same deck 11 times and claimed 596 resources for 195 decks,
 * see issue #471). Every view that lists presentations therefore needs the
 * `default_langcode = 1` filter plus `rendering_language` set to the
 * interface language. This test reads the exported config so a well-meaning
 * Views UI edit cannot silently reintroduce the multiplication.
 *
 * @group saho_classroom
 */
final class ClassroomViewsLanguageGuardTest extends UnitTestCase {

  /**
   * Views (config names) that list translated presentation nodes.
   */
  private const GUARDED_VIEWS = [
    'views.view.classroom',
    'views.view.classroom_presentations',
    'views.view.saho_classroom_topic',
  ];

  /**
   * Locates the repository's config/sync directory.
   */
  private static function configSyncDir(): string {
    $dir = __DIR__;
    for ($i = 0; $i < 12; $i++) {
      if (is_dir($dir . '/config/sync')) {
        return $dir . '/config/sync';
      }
      $parent = dirname($dir);
      if ($parent === $dir) {
        break;
      }
      $dir = $parent;
    }
    throw new \RuntimeException('config/sync directory not found above ' . __DIR__);
  }

  /**
   * Data provider: one case per guarded view.
   */
  public static function guardedViewsProvider(): array {
    $cases = [];
    foreach (self::GUARDED_VIEWS as $name) {
      $cases[$name] = [$name];
    }
    return $cases;
  }

  /**
   * Every display that defines filters keeps the default_langcode filter.
   *
   * @dataProvider guardedViewsProvider
   */
  public function testDefaultLangcodeFilter(string $config_name): void {
    $view = Yaml::parseFile(self::configSyncDir() . '/' . $config_name . '.yml');
    $this->assertIsArray($view['display'] ?? NULL, "$config_name has displays");

    $checked = 0;
    foreach ($view['display'] as $display_id => $display) {
      $filters = $display['display_options']['filters'] ?? NULL;
      if ($filters === NULL) {
        // Display inherits the default display's filters.
        continue;
      }
      $checked++;
      $filter = $filters['default_langcode'] ?? NULL;
      $this->assertIsArray($filter, "$config_name:$display_id defines filters but lacks default_langcode");
      $this->assertSame('node_field_data', $filter['table'] ?? NULL, "$config_name:$display_id default_langcode table");
      $this->assertSame('1', (string) ($filter['value'] ?? ''), "$config_name:$display_id default_langcode must be 1");
      $this->assertFalse((bool) ($filter['exposed'] ?? FALSE), "$config_name:$display_id default_langcode must not be exposed");
    }
    $this->assertGreaterThan(0, $checked, "$config_name: no display defines filters");
  }

  /**
   * The default display renders rows in the interface language.
   *
   * @dataProvider guardedViewsProvider
   */
  public function testRenderingLanguage(string $config_name): void {
    $view = Yaml::parseFile(self::configSyncDir() . '/' . $config_name . '.yml');
    $rendering = $view['display']['default']['display_options']['rendering_language'] ?? NULL;
    $this->assertSame('***LANGUAGE_language_interface***', $rendering, "$config_name default display rendering_language");
    foreach ($view['display'] as $display_id => $display) {
      if (array_key_exists('rendering_language', $display['display_options'] ?? [])) {
        $this->assertSame('***LANGUAGE_language_interface***', $display['display_options']['rendering_language'], "$config_name:$display_id rendering_language");
      }
    }
  }

}
