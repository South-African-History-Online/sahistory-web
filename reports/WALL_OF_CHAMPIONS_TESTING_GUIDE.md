# Wall of Champions - Testing Guide
**Quick Reference for Testing the Wall of Champions Feature**

## Quick Start Testing

### 1. Access the Wall of Champions Page
```
URL: https://[your-site].ddev.site/champions
Expected: Empty state message (no champions yet)
```

### 2. Create a Test Champion User

**Option A: Create New User**
```
1. Go to: /admin/people/create
2. Fill in:
   - Username: test_champion
   - Email: test@example.com
   - Password: [secure password]
   - First name: Test
   - Last name: Champion
3. Check: Active
4. Save
```

**Option B: Use Existing User**
- Use any existing user account
- Must have first_name and last_name fields populated

### 3. Create Champion Subscription for User

**Via Drupal Admin:**
```
1. Go to: /admin/commerce/subscriptions/add/product_variation
2. Fill in:
   - Title: Test Champion Subscription
   - Store: [Your Store]
   - Billing schedule: [Monthly or Annual]
   - Customer: test_champion (or your test user)
   - State: Active
   - Purchased entity: [Champion Membership variation]
3. Save
```

**Via Database (Quick Test Method):**
```sql
-- Get user ID
SELECT uid FROM users_field_data WHERE name = 'test_champion';

-- Get champion variation ID
SELECT variation_id FROM commerce_product_variation WHERE type = 'champion_membership' LIMIT 1;

-- Create subscription (use actual IDs from above)
INSERT INTO commerce_subscription (type, uid, state, purchased_entity, starts, ends)
VALUES ('product_variation', [USER_ID], 'active', [VARIATION_ID], UNIX_TIMESTAMP(), NULL);
```

### 4. Opt User Into Wall of Champions

```
1. Log in as test user (or edit as admin)
2. Go to: /user/[uid]/edit
3. Expand: "Wall of Champions" section
4. Check: "Display on Wall of Champions"
5. Enter testimonial (optional): "I support SAHO because South African history matters!"
6. Save
```

### 5. Verify Display

**Full Page:**
```
URL: /champions
Expected:
- User card appears
- Shows "Test Champion" (or username)
- Shows testimonial if provided
- Shows "Champion since [Month Year]"
- Card has hover effect
```

**Block (If Placed):**
```
1. Go to: /admin/structure/block
2. Add block: "Wall of Champions Block"
3. Region: Content (or desired region)
4. Configure: Display count = 6
5. Save
6. View homepage or wherever block is placed
Expected:
- Champions display in grid
- "View All Champions" button shows
- Total count displayed
```

## Test Scenarios

### ✅ Positive Tests

| Test | Steps | Expected Result |
|------|-------|----------------|
| **Opt-in Works** | Check opt-in box, save | User appears on /champions |
| **Testimonial Displays** | Add testimonial, save | Quote shows on wall |
| **Name Display** | User with first+last name | Shows "FirstName LastName" |
| **Fallback Name** | User without names | Shows username |
| **Multiple Champions** | Create 3+ opted-in champions | All display in grid |
| **Hover Effect** | Hover over card | Card lifts with shadow |
| **Mobile View** | Resize to mobile | Cards stack vertically |
| **Block Count** | Set block to 3 champions | Only 3 display in block |
| **View All Link** | Click "View All" in block | Redirects to /champions |

### ✅ Privacy Tests

| Test | Steps | Expected Result |
|------|-------|----------------|
| **Opt-out Default** | Create new user | Opt-in unchecked by default |
| **Hide Champion** | Uncheck opt-in, save | User disappears immediately |
| **Re-opt-in** | Check box again | User reappears |
| **No Email Shown** | Check displayed info | Email address not visible |
| **Only Opted-in Show** | Have 5 users, 2 opted-in | Only 2 appear on wall |

### ✅ Subscription Tests

| Test | Steps | Expected Result |
|------|-------|----------------|
| **Active Only** | Subscription state = active | User appears |
| **Inactive Hidden** | Cancel subscription | User disappears |
| **Reactivation** | Reactivate subscription | User reappears |
| **Multiple Subs** | User has 2 active subs | Shows once (DISTINCT) |
| **Wrong Type** | Non-champion subscription | User doesn't appear |

