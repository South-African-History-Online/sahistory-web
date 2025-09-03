# Safe Image URL Conversion

This script safely converts absolute image URLs to relative paths while preventing broken links.

## Problem

After migrations or upgrades, CKEditor content may contain absolute URLs like:
- ❌ `http://www.sahistory.org.za/sites/default/files/article/file%20attachment/image.png`

These should be relative paths:
- ✅ `/sites/default/files/article/file attachment/image.png`

## Solution

The `fix_image_urls_safe.php` script:

1. **Verifies file existence** before making changes
2. **Only updates URLs** where files actually exist on disk  
3. **Fixes URL encoding** (spaces, brackets, special characters)
4. **Prevents broken links** by leaving missing files unchanged
5. **Updates both current and revision tables**

## Usage

```bash
# Run the safe conversion script
ddev exec php scripts/fix_image_urls_safe.php

# Follow the prompts to confirm changes
```

## What It Does

### File Verification Process
1. Scans all `node__body` content for absolute URLs
2. Extracts file paths from URLs
3. Checks if files exist at `webroot/sites/default/files/...`
4. Only converts URLs where files are verified to exist

### URL Transformations
- `http://www.sahistory.org.za/sites/default/files/image.jpg` → `/sites/default/files/image.jpg`
- `file%20attachment` → `file attachment` (decode URL encoding)
- `%5B` → `[`, `%5D` → `]`, `%2C` → `,` (brackets, commas)

### Safety Features
- **Transactional updates** - rolls back on any error
- **File existence checking** - no broken links created  
- **Interactive confirmation** - review before proceeding
- **Detailed logging** - shows exactly what's happening
- **Statistics reporting** - summary of changes made

## Output Example

```
=== Safe Image URL Conversion Script ===
Converting absolute URLs to relative paths (only for existing files)

📋 Finding nodes with absolute URLs...
Found 1467 nodes with absolute URLs

🔍 Checking file existence for each URL...
✅ Entity 7855: File exists - sites/default/files/biko_house.jpg
❌ Entity 8006: File missing - sites/default/files/missing_image.png

=== ANALYSIS RESULTS ===
📊 Total nodes analyzed: 1467
📊 Total URLs found: 2148  
✅ Files that exist: 1876
❌ Missing files: 272
🔄 Nodes to update: 1195
⏸️  Nodes to skip (missing files): 272

❓ Proceed with updating 1195 nodes? (y/N): y

✅ Successfully updated 1195 nodes
⚠️  Skipped 272 nodes with missing files  
🛡️  No broken links were created
```

## When to Use

- After Drupal migrations or upgrades
- When CKEditor content has absolute URLs
- Before deploying to production
- When fixing legacy content issues

## Safety Guarantees

- ✅ No broken links will be created
- ✅ Only existing files get converted
- ✅ Transactional updates (all or nothing)
- ✅ Missing files left unchanged for safety
- ✅ Full rollback on any errors

## Files Updated

- `node__body` - Current content
- `node_revision__body` - Revision history

Both tables are kept in sync to maintain consistency.