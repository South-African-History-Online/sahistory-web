# CloudFlare CDN Setup for SAHO
## Global Performance & Security

**Priority:** P2 (Medium-High)
**Estimated Time:** 4 hours
**Cost:** Free tier available
**Risk Level:** 🟢 Low

---

## Benefits

- **Performance:** 50-70% faster global load times
- **Bandwidth:** Reduce origin server bandwidth by 60%+
- **Security:** DDoS protection, WAF, bot mitigation
- **Analytics:** Traffic insights and threats blocked
- **Cost:** Free tier for most needs

---

## Step 1: Sign Up & Add Domain

1. Create CloudFlare account: https://dash.cloudflare.com/sign-up
2. Add domain: `sahistory.org.za`
3. CloudFlare will scan existing DNS records
4. Verify all records migrated correctly

---

## Step 2: Update Nameservers

**At Your Domain Registrar:**

```
Old nameservers: [current nameservers]

New nameservers (CloudFlare):
 • ava.ns.cloudflare.com
 • cruz.ns.cloudflare.com
```

**Wait for propagation:** 2-24 hours (usually < 1 hour)

Check status:
```bash
dig NS sahistory.org.za
# Should show CloudFlare nameservers
```

---

## Step 3: Configure CloudFlare Settings

### SSL/TLS Settings

```
SSL/TLS encryption mode: Full (Strict)
- Requires valid SSL on origin server
- End-to-end encryption

Edge Certificates:
☑ Always Use HTTPS (301 redirect)
☑ Automatic HTTPS Rewrites
☑ Opportunistic Encryption
☑ TLS 1.3 (Modern clients)
☐ TLS 1.2 (Legacy support)
```

### Speed Settings

```
☑ Auto Minify
  ☑ JavaScript
  ☑ CSS
  ☑ HTML

☑ Brotli Compression

Rocket Loader: Off (conflicts with Drupal JS)

☑ Early Hints (HTTP 103)

Polish: Lossy (image optimization)
  - WebP format
  - Responsive images

☑ Mirage (lazy loading for slow connections)
```

### Caching Settings

```
Caching Level: Standard

Browser Cache TTL: 4 hours
  (Drupal handles dynamic content)

☑ Always Online (serve stale if origin down)

Crawler Hints: On
  (Help search engines find content)

Development Mode: Off
  (Use only when testing changes)
```

### Network Settings

```
☑ HTTP/2
☑ HTTP/3 (QUIC)
☑ 0-RTT Connection Resumption
☑ IPv6 Compatibility
☑ WebSockets
```

---

## Step 4: Page Rules (Free: 3 rules)

### Rule 1: Admin Pages - Bypass Cache

```
URL Pattern: sahistory.org.za/admin*

Settings:
- Cache Level: Bypass
- Security Level: High
- Disable Performance Features
```

### Rule 2: Static Assets - Aggressive Cache

```
URL Pattern: sahistory.org.za/themes/custom/saho/dist/*

Settings:
- Cache Level: Cache Everything
- Edge Cache TTL: 1 month
- Browser Cache TTL: 1 month
```

### Rule 3: API Endpoints - Cache with Short TTL

```
URL Pattern: sahistory.org.za/api/*

Settings:
- Cache Level: Cache Everything
- Edge Cache TTL: 1 hour
- Browser Cache TTL: 5 minutes
```

---

## Step 5: Firewall Rules (Free: 5 rules)

### Rule 1: Block Known Bots

```
Expression:
(cf.client.bot) and not (cf.verified_bot)

Action: Block
```

### Rule 2: Rate Limit API

```
Expression:
(http.request.uri.path contains "/api/") and
(rate_limit > 100 requests per 1m)

Action: Challenge (CAPTCHA)
```

### Rule 3: Geo-Blocking (Optional)

```
Expression:
(ip.geoip.country in {"CN" "RU"}) and
(not http.request.uri.path contains "/api/schema/")

Action: Block

Note: Allow Schema.org API for international research
```

### Rule 4: Protect Login

```
Expression:
(http.request.uri.path eq "/user/login") and
(rate_limit > 5 requests per 5m)

Action: Challenge
```

### Rule 5: Block SQL Injection Attempts

```
Expression:
(http.request.uri.query contains "union select") or
(http.request.uri.query contains "' or 1=1")

Action: Block
```

---

## Step 6: Configure Origin Server

### Update Drupal settings.php

