# Architecture Overview

Comprehensive overview of the SA History Web technical architecture, system design, and component relationships.

## System Architecture

### High-Level Overview
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Drupal Core   │    │   Database      │
│   (SAHO Theme)  │◄──►│   + Custom      │◄──►│   (MySQL)       │
│                 │    │   Modules       │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
        ▲                        ▲                        ▲
        │                        │                        │
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Static Files  │    │   File System   │    │   Search Index  │
│   (CSS/JS/IMG)  │    │   (Media)       │    │   (Optional)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Core Components

### 1. Drupal Foundation
**Version**: Drupal 11.2.3
**Purpose**: Content management, user authentication, API foundation

**Key Drupal Modules**:
- **Views**: Dynamic content listings and queries
- **Media**: File and image management
- **Pathauto**: Clean URL generation
- **Metatag**: SEO optimization
- **Admin Toolbar**: Enhanced admin experience

### 2. Custom SAHO Theme
**Location**: `webroot/themes/custom/saho/`
**Framework**: Bootstrap 5

**Key Features**:
- **Responsive Design**: Mobile-first approach
- **SA Heritage Colors**: Brand-consistent color palette
- **Component Library**: Reusable UI components
- **Accessibility**: WCAG 2.1 AA compliance
- **Performance**: Optimized CSS/JS delivery

### 3. Custom Modules

#### SAHO Featured Articles (`saho_featured_articles`)
**Purpose**: Dynamic featured content management and display

**Components**:
```
saho_featured_articles/
├── src/
│   └── Controller/
│       └── FeaturedArticlesController.php  # Main page controller
├── templates/
│   └── saho-featured-articles.html.twig    # Template file
├── css/
│   └── featured-articles.css               # Module styles
├── saho_featured_articles.info.yml         # Module definition
├── saho_featured_articles.libraries.yml    # Asset libraries
├── saho_featured_articles.module           # Hooks and functions
└── saho_featured_articles.routing.yml      # Route definitions
```

**Functionality**:
- **Route**: `/featured` - Main featured content page
- **Database Queries**: Retrieves nodes marked as featured/staff picks
- **Dynamic Categories**: Liberation Struggle, Heritage Sites, Apartheid Era
- **Template Rendering**: Proper Drupal template system integration

## Data Architecture

### Content Types

#### Core Content Types
```sql
-- Articles: Historical articles and essays
node_type: article
├── field_article_image     # Primary article image
├── field_synopsis          # Article summary
├── field_staff_picks       # Boolean: Editor selection
├── field_home_page_feature # Boolean: Homepage feature
└── body                    # Main article content

-- Biographies: Historical figures
node_type: biography  
├── field_bio_pic           # Portrait image
├── field_birth_date        # Date of birth
├── field_death_date        # Date of death (if applicable)
├── field_synopsis          # Biography summary
└── body                    # Full biography

-- Places: Historical locations
node_type: place
├── field_place_image       # Location images
├── field_coordinates       # Geographic coordinates
├── field_place_type        # Category of place
└── body                    # Place description

-- Events: Historical events
node_type: event
├── field_event_image       # Event images
├── field_event_date        # Event date(s)
├── field_event_location    # Where it happened
└── body                    # Event description
```

#### Custom Fields
```php
// Featured content management
field_staff_picks: Boolean (0/1)
field_home_page_feature: Boolean (0/1)

// Content categorization
field_timeline_categories: Taxonomy reference
field_place_type_category: Taxonomy reference
field_african_country: Taxonomy reference
```

### Database Relationships

```sql
-- Featured content query logic
SELECT DISTINCT n.nid 
FROM node_field_data n
WHERE (
    n.nid IN (
        SELECT entity_id FROM node__field_staff_picks 
        WHERE field_staff_picks_value = 1 AND deleted = 0
    )
    OR 
    n.nid IN (
        SELECT entity_id FROM node__field_home_page_feature 
        WHERE field_home_page_feature_value = 1 AND deleted = 0
    )
) 
AND n.status = 1
LIMIT 50;
```

## Frontend Architecture

