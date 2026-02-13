#!/usr/bin/env drush

<?php

/**
 * @file
 * Delete deprecated content types: blog and frontpagecustom.
 *
 * Usage (PRODUCTION):
 *   drush php:script delete-deprecated-content-types.php
 *
 * This script will:
 * 1. Count content to be deleted
 * 2. Ask for confirmation
 * 3. Delete all nodes of these types
 * 4. Delete the content type configurations
 * 5. Report results
 *
 * IMPORTANT: Create a database backup before running:
 *   drush sql:dump --gzip --result-file=backup-$(date +%Y%m%d-%H%M%S).sql
 */

use Drupal\node\Entity\NodeType;

// Content types to delete.
$content_types_to_delete = ['blog', 'frontpagecustom'];

echo "\n";
echo "========================================\n";
echo "Deprecated Content Type Deletion Script\n";
echo "========================================\n\n";

// Step 1: Count existing content.
echo "Step 1: Counting existing content...\n\n";

$storage = \Drupal::entityTypeManager()->getStorage('node');
$totals = [];

foreach ($content_types_to_delete as $type) {
  $nids = $storage->getQuery()
    ->condition('type', $type)
    ->accessCheck(FALSE)
    ->execute();

  $count = count($nids);
  $totals[$type] = $count;

  if ($count > 0) {
    echo "  - {$type}: {$count} nodes\n";
  } else {
    echo "  - {$type}: No content found\n";
  }
}

$total_nodes = array_sum($totals);

echo "\nTotal nodes to delete: {$total_nodes}\n\n";

// Step 2: Confirmation.
if ($total_nodes > 0) {
  echo "========================================\n";
  echo "WARNING: This will permanently delete {$total_nodes} nodes!\n";
  echo "========================================\n\n";

  echo "Have you created a database backup? (yes/no): ";
  $backup_confirm = trim(fgets(STDIN));

  if (strtolower($backup_confirm) !== 'yes') {
    echo "\nABORTED: Please create a backup first:\n";
    echo "  drush sql:dump --gzip --result-file=backup-\$(date +%Y%m%d-%H%M%S).sql\n\n";
    exit(1);
  }

  echo "\nType 'DELETE' (in capitals) to confirm deletion: ";
  $confirm = trim(fgets(STDIN));

  if ($confirm !== 'DELETE') {
    echo "\nABORTED: Confirmation not received.\n\n";
    exit(1);
  }

  // Step 3: Delete content.
  echo "\n";
  echo "Step 2: Deleting content...\n\n";

  foreach ($content_types_to_delete as $type) {
    if ($totals[$type] > 0) {
      echo "  Deleting {$totals[$type]} {$type} nodes... ";

      $nids = $storage->getQuery()
        ->condition('type', $type)
        ->accessCheck(FALSE)
        ->execute();

      // Delete in batches of 50 to avoid memory issues.
      $batches = array_chunk($nids, 50);
      $deleted_count = 0;

      foreach ($batches as $batch) {
        $nodes = $storage->loadMultiple($batch);
        $storage->delete($nodes);
        $deleted_count += count($batch);

        // Progress indicator for large deletions.
        if ($totals[$type] > 100) {
          echo ".";
        }
      }

      echo " DONE ({$deleted_count} deleted)\n";
    }
  }
}

// Step 4: Delete content type configurations.
echo "\nStep 3: Deleting content type configurations...\n\n";

foreach ($content_types_to_delete as $type) {
  $node_type = NodeType::load($type);
  if ($node_type) {
    $node_type->delete();
    echo "  - Deleted content type: {$type}\n";
  } else {
    echo "  - Content type not found (already deleted): {$type}\n";
  }
}

// Step 5: Summary.
echo "\n";
echo "========================================\n";
echo "COMPLETED SUCCESSFULLY\n";
echo "========================================\n\n";

echo "Summary:\n";
echo "  - Nodes deleted: {$total_nodes}\n";
echo "  - Content types deleted: " . count($content_types_to_delete) . "\n\n";

echo "Next steps:\n";
echo "  1. Export configuration:\n";
echo "     drush config:export -y\n\n";
echo "  2. Clear cache:\n";
echo "     drush cache:rebuild\n\n";
echo "  3. Commit configuration changes to Git\n\n";

exit(0);
