# Featured Content Management

Complete guide for managing featured articles, staff picks, and the `/featured` landing page.

## Overview

The Featured Content system allows editors to curate and showcase the most important and engaging historical content on SA History Online. This system powers the `/featured` landing page with dynamic, categorized content display.

## Content Curation Fields

### Staff Picks (`field_staff_picks`)
**Purpose**: Editor-selected content representing the best of SAHO's collection
**Type**: Boolean (Yes/No)
**Usage**: Mark exceptional articles that demonstrate editorial quality and historical significance

**When to Use Staff Picks**:
- ✅ Exceptionally well-researched articles
- ✅ Compelling storytelling with historical accuracy  
- ✅ Content that resonates with educational goals
- ✅ Articles featuring underrepresented voices
- ✅ High-quality multimedia integration

### Home Page Features (`field_home_page_feature`)
**Purpose**: Content featured on homepage and featured page
**Type**: Boolean (Yes/No)  
**Usage**: Highlight timely, important, or promotional content

**When to Use Homepage Features**:
- ✅ Current events with historical context
- ✅ Seasonal or anniversary content
- ✅ Recently published high-quality articles
- ✅ Content supporting educational campaigns
- ✅ Articles with broad public interest

## Featured Page Categories

### All Featured Content
**Source**: All content marked with either staff picks or homepage features
**Display**: Most recent content first
**Purpose**: Comprehensive view of curated content

### Staff Picks
**Source**: Content marked with `field_staff_picks = 1`
**Display**: First 8 items from featured content
**Badge**: "Staff Pick" (yellow badge)
**Purpose**: Editorial choices and quality showcase

### Most Read  
**Source**: Algorithmic selection (currently simulated)
**Display**: Every 3rd item from featured content (up to 6)
**Badge**: "Trending" (red badge)
**Purpose**: Popular content discovery

### Liberation Struggle
**Future Enhancement**: Content tagged with liberation struggle categories
**Purpose**: Focus on freedom fighters and anti-apartheid movements
**Badge**: "Freedom Fighters & Movements" (green badge)

### Heritage Sites
**Future Enhancement**: Place-based content with cultural significance
**Purpose**: Historical landmarks and cultural sites
**Badge**: "Cultural Landmarks" (blue badge)

### Apartheid Era
**Future Enhancement**: Content focused on apartheid system and resistance
**Purpose**: Systematic analysis of apartheid and its impacts
**Badge**: "Historical Analysis" (gray badge)

## Editorial Workflow

### 1. Content Review Process
```
Create Content → Review for Quality → Mark as Featured → Publish → Monitor Performance
```

### 2. Quality Checklist
Before marking content as featured:

**Historical Accuracy**:
- [ ] Sources properly cited and verified
- [ ] Facts cross-referenced with reliable sources
- [ ] Balanced perspective on controversial topics
- [ ] Context provided for historical events

**Editorial Standards**:
- [ ] Clear, engaging writing style
- [ ] Appropriate reading level for target audience
- [ ] Grammar and spelling reviewed
- [ ] Images properly attributed and relevant

**Cultural Sensitivity**:
- [ ] Respectful treatment of all communities
- [ ] Inclusive language and perspectives
- [ ] Appropriate handling of sensitive topics
- [ ] Recognition of diverse viewpoints

**Technical Requirements**:
- [ ] Proper categorization and tagging
- [ ] SEO-optimized title and meta description  
- [ ] High-quality featured image (if applicable)
- [ ] Mobile-friendly formatting

### 3. Marking Content as Featured

#### Via Node Edit Form:
1. Navigate to the content edit page
2. Locate the "Featured Content" section
3. Check appropriate boxes:
   - ☑️ **Staff Picks**: For editorial selections
   - ☑️ **Home Page Feature**: For homepage prominence
4. Save the content
5. Clear cache: `ddev drush cr`

#### Via Bulk Operations:
1. Go to `/admin/content`
2. Select multiple pieces of content
3. Choose "Modify field values" bulk operation
4. Update featured content fields
5. Apply to selected content

## Featured Page Management

### Accessing the Featured Page
- **Public URL**: `/featured`
- **Admin Access**: Content appears automatically based on field values
- **Cache**: Cleared automatically when content is updated

