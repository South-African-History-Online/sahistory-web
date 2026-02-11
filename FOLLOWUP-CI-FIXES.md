# Follow-Up: Fix Pre-Existing Test & Code Quality Issues

**Context:** PR #268 enabled PHPUnit tests and improved CI/CD. The tests are now running and exposing pre-existing bugs and code quality issues that were previously hidden.

## Priority 1: Fix Unit Test Failures (11 tests)

### Issue 1: ContentExtractorServiceTest (6 failures)
**File:** `webroot/modules/custom/saho_utils/tests/src/Unit/Service/ContentExtractorServiceTest.php`

Tests returning empty strings instead of expected content:
- `testExtractTeaserWithCustomLength` - Expected 'Short text', got ''
- `testExtractTeaserSpecificField` - Expected 'Custom field content', got ''
- `testExtractSummaryFromSummaryField` - Expected 'This is the summary', got ''
- `testExtractSummaryFallbackToBody` - Expected 'Body content', got ''
- `testExtractBodyTextWithStripping` - Expected 'HTML content here', got ''
- `testExtractBodyTextWithoutStripping` - Expected HTML, got ''

**Root Cause:** Mock objects not configured correctly, or service logic has bugs.

**Fix Strategy:**
1. Review mock setup in test - ensure field getValue() returns expected data
2. Debug actual ContentExtractorService logic
3. Fix either test mocks or service implementation

### Issue 2: EntityItemBuilderServiceTest (4 failures)
**File:** `webroot/modules/custom/saho_utils/tests/src/Unit/Service/EntityItemBuilderServiceTest.php`

Tests failing on image URLs and array assertions:
- `testBuildItemWithImage` - Expected 'https://example.com/image.jpg', got null
- `testBuildItemWithTeaser` - Expected 'https://example.com/teaser.jpg', got null
- `testBuildMultipleItemsWithImage` - Expected 'https://example.com/img1.jpg', got null
- `testBuildCustomItem` - Expected array size 2, got 0

**Root Cause:** Image URL extraction logic not working, or test mocks incomplete.

**Fix Strategy:**
1. Check how image URLs are extracted from entity references
2. Verify file entity mocking in tests
3. Fix URL generation logic or test setup

### Issue 3: TermTrackerTest (1 failure)
**File:** `webroot/modules/custom/saho_statistics/tests/src/Unit/Service/TermTrackerTest.php`

Mock expectation failure:
- `testGetMostReadContentAllTime` - `Select::fields()` called more than once (expected once)

**Root Cause:** Query builder mock too strict or service logic changed.

**Fix Strategy:**
1. Review TermTracker::getMostReadContentAllTime() implementation
2. Check if fields() is called multiple times legitimately
3. Update mock expectations or fix service logic

---

## Priority 2: Fix PHPCS Violations (14 errors, 18 warnings)

### Quick Win: Auto-fixable (8 errors)
Run phpcbf to automatically fix:
```bash
./vendor/bin/phpcbf --standard=Drupal webroot/modules/custom
```

**Files affected:**
- `africa_regions/africa_regions.module` (6 auto-fixable)
- `saho_performance/saho_performance.module` (2 auto-fixable)

### Manual Fixes Needed (6 errors)

**File:** `webroot/modules/custom/saho_utils/africa_regions/africa_regions.module`
- Line 59: Split long array declaration across multiple lines (136 chars → 120 max)

**File:** `webroot/modules/custom/saho_utils/saho_example_block/saho_example_block.module`
- Line 3: Add short description to doc comment

**File:** `webroot/modules/custom/saho_performance/saho_performance.module`
- Lines 622, 626, 635, 639: Fix comment indentation (expected 1 space)

### Warnings (Optional - 18 total)
Line length warnings (80 char limit). Can be ignored or fixed manually:
- `saho_utils.install` (1 warning)
- `saho_performance.module` (10 warnings)
- `saho_tools.module` (5 warnings)
- `saho_cleanup.module` (1 warning)
- `saho_statistics.module` (2 warnings)
- `db_fixes.install` (2 warnings)

---

## Priority 3 (Optional): Shop Theme PHPCS

**File:** `webroot/themes/custom/saho_shop/theme-settings.php`

**Status:** 129 errors, 20 warnings (kept `|| true` in shop-ci.yml for now)

**Fix Strategy:**
1. Create separate branch: `fix/shop-theme-phpcs`
2. Run: `./vendor/bin/phpcbf --standard=Drupal webroot/themes/custom/saho_shop/`
3. Manually fix remaining issues
4. Create separate PR (large scope)

---

## Recommended Workflow

### Step 1: Fix Unit Tests
```bash
git checkout main
git pull origin main
git checkout -b fix/unit-test-failures

# Fix tests one by one
# Run tests locally: ddev exec -d /var/www/html/webroot/modules/custom phpunit

git add webroot/modules/custom/*/tests
git commit -m "Fix unit test failures in ContentExtractor, EntityItemBuilder, TermTracker"
git push origin fix/unit-test-failures
gh pr create --base main --head fix/unit-test-failures
```

### Step 2: Fix PHPCS Issues
```bash
git checkout main
git pull origin main
git checkout -b fix/phpcs-violations

# Auto-fix
./vendor/bin/phpcbf --standard=Drupal webroot/modules/custom

# Manual fixes for remaining errors
# nano webroot/modules/custom/saho_utils/africa_regions/africa_regions.module
# (fix line 59 array)
# nano webroot/modules/custom/saho_utils/saho_example_block/saho_example_block.module
# (add doc comment line 3)
# nano webroot/modules/custom/saho_performance/saho_performance.module
# (fix comment indentation lines 622, 626, 635, 639)

# Verify
./vendor/bin/phpcs --standard=Drupal webroot/modules/custom

git add webroot/modules/custom
git commit -m "Fix PHPCS violations: comment punctuation, indentation, doc comments"
git push origin fix/phpcs-violations
gh pr create --base main --head fix/phpcs-violations
```

### Step 3 (Optional): Shop Theme
```bash
git checkout main
git pull origin main
git checkout -b fix/shop-theme-phpcs

./vendor/bin/phpcbf --standard=Drupal webroot/themes/custom/saho_shop/
# Fix remaining issues manually

./vendor/bin/phpcs --standard=Drupal webroot/themes/custom/saho_shop/

git add webroot/themes/custom/saho_shop
git commit -m "Fix PHPCS violations in shop theme (129 errors)"
git push origin fix/shop-theme-phpcs
gh pr create --base main --head fix/shop-theme-phpcs
```

---

## Expected Timeline

- **Unit test fixes:** 2-4 hours (debugging + fixes)
- **PHPCS fixes:** 30-60 minutes (mostly automated)
- **Shop theme:** 1-2 hours (optional, can defer)

**Total:** ~3-5 hours of focused work across 2 separate PRs.

---

## Success Criteria

After all fixes:
- ✅ All 46 PHPUnit tests passing (0 failures)
- ✅ PHPCS reports 0 errors in custom modules
- ✅ Code coverage reports generated cleanly
- ✅ CI pipeline fully green on all PRs
- ✅ No false positives or noise in CI logs

---

## Notes

- These are **pre-existing issues** exposed by enabling the test infrastructure
- Fixing them **improves code quality** and prevents regressions
- The CI/CD improvements in PR #268 are **working as intended** by catching these issues
- No rush - can be fixed incrementally over next sprint
