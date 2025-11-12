<?php

/**
 * @file
 * DDEV-generated settings for shop.sahistory.org.za multisite.
 *
 * This file configures the database and development settings for the shop site.
 */

$host = "db";
$port = 3306;
$driver = "mysql";

// Shop site uses separate database
$databases['default']['default']['database'] = "shop";
$databases['default']['default']['username'] = "db";
$databases['default']['default']['password'] = "db";
$databases['default']['default']['host'] = $host;
$databases['default']['default']['port'] = $port;
$databases['default']['default']['driver'] = $driver;
$databases['default']['default']['prefix'] = '';

// Old publications database connection for migration
$databases['old_publications']['default']['database'] = "old_publications";
$databases['old_publications']['default']['username'] = "db";
$databases['old_publications']['default']['password'] = "db";
$databases['old_publications']['default']['host'] = $host;
$databases['old_publications']['default']['port'] = $port;
$databases['old_publications']['default']['driver'] = $driver;
$databases['old_publications']['default']['prefix'] = '';

// Security: hash salt
// IMPORTANT: Set this in settings.local.php or environment variable
// Generate with: drush eval "echo \Drupal\Component\Utility\Crypt::randomBytesBase64(55)"
// DO NOT commit the actual hash_salt to git!
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
// Fallback for DDEV (override in settings.local.php for production)
if (empty($settings['hash_salt'])) {
  $settings['hash_salt'] = getenv('DRUPAL_HASH_SALT') ?: 'INSECURE-DEVELOPMENT-ONLY-CHANGE-IN-PRODUCTION';
}

// Recommended setting for Drupal 11
$settings['state_cache'] = TRUE;

// This will prevent Drupal from setting read-only permissions on sites/default.
$settings['skip_permissions_hardening'] = TRUE;

// This will ensure the site can only be accessed through the intended host names.
$settings['trusted_host_patterns'] = [
  '^shop\.sahistory\.org\.za$',
  '^shop\.ddev\.site$',
  '^localhost$',
  '^127\.0\.0\.1$',
];

// Don't use Symfony's APCLoader. ddev includes APCu.
$settings['class_loader_auto_detect'] = FALSE;

// Set $settings['config_sync_directory'] if not set in settings.php.
if (empty($settings['config_sync_directory'])) {
  $settings['config_sync_directory'] = '../config/shop';
}

// Override drupal/symfony_mailer default config to use Mailpit.
$config['symfony_mailer.settings']['default_transport'] = 'sendmail';
$config['symfony_mailer.mailer_transport.sendmail']['plugin'] = 'smtp';
$config['symfony_mailer.mailer_transport.sendmail']['configuration']['user'] = '';
$config['symfony_mailer.mailer_transport.sendmail']['configuration']['pass'] = '';
$config['symfony_mailer.mailer_transport.sendmail']['configuration']['host'] = 'localhost';
$config['symfony_mailer.mailer_transport.sendmail']['configuration']['port'] = '1025';

// ============================================
// DEVELOPMENT OVERRIDES
// ============================================

// Enable verbose logging for errors.
$config['system.logging']['error_level'] = 'verbose';

// Database log settings
$config['dblog.settings']['row_limit'] = 10000;

// Disable caching for development
$config['system.performance']['cache']['page']['max_age'] = 0;
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Enable development modules
$config['core.extension']['module']['devel'] = 1;
$config['core.extension']['module']['views_ui'] = 1;
$config['core.extension']['module']['field_ui'] = 1;
$config['core.extension']['module']['dblog'] = 1;

// Enable Twig debugging for theme development
$settings['twig_debug'] = TRUE;
$settings['twig_auto_reload'] = TRUE;
$settings['twig_cache'] = FALSE;

// PHP settings for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M');

// Disable secure cookies for local development
ini_set('session.cookie_secure', 0);

// Disable cache for better debugging
$settings['cache']['bins']['render'] = 'cache.backend.memory';
$settings['cache']['bins']['page'] = 'cache.backend.memory';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.memory';

// Redis configuration (if available).
if (extension_loaded('redis') && !empty(getenv('REDIS_HOST'))) {
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = getenv('REDIS_HOST');
  $settings['redis.connection']['port'] = getenv('REDIS_PORT') ?: '6379';
  $settings['cache']['default'] = 'cache.backend.redis';
  $settings['cache_prefix'] = 'shop_redis_';
}