### ✅ Security Tests

| Test | Steps | Expected Result |
|------|-------|----------------|
| **XSS Attempt** | Testimonial: `<script>alert('xss')</script>` | HTML stripped, shows as text |
| **HTML Tags** | Testimonial: `<b>Bold text</b>` | Tags removed |
| **Long Input** | 300 character testimonial | Truncated to 250 + "..." |
| **SQL Injection** | Testimonial: `'; DROP TABLE users;--` | Safely escaped |
| **Special Chars** | Testimonial: `"quotes" & <symbols>` | Properly escaped |

### ✅ Empty State Tests

| Test | Steps | Expected Result |
|------|-------|----------------|
| **No Champions** | 0 opted-in champions | "No Champions Yet" message |
| **Empty Block** | Block with 0 champions | Empty state with join CTA |
| **CTA Link** | Click "Become a Champion" | Links to /product/champion-subscription |

### ✅ Performance Tests

| Test | Steps | Expected Result |
|------|-------|----------------|
| **Page Load** | Visit /champions | Loads in <2 seconds |
| **Cache Hit** | Visit twice | Second load faster (cached) |
| **Many Champions** | Create 50+ champions | No performance degradation |
| **Block Load** | Page with block | Doesn't slow page load |

## Manual Query Testing

### Check Champions in Database
```bash
ddev drush sqlq "
SELECT DISTINCT
  u.uid,
  u.name,
  fn.field_first_name_value AS first_name,
  ln.field_last_name_value AS last_name,
  opt.field_champion_wall_opt_in_value AS opted_in,
  test.field_champion_testimonial_value AS testimonial,
  cs.state AS sub_state
FROM commerce_subscription cs
JOIN commerce_product_variation cpv ON cs.purchased_entity = cpv.variation_id
JOIN users_field_data u ON cs.uid = u.uid
LEFT JOIN user__field_champion_wall_opt_in opt ON u.uid = opt.entity_id
LEFT JOIN user__field_first_name fn ON u.uid = fn.entity_id
LEFT JOIN user__field_last_name ln ON u.uid = ln.entity_id
LEFT JOIN user__field_champion_testimonial test ON u.uid = test.entity_id
WHERE cpv.type = 'champion_membership'
AND u.status = 1
ORDER BY cs.starts DESC
"
```

### Check Route
```bash
ddev drush route:overview | grep champions
```

### Check Block Availability
```bash
ddev drush pm:list --filter="wall_of_champions"
```

### Clear Cache
```bash
ddev drush cr
```

## Common Issues & Fixes

### Issue: User not appearing on wall

**Checklist:**
- [ ] Subscription state is 'active'?
- [ ] Subscription type is 'champion_membership'?
- [ ] User status is 1 (active)?
- [ ] Opt-in field checked?
- [ ] Cache cleared?

**Fix:**
```bash
# Check subscription
ddev drush sqlq "SELECT state, type FROM commerce_subscription WHERE uid = [USER_ID]"

# Check opt-in
ddev drush sqlq "SELECT field_champion_wall_opt_in_value FROM user__field_champion_wall_opt_in WHERE entity_id = [USER_ID]"

# Clear cache
ddev drush cr
```

### Issue: Route not found (404)

**Fix:**
```bash
ddev drush cr
ddev drush route:debug wall_of_champions.page
```

### Issue: Block not showing

**Checklist:**
- [ ] Block placed in region?
- [ ] Block visibility conditions met?
- [ ] Region exists in theme?
- [ ] Cache cleared?

**Fix:**
```bash
# Check block placement
ddev drush block:list | grep "wall_of_champions"

# Clear cache
ddev drush cr
```

### Issue: Styling not applied

**Fix:**
```bash
cd webroot/themes/custom/saho_shop
npm run build
ddev drush cr
```

### Issue: Form fields not showing

**Fix:**
```bash
# Re-import configuration
ddev drush cim -y
ddev drush cr

# Check field exists
ddev drush field:list user | grep champion
```

## Browser Testing Matrix