```php
// Trust CloudFlare IPs for REMOTE_ADDR
// https://www.cloudflare.com/ips/

// CloudFlare IPv4 ranges
$cloudflare_ips = [
  '173.245.48.0/20',
  '103.21.244.0/22',
  '103.22.200.0/22',
  // ... (get full list from CloudFlare docs)
];

// Trust CloudFlare to provide real client IP
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = $cloudflare_ips;
$settings['reverse_proxy_header'] = 'HTTP_CF_CONNECTING_IP';
```

### Update .htaccess (if needed)

```apache
# Trust CloudFlare for client IP
<IfModule mod_remoteip.c>
  RemoteIPHeader CF-Connecting-IP
  RemoteIPInternalProxy 173.245.48.0/20
  # Add all CloudFlare IP ranges
</IfModule>
```

---

## Step 7: Cache Purging Integration

### Install CloudFlare Module

```bash
composer require drupal/cloudflare
ddev drush en cloudflare -y

# Configure API credentials
ddev drush config:set cloudflare.settings apikey "YOUR_API_KEY" -y
ddev drush config:set cloudflare.settings email "your@email.com" -y
ddev drush config:set cloudflare.settings zone "YOUR_ZONE_ID" -y
```

### Get Zone ID

1. CloudFlare Dashboard → sahistory.org.za
2. Overview tab → API section → Zone ID
3. Copy Zone ID

### Get API Key

1. CloudFlare Dashboard → My Profile → API Tokens
2. Create Token → Edit zone DNS
3. Permissions: Zone → Cache Purge → Purge
4. Zone Resources: Include → Specific zone → sahistory.org.za
5. Create Token
6. Copy token

### Configure Auto-Purge

```php
// settings.php
// Purge CloudFlare cache when Drupal cache clears

$config['cloudflare.settings']['zone_id'] = 'YOUR_ZONE_ID';
$config['cloudflare.settings']['apikey'] = 'YOUR_API_KEY';
$config['cloudflare.settings']['email'] = 'your@email.com';

// Auto-purge on node save
$config['cloudflare.settings']['bypass_host'] = FALSE;
```

### Manual Purge

```bash
# Purge entire site
ddev drush cloudflare-purge-all

# Purge specific URLs
ddev drush cloudflare-purge /node/123
ddev drush cloudflare-purge /api/schema/types

# Purge by tag
ddev drush cloudflare-purge-tags node:article:123
```

---

## Step 8: Testing & Validation

### Check CDN is Working

```bash
# Check if CloudFlare is serving
curl -I https://sahistory.org.za/ | grep -i cf-

# Should see headers like:
# cf-cache-status: HIT
# cf-ray: [ID]-[COLO]
# cf-request-id: [ID]
```

### Test Cache Hit Rates

```bash
# First request (MISS)
curl -I https://sahistory.org.za/node/123 | grep cf-cache-status
# cf-cache-status: MISS

# Second request (HIT)
curl -I https://sahistory.org.za/node/123 | grep cf-cache-status
# cf-cache-status: HIT
```

### Test from Multiple Locations

Use: https://www.webpagetest.org/

Test from:
- Johannesburg, South Africa (local)
- London, UK (Europe)
- New York, USA (Americas)
- Sydney, Australia (Asia-Pacific)

**Before CloudFlare:** ~3-5s load time globally
**After CloudFlare:** ~0.5-1.5s load time globally

---

## Step 9: Monitor Performance

### CloudFlare Analytics Dashboard

Key metrics:
- **Requests:** Total requests served
- **Bandwidth:** Data transferred
- **Cache Hit Ratio:** % served from CDN (target: 80%+)
- **Threats Blocked:** Security incidents prevented
- **Response Time:** Average load time

### Set Up Alerts

Email notifications for:
- Traffic spikes (DDoS potential)
- Origin server errors (5xx)
- SSL certificate expiry
- Unusual traffic patterns

---

## Step 10: Advanced Features (Pro Tier - $20/month)

### If Upgrade Needed:

**Pro Tier Benefits:**
- **20 Page Rules** (vs 3 on free)
- **WAF (Web Application Firewall)**
- **Image Optimization** (Polish, Mirage)
- **Mobile Optimization**
- **Prioritized Support**

**When to Upgrade:**
- Traffic > 1M requests/month
- Need advanced DDoS protection
- Want detailed analytics
- Require faster support response

---

## Rollback Procedure

If issues arise:

### Immediate Rollback (Emergency)

