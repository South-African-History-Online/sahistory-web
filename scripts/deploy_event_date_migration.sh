#!/bin/bash
#
# Deployment script for Event Date Field Consolidation
#
# This script handles the migration of field_this_day_in_history_3 and
# field_this_day_in_history_date_2 to the new field_event_date.
#
# IMPORTANT: The order of operations matters!
# 1. First create the new field (without deleting old ones)
# 2. Run update hooks to migrate data
# 3. Then import remaining config to delete old fields
#
# Usage: Run this script from the project root directory
#        ./scripts/deploy_event_date_migration.sh

set -e

echo "=========================================="
echo "Event Date Field Migration Deployment"
echo "=========================================="

# Check we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "Error: Please run this script from the project root directory"
    exit 1
fi

echo ""
echo "Step 1: Clear cache"
ddev drush cr

echo ""
echo "Step 2: Create the new field_event_date field storage and field"
echo "        (This must happen BEFORE we delete the old fields)"

# Import only the new field configs first
ddev drush config:import --partial --source=config/sync -y \
    --filter='field.storage.node.field_event_date,field.field.node.event.field_event_date' 2>/dev/null || {
    echo "Partial import not available, trying alternative approach..."
    # Alternative: manually create the field via PHP
    ddev drush php:eval "
        \$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_event_date');
        if (!\$field_storage) {
            \$field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
                'field_name' => 'field_event_date',
                'entity_type' => 'node',
                'type' => 'datetime',
                'settings' => ['datetime_type' => 'date'],
            ]);
            \$field_storage->save();
            echo 'Created field_event_date storage\n';
        }

        \$field = \Drupal\field\Entity\FieldConfig::loadByName('node', 'event', 'field_event_date');
        if (!\$field) {
            \$field = \Drupal\field\Entity\FieldConfig::create([
                'field_storage' => \$field_storage,
                'bundle' => 'event',
                'label' => 'Event Date',
            ]);
            \$field->save();
            echo 'Created field_event_date field on event bundle\n';
        }
    "
}

echo ""
echo "Step 3: Run database updates to migrate the data"
ddev drush updb -y

echo ""
echo "Step 4: Import the full configuration (this will delete old fields)"
ddev drush cim -y

echo ""
echo "Step 5: Final cache clear"
ddev drush cr

echo ""
echo "Step 6: Verify migration"
COUNT=$(ddev drush sqlq "SELECT COUNT(*) FROM node__field_event_date")
echo "Records in field_event_date: $COUNT"

echo ""
echo "=========================================="
echo "Migration complete!"
echo "=========================================="