### Theme Structure
```
saho/
├── css/
│   ├── main.style.css              # Compiled main styles
│   ├── featured-content-modern.css # Featured page specific
│   └── components/                 # Component-specific CSS
├── js/
│   ├── main.script.js             # Global JavaScript
│   └── featured-content.js        # Featured page interactions
├── templates/
│   ├── views/                     # Views template overrides
│   ├── nodes/                     # Node template overrides
│   └── pages/                     # Page template overrides
├── src/scss/                      # SCSS source files
└── build/                         # Compiled assets
```

### Component System

#### Landing Page Components
- **Hero Section**: Title, description, statistics
- **Navigation Sidebar**: Category filtering with icons
- **Content Grid**: Bootstrap-based responsive grid
- **Content Cards**: Reusable card components with images
- **Call-to-Action**: Bottom section with navigation links

#### JavaScript Architecture
```javascript
// Drupal behaviors pattern
Drupal.behaviors.featuredContentNavigation = {
  attach: function (context, settings) {
    // Category switching logic
    // Content loading and filtering  
    // Dynamic count updates
  }
};
```

## Routing & URL Structure

### Core Routes
```yaml
# Main featured content page
featured_content.page:
  path: '/featured'
  controller: 'FeaturedArticlesController::page'
  
# Content type routes (Drupal default)
article: /article/{node}
biography: /biography/{node} 
place: /place/{node}
event: /event/{node}
```

### Clean URLs via Pathauto
```
Articles: /articles/[node:title]
Biographies: /people/[node:title]
Places: /places/[node:title]
Events: /events/[node:title]
```

## Security Architecture

### Access Control
- **Drupal Permissions**: Role-based content access
- **HTTPS Enforcement**: SSL/TLS for all communications
- **Input Validation**: Drupal's built-in sanitization
- **CSRF Protection**: Drupal's token system

### Content Security
```php
// Template security (Twig auto-escaping)
{{ node_title }}                    # Auto-escaped
{{ node_title|raw }}               # Raw (avoid)
{{ node_title|striptags|trim }}    # Sanitized
```

## Performance Architecture

### Caching Strategy
```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ Browser     │    │ Drupal      │    │ Database    │
│ Cache       │    │ Cache       │    │ Query       │
│ (Static)    │    │ (Dynamic)   │    │ Cache       │
└─────────────┘    └─────────────┘    └─────────────┘
```

**Cache Layers**:
1. **Browser Cache**: Static assets (CSS, JS, images)
2. **Drupal Page Cache**: Full page caching for anonymous users
3. **Drupal Internal Cache**: Configuration, plugins, queries
4. **Database Query Cache**: MySQL query result caching

### Asset Optimization
```yaml
# CSS/JS aggregation (production)
system.performance:
  css.preprocess: true
  js.preprocess: true
  
# Image optimization
image_styles:
  - thumbnail: 150x150
  - medium: 400x300  
  - large: 800x600
```

## Integration Points

### Search Integration
- **Drupal Search**: Built-in search functionality
- **Views Integration**: Filtered content listings
- **Faceted Search**: Category and metadata filtering

### Media Management
```php
// Image field handling
$image_fields = [
  'field_article_image',
  'field_bio_pic', 
  'field_place_image',
  'field_event_image'
];
```

### Third-Party Services
- **DDEV**: Local development environment
- **Git**: Version control and deployment
- **Composer**: PHP dependency management

## Development Patterns

### Controller Pattern
```php
class FeaturedArticlesController extends ControllerBase {
  public function page() {
    // Database queries
    // Node loading
    // Template rendering
    return $build;
  }
}
```

### Template Pattern
```twig
{# Proper Drupal templating #}
{% for node in nodes %}
  {% set node_url = path('entity.node.canonical', {'node': node.id()}) %}
  {% set node_title = node.label() %}
  {# Render node data safely #}
{% endfor %}
```

## Scalability Considerations

### Horizontal Scaling
- **Database**: Read replicas for high-traffic scenarios
- **File Storage**: CDN integration for media files
- **Application**: Load balancer support

### Performance Monitoring
- **Database Queries**: Slow query logging
- **Cache Hit Rates**: Drupal cache statistics  
- **Page Load Times**: Frontend performance metrics

---

**Next Reading**:
- [Custom Modules](Custom-Modules.md) - Detailed module documentation
- [Theme Development](Theme-Development.md) - Frontend development guide
- [Content Structure](Content-Structure.md) - Content modeling details