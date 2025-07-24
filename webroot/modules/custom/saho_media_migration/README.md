# SAHO Media Migration

This module provides tools for migrating file entity references to media entities in the SAHO website. It helps address issues with media entities that were not correctly migrated from Drupal 7 to Drupal 8/9.

## Features

- Generates CSV files mapping file entities to their usages
- Migrates file entities to media entities
- Updates file references in content
- Validates media entities and their references
- Preserves existing media relationships
- Prevents duplicate media entities
- Generates test content using real SAHO files
- Creates before/after migration demonstrations

## Requirements

- Drupal 9.x or higher
- Drush 10.x or higher
- Media module enabled
- File entity module enabled
- Private files directory configured (for CSV storage)
- Access to SAHO file repository for test content

## Installation

1. Install the module using Composer:
   ```bash
   composer require custom/saho_media_migration
   ```

2. Enable the module:
   ```bash
   drush en saho_media_migration
   ```

## Test Content Generation

### Generate Test Content with Real SAHO Files

```bash
# Generate 100 nodes with real biographical pictures (broken state)
drush saho:generate-real-test-content 100 --bio-pics-only --use-broken

# Generate 100 nodes with real biographical pictures (fixed state)
drush saho:generate-real-test-content 100 --bio-pics-only --use-fixed

# Generate 50 nodes with real documents (broken state)
drush saho:generate-real-test-content 50 --documents-only --use-broken
```

### Create Migration Demo

```bash
# Create demo with biographical pictures
drush saho:create-real-migration-demo --focus-type=bio-pics

# Create demo with documents (25 nodes per state)
drush saho:create-real-migration-demo --focus-type=documents --nodes-per-state=25
```

### Analyze Real Files

```bash
# Analyze biographical pictures and their usage
drush saho:analyze-real-files --type=bio-pics --show-usage

# Show 20 document files
drush saho:analyze-real-files --type=documents --limit=20
```

## Migration Workflow

### Step 1: Generate CSV File

First, generate a CSV file mapping all file entities and their usages:

```bash
drush saho:generate-csv
# or use the alias
drush sgc
```

This command:
- Creates a CSV file in `private://migration_csv/`
- Maps file IDs to their entity references
- Identifies existing media entities
- Records usage information

Latest CSV file: `private://migration_csv/media_migration_2025-07-23_14-35-27.csv`

CSV Format:
```csv
file_id,filename,entity_type,entity_id,field_name,existing_media_id
1,document.pdf,node,123,field_media_document,456
```

### Step 2: Validate Current State

Before migration, validate the current state:

```bash
drush saho:validate-media
# or use the alias
drush svm
```

This will show:
- Orphaned media entities
- Missing file references
- Broken media relationships

### Step 3: Perform Migration

Run the migration command:

```bash
drush saho:migrate-files
# or use the alias
drush smf
```

The migration process:
1. Reads the latest CSV file (media_migration_2025-07-23_14-35-27.csv)
2. Checks for existing media entities to prevent duplicates
3. Creates new media entities only for files without existing media
4. Preserves existing references and relationships
5. Updates node references to point to media entities

### Step 4: Update References

Update content references:

```bash
drush saho:update-references
# or use the alias
drush sur
```

This will:
1. Update all content types using the migrated files
2. Preserve existing relationships
3. Fix broken media references

### Step 5: Final Validation

Validate the migration results:

```bash
drush saho:validate-media
```

Compare the validation results with the initial state to verify the migration success.

## Duplicate Prevention

The module prevents duplicate media entities by:
1. Checking for existing media entities before creation
2. Using the CSV file to track relationships
3. Preserving existing media references
4. Skipping files that already have media entities

## Reference Preservation

Existing references are preserved through:
1. Pre-migration mapping in CSV
2. Checking for existing media entities
3. Maintaining original file relationships
4. Updating only necessary references

## Troubleshooting

### CSV Generation Issues

If CSV generation fails:
1. Check private files directory permissions
2. Ensure sufficient disk space
3. Verify database access permissions

### Migration Issues

If migration encounters problems:
1. Check the Drupal logs:
   ```bash
   drush watchdog:show
   ```
2. Verify file permissions
3. Check database access
4. Review CSV file for mapping issues

### Duplicate Prevention

If duplicates occur:
1. Run validation:
   ```bash
   drush svm
   ```
2. Check CSV file for mapping issues
3. Review file usage table

### Reference Issues

If references are broken:
1. Run validation
2. Check original file relationships
3. Review node field configurations
4. Verify media entity field settings

## Best Practices

1. Always generate a new CSV before migration
2. Run validation before and after migration
3. Back up the database before migration
4. Monitor logs during migration
5. Test on a staging environment first

## Testing with Real Data

1. Prepare the environment:
   ```bash
   # Clear Drupal's cache
   drush cr

   # Validate current state
   drush saho:validate-media
   ```

2. Generate test content:
   ```bash
   # Create demo with bio pics (25 nodes per state)
   drush saho:create-real-migration-demo --focus-type=bio-pics --nodes-per-state=25

   # Create demo with documents
   drush saho:create-real-migration-demo --focus-type=documents --nodes-per-state=25
   ```

3. Analyze the files:
   ```bash
   # Check bio pics
   drush saho:analyze-real-files --type=bio-pics --show-usage

   # Check documents
   drush saho:analyze-real-files --type=documents --show-usage
   ```

4. Run the migration:
   ```bash
   # Generate CSV mapping
   drush saho:generate-csv

   # Migrate files to media entities
   drush saho:migrate-files

   # Update references
   drush saho:update-references

   # Validate results
   drush saho:validate-media
   ```

5. Verify results:
   - Visit /admin/content
   - Filter by "BROKEN Real SAHO" to see pre-migration state
   - Filter by "FIXED Real SAHO" to see post-migration state
   - Compare media handling between states

## Contributing

Submit issues and pull requests to the project repository. Follow Drupal coding standards and include tests for new functionality.

## License

This module is licensed under GPL v2 or later.