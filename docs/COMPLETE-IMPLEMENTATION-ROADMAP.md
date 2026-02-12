# Complete Implementation Roadmap
## SAHO Project Improvements - Full Plan

**Created:** 2026-02-12
**Status:** Planning Complete - Ready for Implementation
**Total Effort:** 130.5 hours (~4 weeks)

---

## Executive Summary

This roadmap consolidates all recommendations from the comprehensive project analysis into a phased, prioritized implementation plan with clear dependencies, timelines, and success criteria.

---

## Implementation Phases Overview

| Phase | Focus | Duration | Risk | Priority |
|-------|-------|----------|------|----------|
| **Phase 0** | Quick Wins | 1 day | 🟢 None | P1 |
| **Phase 1** | Field Consolidation | 2 weeks | 🟡 Low | P1-P2 |
| **Phase 2** | Modern Features | 1 week | 🟢 Low | P2 |
| **Phase 3** | Content Migration | 2 weeks | 🟠 Medium | P2-P3 |
| **Phase 4** | Legacy Cleanup | 3 months | 🔴 High | P3 |

**Total Timeline:** 6 months (conservative) or 2 months (aggressive)

---

## Phase 0: Quick Wins (Day 1)

**Goal:** Maximum impact, zero risk, immediate benefits

**Duration:** 4-8 hours
**Risk Level:** 🟢 None
**Dependencies:** None
**Can Start:** Immediately

### Tasks

#### 1. Create robots.txt (30 min)
**File:** `webroot/robots.txt`
**Owner:** Any developer
**Testing:** `curl https://sahistory.org.za/robots.txt`

```bash
cat > webroot/robots.txt << 'EOF'
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /user/
Disallow: /api/internal/

# AI Crawlers
User-agent: GPTBot
User-agent: ChatGPT-User
Allow: /

Sitemap: https://sahistory.org.za/sitemap.xml
EOF

git add webroot/robots.txt
git commit -m "Add robots.txt for SEO and AI crawler guidance"
```

**Success Criteria:**
- [ ] File accessible at `/robots.txt`
- [ ] Google Search Console recognizes file
- [ ] AI crawlers can discover llm.txt

---

#### 2. Enable JSON:API (1 hour)
**Owner:** Backend developer
**Testing:** `curl https://sahistory.org.za/jsonapi/node/article`

```bash
ddev drush en jsonapi -y
ddev drush config:set jsonapi.settings read_only true -y
ddev drush config:export -y
```

**Success Criteria:**
- [ ] `/jsonapi` endpoint accessible
- [ ] Returns valid JSON for all content types
- [ ] Read-only mode verified (no PUT/POST/DELETE)
- [ ] CORS headers configured
- [ ] Documentation updated

**Documentation:** See `docs/JSON-API-USAGE.md` (create)

---

#### 3. Embed Schema.org in HTML (2 hours)
**Owner:** Theme developer
**Files:** `webroot/themes/custom/saho/saho.theme`, all node templates

**Implementation:**
```php
// saho.theme - Add to existing saho_preprocess_node()
if ($variables['view_mode'] === 'full') {
  try {
    $schema_service = \Drupal::service('saho_tools.schema_org');
    $schema_data = $schema_service->getNodeSchema($node);

    if ($schema_data) {
      $variables['schema_json'] = json_encode(
        $schema_data,
        JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
      );
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('saho')->error('Schema generation failed: @message', [
      '@message' => $e->getMessage(),
    ]);
  }
}
```

**Template update:**
```twig
{# Add to all node templates before </article> #}
{% if schema_json %}
<script type="application/ld+json">
  {{ schema_json|raw }}
</script>
{% endif %}
```

**Testing:**
- [ ] Google Rich Results Test passes
- [ ] Schema.org Validator passes
- [ ] No JavaScript errors in console

---

#### 4. Add Organization Schema (30 min)
**Owner:** Theme developer
**File:** `webroot/themes/custom/saho/templates/system/html.html.twig`

