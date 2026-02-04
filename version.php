<?php
/**
 * Display current deployment version
 *
 * Access at: https://yourdomain.com/version.php
 */

// Get git tag (version)
$version = 'unknown';
$git_dir = __DIR__ . '/.git';

if (is_dir($git_dir)) {
    // Try to get the latest git tag
    exec('git describe --tags --abbrev=0 2>/dev/null', $output, $return_code);
    if ($return_code === 0 && !empty($output[0])) {
        $version = trim($output[0]);
    } else {
        // Fallback to commit hash if no tags exist
        exec('git rev-parse --short HEAD 2>/dev/null', $commit_output, $commit_return);
        if ($commit_return === 0 && !empty($commit_output[0])) {
            $version = 'commit-' . trim($commit_output[0]);
        }
    }

    // Get commit date as deployment timestamp
    exec('git log -1 --format=%ct 2>/dev/null', $timestamp_output);
    $timestamp = !empty($timestamp_output[0]) ? (int)$timestamp_output[0] : time();
    $deployed_at = date('Y-m-d H:i:s T', $timestamp);
} else {
    $deployed_at = 'never';
}

header('Content-Type: text/plain');
echo "Version: {$version}\n";
echo "Deployed At: {$deployed_at}\n";
echo "Server: " . gethostname() . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
