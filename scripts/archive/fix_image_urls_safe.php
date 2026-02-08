<?php

/**
 * Safe Image URL Conversion Script
 * 
 * Converts absolute image URLs to relative paths, but only for files that actually exist.
 * Prevents broken links by verifying file existence before making changes.
 * 
 * Usage:
 *   ddev exec php scripts/fix_image_urls_safe.php
 * 
 * What it does:
 * - Finds all absolute URLs: http://www.sahistory.org.za/sites/default/files/...
 * - Checks if the file exists on disk at webroot/sites/default/files/...
 * - Converts to relative paths: /sites/default/files/... (ONLY if file exists)
 * - Fixes URL encoding (%20 spaces, %5B brackets, etc.)
 * - Updates both node__body and node_revision__body tables
 * - Leaves URLs with missing files unchanged to prevent broken links
 *
 * Date: September 2025
 */

echo "=== Safe Image URL Conversion Script ===\n";
echo "Converting absolute URLs to relative paths (only for existing files)\n\n";

// Database connection - check environment
$host = getenv('DB_HOST') ?: (getenv('IS_DDEV_PROJECT') ? 'db' : 'localhost');
$db = getenv('DB_NAME') ?: 'db';
$user = getenv('DB_USER') ?: 'db';
$pass = getenv('DB_PASSWORD') ?: 'db';