```twig
{% if not logged_in %}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "EducationalOrganization",
  "name": "South African History Online",
  "alternateName": "SAHO",
  "url": "https://sahistory.org.za",
  "logo": "https://sahistory.org.za/themes/custom/saho/logo.svg",
  "foundingDate": "2000",
  "sameAs": [
    "https://www.facebook.com/sahistoryonline",
    "https://twitter.com/sahistoryonline"
  ]
}
</script>
{% endif %}
```

**Testing:**
- [ ] Schema appears in HTML source
- [ ] Google Search Console recognizes organization
- [ ] Knowledge Panel shows correct info (may take weeks)

---

### Phase 0 Success Criteria

**Checklist:**
- [ ] All 4 tasks completed
- [ ] Zero errors in production
- [ ] Configuration exported
- [ ] Git commit created
- [ ] PR merged to main
- [ ] Deployed to production
- [ ] Monitoring confirms no issues
- [ ] Documentation updated

**Impact Metrics:**
- SEO: +10-20% organic traffic within 30 days
- AI Discovery: llm.txt discoverable
- API: Standard REST access enabled
- Rich Snippets: Better search result appearance

---

## Phase 1: Field Consolidation (Weeks 2-3)

**Goal:** Reduce field redundancy, improve content management

**Duration:** 2 weeks (12 business days)
**Risk Level:** 🟡 Low (non-destructive approach)
**Dependencies:** Phase 0 complete
**Can Start:** After Phase 0

### Task Breakdown

#### Week 1: Author Field Migration

**Day 1-2: Planning & Setup**
- [ ] Database backup
- [ ] Create author taxonomy vocabulary
- [ ] Create `field_author_unified` field storage
- [ ] Attach to all content types
- [ ] Export configuration
- [ ] Write dry-run migration script

**Day 3: Dry-Run Testing**
- [ ] Run dry-run on production clone
- [ ] Review migration report
- [ ] Identify edge cases
- [ ] Test rollback procedure
- [ ] Document findings

**Day 4-5: Execute Migration**
- [ ] Run migration in batches (100 nodes/batch)
- [ ] Monitor logs for errors
- [ ] Verify data integrity
- [ ] Update templates with fallback
- [ ] Test frontend rendering

**Success Criteria:**
- [ ] 100% nodes migrated successfully
- [ ] Old fields intact (backup)
- [ ] New fields populated
- [ ] Templates show correct data
- [ ] No broken links or missing content

**Documentation:** See `docs/NON-DESTRUCTIVE-REFACTOR-PLAN.md` Section 2.1

---

#### Week 2: Image Field Consolidation

**Day 6-8: Image Migration**
- [ ] Create `field_image_unified` (media reference)
- [ ] Migrate images to Media Library
- [ ] Copy references to new field
- [ ] Update image styles
- [ ] Update templates

**Day 9-10: Testing & Monitoring**
- [ ] Visual regression testing
- [ ] Performance testing (image loading)
- [ ] Mobile testing
- [ ] Cross-browser testing
- [ ] Monitor 404s for broken images

**Success Criteria:**
- [ ] All images accessible via new field
- [ ] Old image fields intact
- [ ] Responsive images working
- [ ] WebP optimization still functioning
- [ ] No performance regression

---

### Phase 1 Monitoring Period

**Duration:** 30 days after migration
**Activities:**
- Daily monitoring of field usage
- Weekly data integrity checks
- User feedback collection
- Performance metrics tracking

**Go/No-Go Decision:** After 30 days of stable operation
- ✅ Go: Proceed to mark old fields as deprecated
- ⛔ No-Go: Rollback and re-evaluate approach

---

## Phase 2: Modern Features (Week 4)

**Goal:** PWA, CDN, enhanced user experience

**Duration:** 1 week (5 business days)
**Risk Level:** 🟢 Low (additive features)
**Dependencies:** None (can run parallel to Phase 1)
**Can Start:** Anytime

### Day 1-2: PWA Implementation

**Tasks:**
- [ ] Install PWA module
- [ ] Create app icons (192px, 512px)
- [ ] Create manifest.json
- [ ] Write service worker
- [ ] Register service worker
- [ ] Create offline page
- [ ] Test installation flow

