# Fix File Uploads Path Issue

## Problem

Found 109 nodes with broken file references in their body content. The HTML contains paths with a trailing space:
```html
/sites/default/files/file uploads /filename.jpg
```

This references a directory `file uploads ` (with trailing space) that only contains 5 .txt files.

## Root Cause

Three similar directories exist:
1. `file upload` - different directory
2. `file uploads` - correct directory with 90 files
3. `file uploads ` - directory with trailing space (only 5 .txt files)

The HTML is incorrectly referencing the trailing-space version.

## Affected Nodes

- **70 archive nodes** - Files actually located in `/sites/default/files/archive-files/`
- **35 place nodes** - Files actually located in `/sites/default/files/file uploads/`
- **4 article nodes** - Files actually located in `/sites/default/files/file uploads/`
- **Total: 109 nodes**

## Solution

The bash script `fix_file_uploads_paths.sh` performs the following updates:

1. **Archive nodes**: Replace `/sites/default/files/file uploads /` → `/sites/default/files/archive-files/`
2. **Place nodes**: Replace `/sites/default/files/file uploads /` → `/sites/default/files/file uploads/`
3. **Article nodes**: Replace `/sites/default/files/file uploads /` → `/sites/default/files/file uploads/`

Both current and revision tables are updated to keep history in sync.

The script includes:
- Pre-flight checks to show affected nodes
- Optional backup creation
- Interactive confirmation prompts
- Post-fix verification
- Cache clearing

## Verification

Tested sample files:
- Archive files (node 124417): `fig._3.1a.jpg`, `sound_icon_s.jpg` → exist in `archive-files/`
- Place files: `clifton and camps bay beach.png`, `bo-kaap.jpg` → exist in `file uploads/`

## How to Run

The script automatically detects your environment:
- **Local (DDEV)**: Uses `ddev drush`
- **Staging/Production**: Uses `./vendor/bin/drush`

```bash
# Run the interactive script
./scripts/fix_file_uploads_paths.sh
```

The script will:
1. Detect environment and show drush command being used
2. Show count of affected nodes by content type
3. Ask if you want to create a backup
4. Ask for confirmation before making changes
5. Execute the fixes
6. Verify the results
7. Clear the cache
8. Provide test URLs and environment-specific rollback instructions

### Manual Verification (Optional)

```bash
# Check what will be affected
ddev drush sqlq "SELECT entity_id, bundle FROM node__body WHERE body_value LIKE '%/sites/default/files/file uploads /%'"

# After running, verify specific nodes
ddev drush sqlq "SELECT SUBSTRING(body_value, LOCATE('fig._3.1a.jpg', body_value) - 50, 100) FROM node__body WHERE entity_id = 124417"
```

## Example Nodes to Test

- Node 124417 (archive): Kora: A Lost Khoisan Language Chapter 3
- Node 65696 (place): Should now display images correctly
- Node 65906 (place): Lagoon Bridge Milnerton
