<?php

namespace Drupal\ludwig;

/**
 * An interface for PackageManager.
 */
interface PackageManagerInterface {

  /**
   * Gets the ludwig-managed packages.
   *
   * @return array
   *   The packages, keyed by package name.
   */
  public function getPackages();

}
