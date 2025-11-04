<?php

/**
 * @file
 * Drupal site-specific configuration file for shop.sahistory.org.za.
 *
 * This is the settings file for the SAHO Commerce Shop multisite.
 */

// Include the default settings from the parent site as a base.
// This ensures consistency across the multisite installation.
$databases = [];

// Site-specific settings for shop.sahistory.org.za
// This will be configured by DDEV settings.ddev.php

/**
 * Location of the site configuration files.
 *
 * The shop site will have its own configuration directory to maintain
 * separation from the main site.
 */
$settings['config_sync_directory'] = '../config/shop';

/**
 * Hash salt for security.
 *
 * This will be set by settings.ddev.php or settings.local.php
 */
$settings['hash_salt'] = '';

/**
 * Trusted host patterns.
 *
 * Configure the domains that this shop site responds to.
 */
$settings['trusted_host_patterns'] = [
  '^shop\.sahistory\.org\.za$',
  '^shop\.ddev\.site$',
  '^localhost$',
  '^127\.0\.0\.1$',
];

/**
 * File system paths.
 */
$settings['file_public_path'] = 'sites/shop.sahistory.org.za/files';
$settings['file_private_path'] = 'sites/shop.sahistory.org.za/files/private';
$settings['file_temp_path'] = '/tmp';

/**
 * Reverse proxy configuration (if behind a proxy in production).
 */
$settings['reverse_proxy'] = FALSE;

/**
 * Skip permissions hardening for development (will be set by DDEV).
 */
// $settings['skip_permissions_hardening'] = TRUE;

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/default.services.yml';

/**
 * Environment-specific settings.
 *
 * Include DDEV-generated settings for local development.
 */
if (file_exists($app_root . '/' . $site_path . '/settings.ddev.php')) {
  include $app_root . '/' . $site_path . '/settings.ddev.php';
}

/**
 * Local settings override.
 *
 * Include optional local settings file for custom overrides.
 */
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
