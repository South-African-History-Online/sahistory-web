# Database Fixes

This Drupal module addresses specific database schema issues in the site.

## Current Fixes

### Watchdog Location Column

Fixes a database error that occurs when accessing URLs with very long filenames:

```
SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'location' at row 1
```

The issue occurs because the `location` column in the `watchdog` table is too small to store long URLs. This module 
implements update hooks that alter the database schema to change the `location` column from its current type
to `TEXT` type, which can store much longer strings.

## Installation and Usage

1. Install the module using the admin interface or Drush: `drush en db_fixes`
2. Run database updates: `drush updatedb` or via the admin interface at `/update.php`
3. The module doesn't need to remain enabled after the database update has run

## Technical Implementation

The module implements the following:

### Installation Hook

- `db_fixes_install()`: When the module is first installed, this hook:
  - Directly applies the watchdog location column fix during installation
  - Includes error handling in case the table doesn't exist yet
  - Allows update hooks to be properly detected and run on existing installations

### Update Hooks

- `db_fixes_update_8001`: For Drupal 8 and 9 sites
- `db_fixes_update_11001`: For Drupal 11 sites

Both update hooks:

1. Get the database schema
2. Change the `location` column in the `watchdog` table to use TEXT datatype
3. Return a success message when complete

Whether the module is freshly installed or updated from a previous version, the watchdog table will be properly modified to handle long URLs.