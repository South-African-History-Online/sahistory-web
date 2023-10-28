<?php

namespace Drupal\ludwig;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Adds ludwig-managed packages to the autoloader.
 *
 * Service providers are only executed when the container is being built,
 * removing the need to cache the module's package information.
 */
class LudwigServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $root = \Drupal::hasService('app.root') ? \Drupal::root() : DRUPAL_ROOT;
    $package_manager = new PackageManager($root);
    $namespaces = $container->getParameter('container.namespaces');
    foreach ($package_manager->getPackages() as $package_name => $package) {
      if ($package['status'] == 'Installed') {
        if ($package['resource'] == 'psr-4' || $package['resource'] == 'psr-0') {
          $namespace = $package['namespace'];
          // If this namespace exists already, convert it's path(s) to
          // $old_paths array so that we can add more paths to it.
          if (isset($namespaces[$namespace])) {
            if (is_string($namespaces[$namespace])) {
              $old_paths = [];
              $old_paths[] = $namespaces[$namespace];
            }
            elseif (is_array($namespaces[$namespace])) {
              $old_paths = $namespaces[$namespace];
            }
          }
          else {
            $old_paths = [];
          }
          // Create a $new_paths array and add all new paths to it.
          $new_paths = [];
          if (!empty($package['paths'])) {
            foreach ($package['paths'] as $path) {
              !empty($path) ? $new_paths[] = $package['path'] . '/' . $path : $new_paths[] = $package['path'];
            }
          }
          // Merge all 'old' amd 'new' paths into one array
          // and filter out possible duplicates.
          $return_paths = array_unique(array_merge($old_paths, $new_paths));
          // If the namespace has one path only
          // we can give it back as a string.
          if (count($return_paths) == 1) {
            $namespaces[$namespace] = $return_paths[0];
          }
          // If the namespace has multiple paths
          // we should giving them back as an array of paths.
          elseif (count($return_paths) > 1) {
            $namespaces[$namespace] = $return_paths;
          }
        }
      }
    }
    $container->setParameter('container.namespaces', $namespaces);
  }

}
