<?php

namespace Drupal\ludwig;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides service for ludwig require_once calls.
 *
 * 'classmap' and 'files' libraries are not supported by Ludwig
 * automatically. Contrib modules can take advantage of this
 * Ludwig service to require_once necessary files inside
 * their scripts (.module and/or .install files usually).
 */
class RequireOnce {

  use StringTranslationTrait;

  /**
   * The dummy constructor.
   *
   * All we need from this service class is the helper function below.
   */
  public function __construct() {
  }

  /**
   * The helper function for Ludwig integration.
   *
   * @param string $package_name
   *   The package name.
   * @param string $file_to_require
   *   The file to require.
   * @param string $dir_name
   *   The caller module directory name.
   */
  public function requireOnce($package_name, $file_to_require, $dir_name) {
    $ludwig_json = $dir_name . '/ludwig.json';
    if (file_exists($ludwig_json)) {
      $packages = file_get_contents($ludwig_json);
      $packages = json_decode($packages, TRUE);
    }
    else {
      throw new \Exception(sprintf('File not found: %s.', $ludwig_json));
    }
    if (!empty($packages['require'][$package_name]['version'])) {
      $version = $packages['require'][$package_name]['version'];
    }
    else {
      throw new \Exception(sprintf('The %s library "version" argument in %s file is empty.', $package_name, $ludwig_json));
    }
    $require = $dir_name . '/lib/' . str_replace('/', '-', $package_name) . '/' . $version . '/' . $file_to_require;
    if (file_exists($require)) {
      require_once $require;
    }
    else {
      \Drupal::logger('ludwig')->error($this->t('File not found: @require.', [
        '@require' => $require,
      ]));
    }
  }

}
