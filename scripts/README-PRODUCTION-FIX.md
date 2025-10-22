# Production Fix Instructions

## Quick Fix - Upload and Run Script

1. **Upload the script to production:**
   ```bash
   scp scripts/PRODUCTION-RUN-THIS.sh sahistrg878@cp1.sahistory.org.za:/home/sahistrg878/
   ```

2. **SSH to production:**
   ```bash
   ssh sahistrg878@cp1.sahistory.org.za
   ```

3. **Run the script:**
   ```bash
   bash PRODUCTION-RUN-THIS.sh
   ```

4. **Start reindexing:**
   ```bash
   cd /home/sahistrg878/public_html/sahistory.org.za
   vendor/bin/drush search-api:index
   ```

## What This Does

Fixes all NULL image title fields that cause this error:
```
Call to a member function getCacheTags() on null
```

Covers ALL 17 image field types across 30,000+ records.

## Time Required

- Script runs in 2-3 minutes
- Reindexing takes 10-15 minutes for 31,400 items
- No downtime required

## Verification

After reindexing completes, check logs:
```bash
vendor/bin/drush watchdog:show --type=search_api --count=20
```

Should see no more getCacheTags() errors.
