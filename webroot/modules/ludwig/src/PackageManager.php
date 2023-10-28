<?php

namespace Drupal\ludwig;

use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Provides information about ludwig-managed packages.
 *
 * Extensions (modules, profiles) can define a ludwig.json which is
 * discovered by this class. This discovery works even without a
 * Drupal installation, and covers non-installed extensions.
 */
class PackageManager implements PackageManagerInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new PackageManager object.
   *
   * @param string $root
   *   The app root.
   */
  public function __construct($root) {
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackages() {
    $listing = new ExtensionDiscovery($this->root);
    // Get all profiles, and modules belonging to those profiles.
    $profiles = $listing->scan('profile');
    $profile_directories = array_map(function ($profile) {
      return $profile->getPath();
    }, $profiles);
    $listing->setProfileDirectories($profile_directories);
    $modules = $listing->scan('module');
    /** @var \Drupal\Core\Extension\Extension[] $extensions */
    $extensions = $profiles + $modules;

    $packages = [];
    // We need the main drupal version for the compatibility check later.
    $drupal_main_version = 'd' . explode('.', \Drupal::VERSION, 2)[0];
    // We need a list for unique extension-package check later.
    $packages_list = [];
    foreach ($extensions as $extension_name => $extension) {
      $extension_path = $extension->getPath();
      // Let's check if this module has ludwig.json file and
      // proceed only if it does.
      if (is_file($this->root . '/' . $extension_path . '/ludwig.json')) {
        $config = $this->jsonRead($this->root . '/' . $extension_path . '/ludwig.json');
        $config += [
          'require' => [],
        ];
        foreach ($config['require'] as $package_name => $package_data) {
          // The package name can be appended with core compatibility strings
          // (one or multiple drupal core versons all separated with spaces).
          $package_name_plus = explode(' ', $package_name);
          $package_name = $package_name_plus[0];
          // Multiple extensions can require the same package. We need the
          // unique extension+package name for our $packages array items.
          $extension_package_name = $extension_name . '_' . $package_name;
          if (isset($package_name_plus[1]) && !in_array($drupal_main_version, $package_name_plus)) {
            // Core compatibility doesn't match. Skip this package version.
            continue;
          }
          // We don't want the extension to require the same package twice
          // due to the improperly configured ludwig.json file.
          if (isset($packages_list[$extension_package_name])) {
            // Skipping the package. This one exists already.
            continue;
          }
          else {
            $packages_list[$extension_package_name] = TRUE;
          }
          $package_path = $extension_path . '/lib/' . str_replace('/', '-', $package_name) . '/' . $package_data['version'];
          $package = $this->jsonRead($this->root . '/' . $package_path . '/composer.json');
          $description = !empty($package['description']) ? $package['description'] : '';
          $homepage = !empty($package['homepage']) ? $package['homepage'] : '';
          // Create the base package data array.
          $package_base = [
            'name' => $package_name,
            'version' => $package_data['version'],
            'description' => $description,
            'homepage' => $homepage,
            'provider' => $extension_name,
            'provider_path' => $extension_path,
            'download_url' => $package_data['url'],
            'path' => $package_path,
          ];
          if (empty($package)) {
            // Missing package. This one needs a download.
            $package_append = [
              'namespace' => '',
              'paths' => [],
              'status' => 'Missing',
              'resource' => '',
            ];
            $packages[$extension_package_name] = array_merge($package_base, $package_append);
            continue;
          }
          if (!empty($package['autoload'])) {
            $resources = array_keys($package['autoload']);
            // Iterate through all autoload types (resources).
            foreach ($resources as $resource) {
              // Making the package name unique for multi-resource packages.
              $extension_package_resource_name = $extension_package_name . '_' . $resource;
              if (!empty($package['autoload'][$resource])) {
                $status = '';
                if ($resource == 'files' || $resource == 'classmap' || $resource == 'exclude-from-classmap' || $resource == 'target-dir') {
                  $autoload = $package['autoload'];
                  $package_namespaces = [$resource];
                  if ($resource == 'classmap' || $resource == 'files') {
                    // Additional integration inside .module file is needed.
                    $status = 'Not installed';
                    // The .module file integration check.
                    if (is_file($this->root . '/' . $extension_path . '/' . $extension_name . '.module')) {
                      $module_file = file_get_contents($this->root . '/' . $extension_path . '/' . $extension_name . '.module');
                    }
                    // Mark back this package as 'Installed' if this
                    // module's .module file is calling ludwig.require_once
                    // service with this package name as an argument.
                    if (!empty($module_file) && (strpos($module_file, 'ludwig.require_once') !== FALSE) && ((strpos($module_file, "requireOnce('" . $package_name) !== FALSE) || (strpos($module_file, "requireOnce( '" . $package_name) !== FALSE) || (strpos($module_file, 'requireOnce("' . $package_name) !== FALSE) || (strpos($module_file, 'requireOnce( "' . $package_name) !== FALSE))) {
                      $status = 'Installed';
                    }
                  }
                  else {
                    // 'exclude-from-classmap' and 'target-dir' types.
                    $status = 'Not supported';
                  }
                }
                elseif ($resource == 'psr-4' || $resource == 'psr-0') {
                  $autoload = $package['autoload'][$resource];
                  $package_namespaces = array_keys($autoload);
                }
                else {
                  // The unknown library type.
                  $package_append = [
                    'namespace' => '',
                    'paths' => [],
                    'status' => 'Unknown type',
                    'resource' => 'unknown',
                  ];
                  $packages[$extension_package_resource_name] = array_merge($package_base, $package_append);
                  continue;
                }
                $status = !empty($status) ? $status : 'Installed';
                // Iterate through all the records inside single autoload type.
                foreach ($package_namespaces as $namespace_key => $namespace) {
                  // Making the package name unique for multi-namespaced
                  // resources.
                  $extension_package_resource_namespace_name = $extension_package_resource_name . '_' . $namespace_key;
                  $paths_raw = $autoload[$namespace];
                  // Support for both single path (string) and multiple
                  // paths (array) inside one resource.
                  $paths = [];
                  if (is_array($paths_raw)) {
                    $paths = $paths_raw;
                  }
                  elseif (is_string($paths_raw)) {
                    $paths[] = $paths_raw;
                  }
                  // Autoloading fails if the namespace ends with a backslash.
                  $namespace = trim($namespace, '\\');
                  // Iterate through all the paths inside this resource.
                  foreach ($paths as $key => $value) {
                    $paths[$key] = rtrim($paths[$key], './');
                    // Core only assumes that LudwigServiceProvider is adding
                    // PSR-4 paths, each PSR-0 path needs to be converted
                    // in order to work.
                    if ($resource == 'psr-0' && !empty($namespace)) {
                      if (!empty($paths[$key])) {
                        $paths[$key] .= '/';
                      }
                      $paths[$key] .= str_replace('\\', '/', $namespace);
                    }
                  }
                  // Add new package.
                  $package_append = [
                    'namespace' => $namespace,
                    'paths' => $paths,
                    'status' => $status,
                    'resource' => $resource,
                  ];
                  $packages[$extension_package_resource_namespace_name] = array_merge($package_base, $package_append);
                }
              }
            }
          }
          elseif (!empty($package['include-path'])) {
            // This is the Legacy library (depricated type).
            // They do not have autoload composer.json section
            // but "include-path" section instead.
            $package_append = [
              'namespace' => '',
              'paths' => [],
              'status' => 'Not supported',
              'resource' => 'legacy',
            ];
            $packages[$extension_package_name] = array_merge($package_base, $package_append);
          }
          else {
            // The library without the autoload section.
            $package_append = [
              'namespace' => '',
              'paths' => [],
              'status' => 'Inactive',
              'resource' => 'inactive',
            ];
            $packages[$extension_package_name] = array_merge($package_base, $package_append);
          }
        }
      }
    }

    // Two versions of the same package are not possible.
    // If multiple providers require the same package
    // we keep the highest required version only, since it has
    // the best probability to work for all providers, and
    // it is the most secure. And we mark all lower package
    // versions as 'Overriden'.
    $loop1_packages = $packages;
    $loop2_packages = $packages;
    foreach ($loop1_packages as $loop1_name => $loop1_package) {
      foreach ($loop2_packages as $loop2_name => $loop2_package) {
        // Let's strip all non-numeric characters from the package
        // versions in order to compare them successfully.
        if (($loop2_package['name'] == $loop1_package['name']) && ($loop2_name != $loop1_name) && (preg_replace("/[^0-9.]/", "", $loop2_package['version']) < preg_replace("/[^0-9.]/", "", $loop1_package['version']))) {
          $packages[$loop2_name]['status'] = 'Overridden';
        }
      }
    }

    return $packages;
  }

  /**
   * Reads and decodes a json file into an array.
   *
   * @param string $filename
   *   Name of the file to read.
   *
   * @return array
   *   The decoded json data.
   */
  protected function jsonRead($filename) {
    $data = [];
    if (file_exists($filename)) {
      $data = file_get_contents($filename);
      $data = json_decode($data, TRUE);
      if (!$data) {
        $data = [];
      }
    }

    return $data;
  }

}