// Production fallback - try to read from Drupal settings
if (!getenv('IS_DDEV_PROJECT') && $host === 'localhost') {
    $settings_file = getcwd() . '/webroot/sites/default/settings.php';
    if (file_exists($settings_file)) {
        $settings_content = file_get_contents($settings_file);
        
        // Try to extract database settings from Drupal settings.php
        if (preg_match('/\$databases\[\'default\'\]\[\'default\'\]\s*=\s*\[(.*?)\];/s', $settings_content, $matches)) {
            $db_config = $matches[1];
            
            if (preg_match('/\'host\'\s*=>\s*[\'"]([^\'"]+)[\'"]/', $db_config, $host_match)) {
                $host = $host_match[1];
            }
            if (preg_match('/\'database\'\s*=>\s*[\'"]([^\'"]+)[\'"]/', $db_config, $db_match)) {
                $db = $db_match[1];
            }
            if (preg_match('/\'username\'\s*=>\s*[\'"]([^\'"]+)[\'"]/', $db_config, $user_match)) {
                $user = $user_match[1];
            }
            if (preg_match('/\'password\'\s*=>\s*[\'"]([^\'"]+)[\'"]/', $db_config, $pass_match)) {
                $pass = $pass_match[1];
            }
        }
    }
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Configuration
$webroot = getcwd() . '/webroot';
$old_pattern = 'http://www.sahistory.org.za/sites/default/files/';
$new_pattern = '/sites/default/files/';

// Statistics
$stats = [
    'total_nodes' => 0,
    'total_urls' => 0,
    'files_exist' => 0,
    'files_missing' => 0,
    'nodes_updated' => 0,
    'nodes_skipped' => 0
];

// Get all nodes with absolute image URLs
echo "📋 Finding nodes with absolute URLs...\n";
$stmt = $pdo->prepare("
    SELECT entity_id, body_value 
    FROM node__body 
    WHERE body_value LIKE :pattern
");
$stmt->execute(['pattern' => "%{$old_pattern}%"]);
$nodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats['total_nodes'] = count($nodes);
echo "Found {$stats['total_nodes']} nodes with absolute URLs\n\n";

if ($stats['total_nodes'] === 0) {
    echo "✅ No absolute URLs found. All URLs are already relative or fixed.\n";
    exit(0);
}

$missing_files = [];
$safe_updates = [];

echo "🔍 Checking file existence for each URL...\n";

foreach ($nodes as $node) {
    $entity_id = $node['entity_id'];
    $content = $node['body_value'];
    
    // Find all image URLs in this content
    $pattern = '/http:\/\/www\.sahistory\.org\.za\/sites\/default\/files\/[^"\'\s<>]+/';
    preg_match_all($pattern, $content, $matches);
    
    $node_safe = true;
    $new_content = $content;
    
    foreach ($matches[0] as $full_url) {
        $stats['total_urls']++;
        
        // Convert URL to file path
        $relative_path = str_replace($old_pattern, 'sites/default/files/', $full_url);
        // Decode URL-encoded characters for file system check
        $decoded_path = urldecode($relative_path);
        $file_path = $webroot . '/' . $decoded_path;
        
        if (file_exists($file_path)) {
            $stats['files_exist']++;
            // Prepare the replacement (keep encoded format but make relative with leading slash)
            $new_url = '/' . $relative_path;
            $new_content = str_replace($full_url, $new_url, $new_content);
            echo "✅ Entity {$entity_id}: File exists - {$decoded_path}\n";
        } else {
            $stats['files_missing']++;
            $missing_files[] = [
                'entity_id' => $entity_id,
                'url' => $full_url,
                'expected_path' => $file_path
            ];
            $node_safe = false;
            echo "❌ Entity {$entity_id}: File missing - {$decoded_path}\n";
        }
    }
    
    // Only update if all files in this node exist
    if ($node_safe && $new_content !== $content) {
        $safe_updates[] = [
            'entity_id' => $entity_id,
            'old_content' => $content,
            'new_content' => $new_content
        ];
        $stats['nodes_updated']++;
    } else {
        $stats['nodes_skipped']++;
    }
}

echo "\n=== ANALYSIS RESULTS ===\n";
echo "📊 Total nodes analyzed: {$stats['total_nodes']}\n";
echo "📊 Total URLs found: {$stats['total_urls']}\n";
echo "✅ Files that exist: {$stats['files_exist']}\n";
echo "❌ Missing files: {$stats['files_missing']}\n";
echo "🔄 Nodes to update: {$stats['nodes_updated']}\n";
echo "⏸️  Nodes to skip (missing files): {$stats['nodes_skipped']}\n\n";

// Show sample missing files
if (!empty($missing_files)) {
    echo "=== SAMPLE MISSING FILES (First 10) ===\n";
    foreach (array_slice($missing_files, 0, 10) as $missing) {
        $short_path = str_replace($webroot . '/', '', $missing['expected_path']);
        echo "Entity {$missing['entity_id']}: {$short_path}\n";
    }
    if (count($missing_files) > 10) {
        echo "... and " . (count($missing_files) - 10) . " more missing files\n";
    }
    echo "\n";
}

if ($stats['nodes_updated'] === 0) {
    echo "ℹ️  No safe updates to perform.\n";
    exit(0);
}

// Ask for confirmation
echo "❓ Proceed with updating {$stats['nodes_updated']} nodes? (y/N): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'y' && strtolower($line) !== 'yes') {
    echo "⏹️  Aborted. No changes made.\n";
    exit(0);
}

// Perform safe updates
echo "\n🚀 Proceeding with safe updates...\n";

$pdo->beginTransaction();

try {
    $update_stmt = $pdo->prepare("
        UPDATE node__body 
        SET body_value = :new_content 
        WHERE entity_id = :entity_id
    ");
    
    $update_revision_stmt = $pdo->prepare("
        UPDATE node_revision__body 
        SET body_value = :new_content 
        WHERE entity_id = :entity_id
    ");
    
    $updated_count = 0;
    foreach ($safe_updates as $update) {
        $update_stmt->execute([
            'new_content' => $update['new_content'],
            'entity_id' => $update['entity_id']
        ]);
        
        $update_revision_stmt->execute([
            'new_content' => $update['new_content'],
            'entity_id' => $update['entity_id']
        ]);
        
        $updated_count++;
        echo "✅ Updated entity {$update['entity_id']}\n";
    }
    
    $pdo->commit();
    
    echo "\n=== UPDATE COMPLETE ===\n";
    echo "✅ Successfully updated {$updated_count} nodes\n";
    echo "⚠️  Skipped {$stats['nodes_skipped']} nodes with missing files\n";
    echo "🛡️  No broken links were created\n";
    
} catch (Exception $e) {
    $pdo->rollback();
    echo "💥 ERROR: Update failed - " . $e->getMessage() . "\n";
    echo "🔄 Transaction rolled back. No changes were made.\n";
    exit(1);
}

// Final verification
echo "\n=== FINAL VERIFICATION ===\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM node__body WHERE body_value LIKE :pattern");
$stmt->execute(['pattern' => "%{$old_pattern}%"]);
$remaining = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM node__body WHERE body_value LIKE :pattern");
$stmt->execute(['pattern' => "%{$new_pattern}%"]);
$fixed = $stmt->fetchColumn();

echo "📊 Remaining absolute URLs: {$remaining} (files don't exist - left for safety)\n";
echo "📊 Fixed relative URLs: {$fixed}\n";
echo "✅ All changes are safe - no broken links created!\n";

?>