| Browser | Version | Status |
|---------|---------|--------|
| Chrome | 120+ | ✅ Tested |
| Firefox | 120+ | ⏳ To Test |
| Safari | 16+ | ⏳ To Test |
| Edge | 120+ | ⏳ To Test |
| Mobile Safari | iOS 15+ | ⏳ To Test |
| Chrome Mobile | Android 12+ | ⏳ To Test |

## Accessibility Testing

### Screen Reader Test
- [ ] Navigate to /champions with screen reader
- [ ] All champion names announced
- [ ] Testimonials readable
- [ ] "View All" link has proper label

### Keyboard Navigation
- [ ] Tab through champion cards
- [ ] Focus visible on all interactive elements
- [ ] Can navigate without mouse

### Color Contrast
- [ ] Text readable on all backgrounds
- [ ] Hover states have sufficient contrast
- [ ] Links distinguishable from body text

## Performance Benchmarks

**Target Metrics:**
- Initial page load: <2 seconds
- Time to interactive: <3 seconds
- Block render: <100ms
- Database query: <50ms
- Cache hit ratio: >90%

**Monitoring:**
```bash
# Check query performance
ddev drush sqlq "SHOW PROFILES"

# Check cache effectiveness
ddev drush cache:get wall_of_champions:champions
```

## Sign-off Checklist

Before considering testing complete:

- [ ] All positive tests pass
- [ ] All privacy tests pass
- [ ] All subscription tests pass
- [ ] All security tests pass
- [ ] Empty states display correctly
- [ ] Performance acceptable
- [ ] Mobile responsive works
- [ ] At least 2 browsers tested
- [ ] Accessibility basics verified
- [ ] No console errors
- [ ] No PHP errors in logs
- [ ] Documentation accurate

## Reporting Issues

When reporting issues, include:

1. **Environment:** Browser, device, URL
2. **User State:** Logged in/out, roles
3. **Steps to Reproduce:** Exact actions taken
4. **Expected:** What should happen
5. **Actual:** What actually happened
6. **Screenshots:** If applicable
7. **Console Errors:** Browser console logs
8. **PHP Errors:** Check with `ddev logs`

## Quick Test Script

Copy/paste this into your terminal for rapid testing:

```bash
#!/bin/bash
echo "🧪 Wall of Champions Quick Test"
echo "================================"

# Check module enabled
echo "1. Checking module status..."
ddev drush pm:list --filter="wall_of_champions" | grep Enabled && echo "✅ Module enabled" || echo "❌ Module not enabled"

# Check route
echo "2. Checking route..."
ddev drush route:overview | grep "wall_of_champions.page" && echo "✅ Route exists" || echo "❌ Route not found"

# Check fields
echo "3. Checking fields..."
ddev drush field:list user | grep "field_champion_wall_opt_in" && echo "✅ Opt-in field exists" || echo "❌ Opt-in field missing"
ddev drush field:list user | grep "field_champion_testimonial" && echo "✅ Testimonial field exists" || echo "❌ Testimonial field missing"

# Check theme compiled
echo "4. Checking theme..."
test -f webroot/themes/custom/saho_shop/css/pages/_wall-of-champions.css && echo "✅ Styles compiled" || echo "❌ Styles not compiled"

# Check for champions
echo "5. Checking for champions..."
CHAMPION_COUNT=$(ddev drush sqlq "SELECT COUNT(DISTINCT cs.uid) FROM commerce_subscription cs JOIN commerce_product_variation cpv ON cs.purchased_entity = cpv.variation_id JOIN users_field_data u ON cs.uid = u.uid JOIN user__field_champion_wall_opt_in opt ON u.uid = opt.entity_id WHERE cs.state = 'active' AND cpv.type = 'champion_membership' AND u.status = 1 AND opt.field_champion_wall_opt_in_value = 1")
echo "Found $CHAMPION_COUNT champion(s) on wall"

echo ""
echo "✨ Test complete! Visit /champions to view the wall."
```

Save as `test_wall_of_champions.sh`, make executable, and run:
```bash
chmod +x test_wall_of_champions.sh
./test_wall_of_champions.sh
```

---

**Remember:** This feature prioritizes user privacy. Always respect user choices and never force opt-in!
