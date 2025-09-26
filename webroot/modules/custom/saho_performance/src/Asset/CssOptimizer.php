<?php

namespace Drupal\saho_performance\Asset;

use Drupal\Core\Asset\CssOptimizer as CoreCssOptimizer;

/**
 * Optimizes CSS files for better performance.
 */
class CssOptimizer extends CoreCssOptimizer {

  /**
   * {@inheritdoc}
   */
  public function optimize(array $css_asset) {
    $css_asset = parent::optimize($css_asset);

    // Additional optimizations.
    if (isset($css_asset['data'])) {
      // Remove unused CSS patterns commonly found in Drupal.
      $css_asset['data'] = $this->removeUnusedPatterns($css_asset['data']);

      // Minify more aggressively.
      $css_asset['data'] = $this->aggressiveMinify($css_asset['data']);
    }

    return $css_asset;
  }

  /**
   * Remove commonly unused CSS patterns.
   */
  protected function removeUnusedPatterns($css) {
    // Remove print styles if not print media.
    $css = preg_replace('/@media\s+print\s*\{[^}]*\}/', '', $css);

    // Remove IE-specific hacks if not needed.
    $css = preg_replace('/\*html\s+[^{]*\{[^}]*\}/', '', $css);
    $css = preg_replace('/\*\+html\s+[^{]*\{[^}]*\}/', '', $css);

    // Remove empty rules.
    $css = preg_replace('/[^{}]+\{\s*\}/', '', $css);

    // Remove comments.
    $css = preg_replace('/\/\*[^*]*\*+(?:[^\/][^*]*\*+)*\//', '', $css);

    return $css;
  }

  /**
   * Aggressive CSS minification.
   */
  protected function aggressiveMinify($css) {
    // Remove unnecessary whitespace.
    $css = preg_replace('/\s+/', ' ', $css);

    // Remove whitespace around specific characters.
    $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);

    // Remove trailing semicolon before closing brace.
    $css = str_replace(';}', '}', $css);

    // Remove units from zero values.
    $css = preg_replace('/([: ])0(px|em|rem|%)/', '$10', $css);

    // Shorten hex colors.
    $css = preg_replace('/#([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3/i', '#$1$2$3', $css);

    return trim($css);
  }

}