**Success Criteria:**
- [ ] Lighthouse PWA score: 100/100
- [ ] Install prompt appears on mobile
- [ ] Offline page displays when no connection
- [ ] Service worker caches assets

**Documentation:** See `docs/PWA-IMPLEMENTATION.md`

---

### Day 3-4: CDN Configuration

**Tasks:**
- [ ] CloudFlare account setup
- [ ] DNS migration
- [ ] Configure page rules (3)
- [ ] Configure firewall rules (5)
- [ ] Set up cache purging
- [ ] Install Drupal CloudFlare module
- [ ] Test from multiple locations

**Success Criteria:**
- [ ] Cache hit ratio > 80%
- [ ] Global load time < 1.5s
- [ ] DDoS protection active
- [ ] SSL working correctly
- [ ] Admin panel not cached

**Documentation:** See `docs/CDN-CLOUDFLARE-SETUP.md`

---

### Day 5: Testing & Documentation

**Tasks:**
- [ ] Performance benchmarking (before/after)
- [ ] Mobile experience testing
- [ ] PWA installation testing (iOS, Android)
- [ ] CDN cache testing
- [ ] Security testing
- [ ] Update documentation

**Metrics:**
- Page load time: -70% (target)
- Bandwidth usage: -60% (target)
- PWA installs: Track baseline

---

## Phase 3: Content Migration (Weeks 5-6)

**Goal:** Migrate legacy content types to modern architecture

