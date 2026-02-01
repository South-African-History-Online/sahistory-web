<?php
/**
 * Display current deploy version
 *
 * Access at: https://yourdomain.com/version.php
 */

$version_file = __DIR__ . '/DEPLOY_VERSION';

if (file_exists($version_file)) {
    $version = trim(file_get_contents($version_file));
    $timestamp = filemtime($version_file);
    $deployed_at = date('Y-m-d H:i:s T', $timestamp);
} else {
    $version = 'unknown';
    $deployed_at = 'never';
}

header('Content-Type: text/plain');
echo "Deploy Version: #{$version}\n";
echo "Deployed At: {$deployed_at}\n";
echo "Server: " . gethostname() . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
