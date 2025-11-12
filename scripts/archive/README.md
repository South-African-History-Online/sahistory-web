# Archived Migration Scripts

This directory contains one-time migration scripts used during the SAHO Shop Phase 2 implementation (November 2025).

**Status:** Historical reference only - these scripts have already been executed and are not needed for ongoing operations.

## Scripts

### create_publication_fields.php
- **Purpose:** Created 13 custom fields and 2 taxonomies for publication products
- **Date:** November 10, 2025
- **Status:** ✅ Executed - fields created
- **Result:** All fields in `config/shop/field.*.yml`

### fix_missing_data.php
- **Purpose:** Fixed missing body text and taxonomy categories after initial migration
- **Date:** November 10, 2025
- **Status:** ✅ Executed - data fixed
- **Result:** 31/33 products have body text, 32/33 have categories

### generate_image_mapping.php
- **Purpose:** Generated CSV mapping of SKUs to image filenames
- **Date:** November 10, 2025
- **Status:** ✅ Executed - mapping created
- **Result:** `product_image_mapping.csv` (since deleted)

### import_product_images.php
- **Purpose:** Imported product cover images from covers/ directory
- **Date:** November 10, 2025
- **Status:** ✅ Executed - 20 images imported
- **Result:** 20/33 products have cover images

### match_and_rename_images.php
- **Purpose:** Matched existing images in files directory to products by name
- **Date:** November 10, 2025
- **Status:** ✅ Executed - images matched
- **Result:** Found 23 cover images

### check_image_readiness.sh
- **Purpose:** Validated that images and products were ready for import
- **Date:** November 10, 2025
- **Status:** ✅ Executed - validation passed

## Migration Summary

**Products Migrated:** 33 of 35 published
**Fields Created:** 13 custom fields + 2 taxonomies
**Images Imported:** 20 of 33 (61% coverage)
**Data Completeness:** 91%

See `SHOP-SETUP-GUIDE.md` in project root for complete documentation.

---

**Note:** These scripts are kept for historical reference but are not maintained. Do not execute them again as they may interfere with existing data.