**Duration:** 2 weeks (10 business days)
**Risk Level:** 🟠 Medium (creates new content, doesn't delete old)
**Dependencies:** Phase 1 complete (field consolidation)
**Can Start:** After Phase 1 success

### Week 1: Node Gallery Migration

**Day 1-3: Setup & Migration**
- [ ] Audit existing galleries
- [ ] Create new gallery content type
- [ ] Write migration script
- [ ] Test on clone database
- [ ] Execute migration in batches
- [ ] Create 301 redirects

**Day 4-5: Validation**
- [ ] Verify all images migrated
- [ ] Test media library functionality
- [ ] Check image display on frontend
- [ ] Verify redirects working
- [ ] Performance testing

**Success Criteria:**
- [ ] All galleries accessible via new content type
- [ ] Old galleries preserved (not deleted)
- [ ] 301 redirects prevent 404s
- [ ] Media library working correctly

---

### Week 2: Content Type Consolidation

**Day 6-8: Legacy Type Migration**
- [ ] `button` → Block types
- [ ] `panel` → Layout Builder sections
- [ ] `landing_page_banners` → Block types
- [ ] `frontpagecustom` → Layout Builder

**Day 9-10: Testing & Documentation**
- [ ] Test all landing pages
- [ ] Verify homepage functionality
- [ ] Check Layout Builder
- [ ] Update content editor documentation

---

### Phase 3 Monitoring Period

**Duration:** 30 days
**Go/No-Go:** Decide whether to deprecate old content types

---

## Phase 4: Legacy Cleanup (Months 3-6)

**Goal:** Remove deprecated fields and content types

**Duration:** 3 months (monitoring + cleanup)
**Risk Level:** 🔴 High (irreversible deletions)
**Dependencies:** Phases 1-3 complete + 90 days stable
**Can Start:** Only after 90+ days of zero issues

### Month 3: Deprecation Warnings

**Tasks:**
- [ ] Mark fields as deprecated in descriptions
- [ ] Hide from content add forms
- [ ] Add admin warnings
- [ ] Email content editors about deprecation
- [ ] Create deprecation timeline

---

### Month 4-5: Continued Monitoring

**Tasks:**
- [ ] Weekly usage reports
- [ ] Confirm zero usage of old fields
- [ ] Archive old field data to files
- [ ] Stakeholder approval for deletion
- [ ] Create final backup

---

### Month 6: Final Cleanup

**Tasks:**
- [ ] Full backup created
- [ ] Archive exported to files
- [ ] Delete deprecated fields
- [ ] Delete legacy content types
- [ ] Clean up configuration
- [ ] Update documentation
- [ ] Celebrate! 🎉

**Success Criteria:**
- [ ] 199 field storages → ~120 field storages (40% reduction)
- [ ] 20 content types → ~14 content types (30% reduction)
- [ ] Zero data loss
- [ ] Improved content editor experience

---

## Risk Management

### Risk Matrix

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Data loss during migration | Low | Critical | Non-destructive approach, backups, rollback scripts |
| Performance degradation | Low | High | Monitoring, testing, rollback capability |
| CDN misconfiguration | Medium | Medium | Test on staging, gradual rollout |
| PWA breaking functionality | Low | Medium | Feature flag, easy disable |
| Schema.org validation errors | Medium | Low | Testing, validation tools |

### Rollback Procedures

Every phase has documented rollback:
- **Phase 0:** Revert commits (instant)
- **Phase 1:** Run rollback scripts (< 1 hour)
- **Phase 2:** Disable modules (instant)
- **Phase 3:** Delete new content, keep old (< 2 hours)
- **Phase 4:** Restore from archive (< 4 hours)

---

## Resource Allocation

### Team Requirements

| Role | Phase 0 | Phase 1 | Phase 2 | Phase 3 | Phase 4 |
|------|---------|---------|---------|---------|---------|
| Backend Dev | 4h | 40h | - | 40h | 8h |
| Frontend Dev | 4h | 8h | 24h | 8h | - |
| DevOps | - | 4h | 16h | 4h | 4h |
| QA | - | 8h | 8h | 8h | 4h |
| Content Editor | - | 4h | - | 4h | 2h |
| **Total** | **8h** | **64h** | **48h** | **64h** | **18h** |

**Grand Total:** 202 hours (~5 weeks for 1 FTE)

---

## Budget Estimate

### Development Costs

```
Phase 0: 8 hours × $75/hr = $600
Phase 1: 64 hours × $75/hr = $4,800
Phase 2: 48 hours × $75/hr = $3,600
Phase 3: 64 hours × $75/hr = $4,800
Phase 4: 18 hours × $75/hr = $1,350
------------------------------------
Total Dev Cost: $15,150
```

### Operational Costs

```
CloudFlare CDN: $0/month (Free tier) or $20/month (Pro)
PWA: $0/month (self-hosted)
Backup Storage: $10/month
Monitoring Tools: $0/month (Free tier) or $50/month (Premium)
------------------------------------
Monthly Recurring: $10-80/month
```

### ROI Calculation

**Benefits:**
- Bandwidth savings: $50/month = $600/year
- Improved SEO: +20% traffic = +$1,000/year (donations)
- Better UX: +15% engagement = +$500/year
- Reduced maintenance: -10 hours/month = $750/year

**Total Annual Benefit:** $2,850/year
**Payback Period:** 5.3 years

**Intangible Benefits:**
- Better discoverability by AI systems
- Improved mobile experience
- Reduced technical debt
- Easier content management
- Better performance globally

---

## Success Metrics

### Technical Metrics

| Metric | Current | Target | Measurement |
|--------|---------|--------|-------------|
| Page Load Time | 3.0s | 1.0s | Lighthouse |
| Lighthouse Score | 75 | 95 | Lighthouse |
| Cache Hit Ratio | N/A | 80%+ | CloudFlare |
| Field Storage Count | 199 | 120 | Drupal DB |
| Content Types | 20 | 14 | Drupal Config |
| PWA Score | 0 | 100 | Lighthouse |

### Business Metrics

| Metric | Current | Target | Measurement |
|--------|---------|--------|-------------|
| Organic Traffic | Baseline | +20% | Google Analytics |
| Mobile Traffic | Baseline | +30% | Google Analytics |
| Bounce Rate | Baseline | -20% | Google Analytics |
| Avg. Session Duration | Baseline | +25% | Google Analytics |
| PWA Installs | 0 | 1,000/month | Custom Tracking |

---

## Decision Points

### After Phase 0 (Day 2)
**Review:** SEO improvements visible?
**Decision:** Continue to Phase 1 or Phase 2?

### After Phase 1 (Week 3)
**Review:** Field migration successful? Any issues?
**Decision:** Proceed to Phase 3 or fix issues?

### After Phase 2 (Week 4)
**Review:** PWA and CDN working well? Performance gains?
**Decision:** Keep or rollback features?

### After Phase 3 (Week 6)
**Review:** Content migration complete? Any data loss?
**Decision:** Start Phase 4 monitoring or additional fixes?

### After Phase 4 Monitoring (Month 5)
**Review:** Zero usage of deprecated fields?
**Decision:** Proceed with deletion or extend monitoring?

---

## Communication Plan

### Stakeholders

**Internal Team:**
- Daily standups during active development
- Weekly progress reports
- Issue tracking in GitHub
- Documentation in `/docs`

**Content Editors:**
- Email before major changes
- Training sessions after Phase 1, 3
- Updated content management guide
- Support channel for questions

**Users:**
- Site banner during maintenance
- Social media updates for major features
- Changelog published
- Feedback collection

**Leadership:**
- Monthly executive summary
- ROI tracking report
- Risk and issue escalation
- Budget vs. actual tracking

---

## Contingency Plans

### If Phase 1 Fails
- Rollback all field migrations
- Keep using old fields
- Re-evaluate migration approach
- Consider paid migration tools

### If Phase 2 CDN Issues
- Disable CloudFlare (bypass mode)
- Revert DNS to origin
- Investigate configuration
- Try alternative CDN (AWS CloudFront)

### If Phase 3 Data Loss
- Restore from backup immediately
- Audit migration scripts
- Add more validation
- Increase testing before retry

### If Budget Overruns
- Prioritize Phase 0 and 2 (highest ROI)
- Defer Phase 3 and 4
- Re-evaluate timeline
- Consider reducing scope

---

## Post-Implementation

### Month 7-12: Optimization

**Activities:**
- Fine-tune CDN cache rules
- Optimize PWA caching strategy
- Add push notifications (PWA)
- Implement GraphQL (if needed)
- A/B test new features
- Collect user feedback
- Continuous performance monitoring

**Metrics:**
- Monitor all success metrics
- Quarterly performance reviews
- Annual ROI analysis
- User satisfaction surveys

---

## Appendices

### A. Related Documentation

- `docs/NON-DESTRUCTIVE-REFACTOR-PLAN.md` - Field consolidation
- `docs/PWA-IMPLEMENTATION.md` - PWA setup
- `docs/CDN-CLOUDFLARE-SETUP.md` - CDN setup
- `docs/COMPREHENSIVE-ANALYSIS.md` - Full project analysis

### B. Scripts Location

- `scripts/migrate-authors-*.php` - Author field migration
- `scripts/migrate-images-*.php` - Image field migration
- `scripts/migrate-node-gallery.php` - Gallery migration
- `scripts/monitor-*.php` - Monitoring scripts
- `scripts/rollback-*.php` - Rollback scripts

### C. Backup Locations

- `backups/db/` - Database backups
- `backups/files/` - File system backups
- `backups/config/` - Configuration backups
- `logs/` - Migration logs and rollback data

### D. Testing Environments

- Production: https://sahistory.org.za
- Staging: https://staging.sahistory.org.za
- Development: https://sahistory-web.ddev.site

---

## Sign-Off

**Prepared by:** Development Team
**Date:** 2026-02-12
**Version:** 1.0.0

**Approvals Required:**

- [ ] Technical Lead: _______________ Date: ___________
- [ ] Project Manager: ______________ Date: ___________
- [ ] Product Owner: ________________ Date: ___________
- [ ] Finance: ______________________ Date: ___________

---

**Next Steps:**

1. ✅ Review and approve roadmap
2. ⏭️ Schedule Phase 0 implementation (1 day)
3. ⏭️ Assign team members to phases
4. ⏭️ Set up project tracking (GitHub Projects)
5. ⏭️ Create communication schedule
6. ⏭️ Begin Phase 0 implementation

---

**Questions or Concerns:**

Contact: [Project Lead Name] - [Email] - [Phone]

**Status Updates:**

Weekly progress reports will be shared via email and posted to the project wiki.
