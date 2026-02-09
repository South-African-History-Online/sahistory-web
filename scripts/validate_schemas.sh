#!/bin/bash

# Schema.org Validation Script
# Tests schema generation for all content types

echo "========================================="
echo "Schema.org Validation for SAHO"
echo "========================================="
echo ""

# Test Historical Events (event content type)
echo "=== Testing Historical Events (TDIH) ==="
NIDS=$(ddev drush sqlq "SELECT GROUP_CONCAT(nid) FROM (SELECT nid FROM node_field_data WHERE type='event' AND status=1 ORDER BY nid DESC LIMIT 3) AS t")
IFS=',' read -ra NID_ARRAY <<< "$NIDS"

for NID in "${NID_ARRAY[@]}"; do
  echo "Testing node $NID..."
  ddev drush ev "
    \$node = \Drupal\node\Entity\Node::load($NID);
    if (\$node) {
      \$schema = \Drupal::service('saho_tools.schema_org_service')->generateSchemaForNode(\$node);
      if (empty(\$schema)) {
        echo '  ❌ ERROR: No schema generated' . PHP_EOL;
      } else {
        \$errors = [];
        if (!isset(\$schema['@type'])) \$errors[] = 'Missing @type';
        if (\$schema['@type'] !== 'Event') \$errors[] = 'Wrong @type';
        if (!isset(\$schema['additionalType'])) \$errors[] = 'Missing HistoricalEvent additionalType';
        if (!isset(\$schema['startDate'])) \$errors[] = 'Missing startDate';
        if (!isset(\$schema['endDate'])) \$errors[] = 'Missing endDate';
        if (!isset(\$schema['location'])) \$errors[] = 'Missing location';
        if (!isset(\$schema['description'])) \$errors[] = 'Missing description';
        if (!isset(\$schema['publisher'])) \$errors[] = 'Missing publisher';
        if (isset(\$schema['eventStatus'])) \$errors[] = 'Has eventStatus (should not)';

        if (empty(\$errors)) {
          echo '  ✅ OK: All required fields present' . PHP_EOL;
        } else {
          echo '  ❌ ERRORS: ' . implode(', ', \$errors) . PHP_EOL;
        }
      }
    }
  "
done

echo ""

# Test Upcoming Events
echo "=== Testing Upcoming Events ==="
NIDS=$(ddev drush sqlq "SELECT GROUP_CONCAT(nid) FROM (SELECT nid FROM node_field_data WHERE type='upcomingevent' AND status=1 ORDER BY nid DESC LIMIT 3) AS t")
IFS=',' read -ra NID_ARRAY <<< "$NIDS"

for NID in "${NID_ARRAY[@]}"; do
  echo "Testing node $NID..."
  ddev drush ev "
    \$node = \Drupal\node\Entity\Node::load($NID);
    if (\$node) {
      \$schema = \Drupal::service('saho_tools.schema_org_service')->generateSchemaForNode(\$node);
      if (empty(\$schema)) {
        echo '  ❌ ERROR: No schema generated' . PHP_EOL;
      } else {
        \$errors = [];
        if (!isset(\$schema['@type'])) \$errors[] = 'Missing @type';
        if (\$schema['@type'] !== 'Event') \$errors[] = 'Wrong @type';
        if (!isset(\$schema['startDate'])) \$errors[] = 'Missing startDate';
        if (!isset(\$schema['endDate'])) \$errors[] = 'Missing endDate';
        if (!isset(\$schema['location'])) \$errors[] = 'Missing location';
        if (!isset(\$schema['description'])) \$errors[] = 'Missing description';
        if (!isset(\$schema['organizer'])) \$errors[] = 'Missing organizer';
        if (!isset(\$schema['eventStatus'])) \$errors[] = 'Missing eventStatus';
        if (!isset(\$schema['offers'])) \$errors[] = 'Missing offers';

        if (empty(\$errors)) {
          echo '  ✅ OK: All required fields present' . PHP_EOL;
        } else {
          echo '  ❌ ERRORS: ' . implode(', ', \$errors) . PHP_EOL;
        }
      }
    }
  "
done

echo ""

# Test Images
echo "=== Testing Images ==="
NIDS=$(ddev drush sqlq "SELECT GROUP_CONCAT(nid) FROM (SELECT nid FROM node_field_data WHERE type='image' AND status=1 ORDER BY nid DESC LIMIT 3) AS t")
IFS=',' read -ra NID_ARRAY <<< "$NIDS"

for NID in "${NID_ARRAY[@]}"; do
  echo "Testing node $NID..."
  ddev drush ev "
    \$node = \Drupal\node\Entity\Node::load($NID);
    if (\$node) {
      \$schema = \Drupal::service('saho_tools.schema_org_service')->generateSchemaForNode(\$node);
      if (empty(\$schema)) {
        echo '  ❌ ERROR: No schema generated' . PHP_EOL;
      } else {
        \$errors = [];
        if (!isset(\$schema['@type'])) \$errors[] = 'Missing @type';
        if (\$schema['@type'] !== 'ImageObject') \$errors[] = 'Wrong @type';
        if (!isset(\$schema['acquireLicensePage'])) \$errors[] = 'Missing acquireLicensePage';
        if (!isset(\$schema['copyrightNotice'])) \$errors[] = 'Missing copyrightNotice';
        if (!isset(\$schema['creditText'])) \$errors[] = 'Missing creditText';
        if (!isset(\$schema['license'])) \$errors[] = 'Missing license';

        if (empty(\$errors)) {
          echo '  ✅ OK: All required fields present (including IPTC metadata)' . PHP_EOL;
        } else {
          echo '  ❌ ERRORS: ' . implode(', ', \$errors) . PHP_EOL;
        }
      }
    }
  "
done

echo ""
echo "========================================="
echo "Validation Complete"
echo "========================================="