### Category Performance

#### View Statistics:
```bash
# Count staff picks
ddev drush sql-query "SELECT COUNT(*) FROM node__field_staff_picks WHERE field_staff_picks_value = 1"

# Count homepage features  
ddev drush sql-query "SELECT COUNT(*) FROM node__field_home_page_feature WHERE field_home_page_feature_value = 1"

# View featured content
ddev drush sql-query "SELECT n.title, sp.field_staff_picks_value, hf.field_home_page_feature_value 
FROM node_field_data n 
LEFT JOIN node__field_staff_picks sp ON n.nid = sp.entity_id 
LEFT JOIN node__field_home_page_feature hf ON n.nid = hf.entity_id 
WHERE (sp.field_staff_picks_value = 1 OR hf.field_home_page_feature_value = 1) 
AND n.status = 1"
```

#### Performance Monitoring:
- **Load Time**: Featured page should load under 3 seconds
- **Content Freshness**: Update featured content weekly
- **User Engagement**: Monitor click-through rates to featured articles

### Troubleshooting

#### Content Not Appearing
```bash
# Check if content is published
ddev drush sql-query "SELECT title, status FROM node_field_data WHERE nid = [NODE_ID]"

# Check featured field values
ddev drush sql-query "SELECT * FROM node__field_staff_picks WHERE entity_id = [NODE_ID]"
ddev drush sql-query "SELECT * FROM node__field_home_page_feature WHERE entity_id = [NODE_ID]"

# Clear cache
ddev drush cr
```

#### Featured Page Errors
```bash
# Check module status
ddev drush pm:list | grep saho_featured

# View error logs
ddev logs | grep featured

# Re-enable module if needed
ddev drush en saho_featured_articles -y
```

## Content Strategy

### Monthly Featured Content Planning

#### Week 1: Historical Anniversaries
- Research upcoming historical anniversaries
- Prepare related featured content
- Update homepage features for timely content

#### Week 2: Staff Picks Review
- Review current staff picks performance
- Identify underrepresented topics or voices
- Add new staff picks from recent publications

#### Week 3: Content Gap Analysis
- Analyze featured content categories
- Identify missing perspectives or time periods
- Commission new content to fill gaps

#### Week 4: Performance Review
- Analyze user engagement with featured content
- Review popular content for staff pick consideration
- Plan next month's featured content strategy

### South African History Context

#### Priority Topics for Featured Content:
- **Liberation Heroes**: Mandela, Biko, Sobukwe, Sisulu, etc.
- **Women's Contributions**: Often underrepresented historical figures
- **Cultural Heritage**: Traditional practices, languages, arts
- **Social Movements**: Trade unions, student movements, civil society
- **Places of Memory**: Robben Island, Soweto, District Six, etc.

#### Seasonal Considerations:
- **June**: Youth Month (June 16, 1976 Soweto Uprising)
- **August**: Women's Month (Women's March, 1956)
- **September**: Heritage Month (Cultural diversity)
- **December**: Human Rights Month (Bill of Rights)

## Best Practices

### Content Curation
1. **Quality over Quantity**: Better to have fewer, exceptional featured items
2. **Diversity**: Ensure representation across different communities and time periods
3. **Balance**: Mix different content types (articles, biographies, places)
4. **Freshness**: Regularly rotate featured content to maintain user interest

### Technical Management
1. **Cache Management**: Clear cache after featuring/unfeaturing content
2. **Performance**: Monitor page load times with many featured items
3. **Mobile Testing**: Ensure featured page works well on mobile devices
4. **Accessibility**: Verify all featured content meets accessibility standards

### Editorial Guidelines
1. **Review Cycle**: Review featured content monthly
2. **Quality Control**: Use the checklist above for all featured content
3. **Cultural Sensitivity**: Always consider cultural implications
4. **Educational Value**: Prioritize content with clear educational benefits

---

**Related Documentation**:
- [Content Guidelines](Content-Guidelines.md) - General content standards
- [Publishing Workflow](Publishing-Workflow.md) - Editorial process
- [Architecture](Architecture.md) - Technical implementation details