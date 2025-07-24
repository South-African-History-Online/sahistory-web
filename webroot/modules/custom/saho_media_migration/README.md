# SAHO Media Migration

Production-ready Drupal module for migrating file entities to media entities in the SAHO website.

## Features

- **Batch Processing**: Efficient handling of large file datasets
- **Web Interface**: User-friendly admin form for non-technical users
- **Drush Commands**: Powerful command-line tools for developers
- **Progress Tracking**: Real-time progress with ETA calculations
- **Validation**: Comprehensive integrity checking
- **CSV Support**: Import/export functionality for data analysis
- **Duplicate Prevention**: Automatically skips files with existing media entities

## Requirements

- Drupal 9.5+, 10.x, or 11.x
- PHP 8.0+
- Media module enabled
- File module enabled
- Private files directory configured

## Installation

1. **Place module files** in `webroot/modules/custom/saho_media_migration/`

2. **Enable the module**:
   ```bash
   drush en saho_media_migration -y
   ```

3. **Clear caches**:
   ```bash
   drush cr
   ```

## Usage

### Web Interface

Visit **Administration** → **Configuration** → **Media** → **SAHO Media Migration**

- View migration status and progress
- Generate CSV mappings
- Start migrations with one click
- Validate migration integrity

### Drush Commands

#### Check Status
```bash
drush saho:status
drush sms  # Short alias
```

#### Start Migration
```bash
# Migrate up to 1000 files
drush saho:migrate --limit=1000
drush smig --limit=1000  # Short alias

# Migrate all files needing migration
drush saho:migrate --limit=0
```

#### Generate CSV Mapping
```bash
drush saho:generate-csv
drush sgc  # Short alias
# Alternative command
drush saho:csv
drush scsv  # Short alias for alternative
```

#### Validate Migration
```bash
drush saho:validate
drush sval  # Short alias
```

#### Import from CSV
```bash
drush saho:import-csv /path/to/file.csv
drush simp /path/to/file.csv  # Short alias
```

#### Test Commands
```bash
drush saho:test
drush st  # Short alias
```

> **Note:** This module contains two command classes that implement similar functionality. 
> For full-featured commands, use the ones documented above. If you encounter any issues, 
> try using the alternative commands where available.

## Migration Process

The module automatically:

1. **Identifies files** without media entities
2. **Prioritizes** most-used files first
3. **Creates media entities** with appropriate bundles:
   - Images → `image` bundle
   - Audio → `audio` bundle  
   - Video → `video` bundle
   - Documents → `file` bundle
4. **Links files** to media entities via source fields
5. **Validates** created entities

## File-to-Media Mapping

| File Type | Media Bundle | Source Field |
|-----------|--------------|--------------|
| `image/*` | `image` | `field_media_image` |
| `audio/*` | `audio` | `field_media_audio_file` |
| `video/*` | `video` | `field_media_video_file` |
| `application/*`, `text/*` | `file` | `field_media_file` |

## Production Recommendations

### Before Migration
- **Backup database** before running migration
- **Test on staging** environment first
- **Check disk space** for CSV files and logs
- **Verify file permissions** on files directory

### During Migration
- **Monitor progress** via web interface or Drush output
- **Check logs** for any warnings or errors
- **Start small** with `--limit=100` for initial tests

### After Migration
- **Validate results** using `drush saho:validate`
- **Clear caches** with `drush cr`
- **Check media library** at `/admin/content/media`
- **Verify content** displays correctly

## Performance Guidelines

### Small Sites (< 1,000 files)
```bash
drush saho:migrate
```

### Medium Sites (1,000 - 10,000 files)
```bash
drush saho:migrate --limit=1000
# Run multiple times until complete
```

### Large Sites (> 10,000 files)
```bash
drush saho:migrate --limit=500
# Process in smaller chunks
# Monitor server resources
```

## File Structure

```
saho_media_migration/
├── saho_media_migration.info.yml
├── saho_media_migration.services.yml
├── saho_media_migration.routing.yml
├── saho_media_migration.module
├── saho_media_migration.libraries.yml
├── README.md
├── css/
│   └── admin.css
└── src/
    ├── Service/
    │   └── MediaMigrationService.php
    ├── Commands/
    │   └── MediaMigrationCommands.php
    ├── Batch/
    │   └── MediaMigrationBatch.php
    └── Form/
        └── MediaMigrationForm.php
```

## Troubleshooting

### Commands Not Found
```bash
# Ensure module is enabled
drush pml | grep saho_media_migration

# Clear caches
drush cr

# List available commands
drush list | grep saho
```

### Migration Fails
1. Check Drupal logs: `drush watchdog:show`
2. Verify file permissions
3. Check available disk space
4. Reduce batch size with `--limit=100`

### Memory Issues
- Reduce `--limit` parameter
- Check PHP memory limit
- Process during off-peak hours

### Performance Issues
- Monitor server resources
- Use smaller batch sizes
- Process incrementally

## Support

For issues:
1. Check Drupal logs: `drush watchdog:show`
2. Validate migration: `drush saho:validate`  
3. Review file permissions and disk space

## License

GPL v2 or later