```
1. CloudFlare Dashboard → DNS
2. Click "Bypass CloudFlare" icon (gray cloud)
3. DNS will point directly to origin
4. Takes effect in ~5 minutes
```

### Complete Rollback

```
1. Update nameservers at domain registrar
2. Point back to original nameservers
3. Wait for DNS propagation (2-24 hours)
4. Verify with: dig NS sahistory.org.za
```

---

## Cost Analysis

### Free Tier (Recommended Start)

```
Cost: $0/month
Bandwidth: Unlimited
SSL: Free
DDoS Protection: Unlimited
Page Rules: 3
Firewall Rules: 5
Analytics: Basic

Perfect for: Most small-medium sites
```

### Pro Tier ($20/month)

```
Cost: $20/month
Bandwidth: Unlimited
All Free features PLUS:
- 20 Page Rules
- WAF
- Image Optimization
- Mobile Optimization
- Prioritized Support

Upgrade when: Traffic > 1M/month or need WAF
```

### ROI Calculation

**Bandwidth Savings:**
- Current bandwidth: ~500GB/month
- Cost without CDN: ~$50/month (typical hosting)
- Cost with CloudFlare Free: $0/month
- **Savings: $50/month = $600/year**

**Performance Gains:**
- Page load time: 3s → 0.8s (73% faster)
- Bounce rate reduction: ~20%
- SEO ranking improvement
- Better user experience globally

---

## Troubleshooting

### Issue: SSL Certificate Errors

**Solution:**
```
1. CloudFlare Dashboard → SSL/TLS → Edge Certificates
2. Verify: Universal SSL Status = Active
3. If pending: Wait 24 hours
4. If failed: Contact CloudFlare support
```

### Issue: Cache Not Purging

**Solution:**
```bash
# Check Drupal CloudFlare module config
ddev drush config:get cloudflare.settings

# Test API credentials
curl -X GET "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE_ID" \
  -H "Authorization: Bearer YOUR_API_KEY"

# Manual purge via API
curl -X POST "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE_ID/purge_cache" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

### Issue: Admin Panel Slow

**Solution:**
```
1. Create Page Rule for /admin/*
2. Settings: Cache Level = Bypass
3. Disable all performance features for admin
```

### Issue: Dynamic Content Cached

**Solution:**
```
# Add Cache-Control headers in Drupal

// In controller or hook_page_attachments()
$response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
$response->headers->set('Pragma', 'no-cache');
$response->headers->set('Expires', '0');
```

---

## Security Best Practices

### Enable Security Features

```
☑ Security Level: Medium
  (Challenge suspicious requests)

☑ Challenge Passage: 30 minutes
  (How long challenge cookie lasts)

☑ Browser Integrity Check
  (Block known malicious browsers)

☐ Privacy Pass Support
  (Alternative to CAPTCHAs - optional)

☑ Email Obfuscation
  (Protect emails from scrapers)

☑ Server-Side Excludes
  (Remove sensitive data from HTML)

☑ Hotlink Protection
  (Prevent image bandwidth theft)
```

### Bot Management

```
Known Bots: Allow
  (Google, Bing, etc.)

Verified Bots: Allow
  (Certified good bots)

Unknown Bots: Challenge
  (Suspicious or unverified)

Definitely Bots: Block
  (Known malicious bots)
```

---

## Maintenance

### Weekly Tasks

- [ ] Check Analytics Dashboard
- [ ] Review Firewall Events log
- [ ] Monitor cache hit ratio (target: 80%+)
- [ ] Check for blocked threats

### Monthly Tasks

- [ ] Review Page Rules effectiveness
- [ ] Update Firewall Rules if new threats
- [ ] Audit bandwidth savings
- [ ] Check SSL certificate status (auto-renews)

### Quarterly Tasks

- [ ] Performance comparison testing
- [ ] Review CloudFlare features for new additions
- [ ] Evaluate if Pro tier upgrade needed
- [ ] Security audit and rule updates

---

## Resources

- **CloudFlare Docs:** https://developers.cloudflare.com/
- **Drupal Module:** https://www.drupal.org/project/cloudflare
- **Status Page:** https://www.cloudflarestatus.com/
- **Community:** https://community.cloudflare.com/
- **Speed Test:** https://www.cloudflare.com/ssl/encrypted-sni/

---

**Status:** Ready to implement
**Next Steps:** Sign up, migrate DNS, configure rules, test performance
**ROI:** $600/year savings + 73% faster load times
