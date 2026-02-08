# Educational Resources Block

A visual, educational block that showcases South African educational resources organized by type (CAPS documents, school books, AIDS resources, teaching materials, and policy documents).

## Features

- **Colorful Resource Cards**: Each resource type displayed as a vibrant, color-coded card
- **Content Counts**: Shows number of articles available per resource type
- **Featured Items**: Displays recent or featured content for each type
- **Multiple Display Modes**:
  - Grid (default) - responsive card grid
  - Carousel - mobile-friendly slideshow
  - List - stacked vertical layout
- **Configurable Filters**: Show all resources, documents only, or materials only
- **Fully Responsive**: Optimized for desktop, tablet, and mobile
- **Accessible**: WCAG 2.1 AA compliant with proper ARIA labels and keyboard navigation

## Resource Types

### CAPS Documents
- **Color**: Deep Heritage Red (#990000)
- **Icon**: File document
- **Keywords**: CAPS, curriculum, assessment policy
- Curriculum Assessment Policy Statements and related documents

### School Books
- **Color**: Slate Blue (#3a4a64)
- **Icon**: Book
- **Keywords**: book, textbook, reading
- Educational books, textbooks, and reading materials

### AIDS Resources
- **Color**: Faded Brick Red (#8b2331)
- **Icon**: Heartbeat
- **Keywords**: AIDS, HIV, health
- HIV/AIDS education and awareness materials

### Teaching Materials
- **Color**: Muted Gold (#b88a2e)
- **Icon**: Chalkboard teacher
- **Keywords**: teaching, lesson, educator
- Lesson plans, teaching guides, and educator resources

### Policy Documents
- **Color**: Lighter Slate (#4a5a74)
- **Icon**: Gavel
- **Keywords**: policy, regulation, guideline
- Educational policies, regulations, and guidelines

## Configuration

### Block Settings

1. **Block Title**: Custom title for the block (default: "Educational Resources")
2. **Introduction Text**: Brief description shown below title
3. **Display Mode**: Choose between Grid, Carousel, or List
4. **Resources to Display**:
   - All Resource Types
   - Documents Only (CAPS, Policy)
   - Materials Only (Books, Teaching)
5. **Show Content Count**: Toggle resource counts
6. **Show Featured Item**: Toggle featured/recent items

### Colors

Each resource type uses colors from the SAHO heritage palette:
- Deep Heritage Red (#990000) - CAPS Documents
- Slate Blue (#3a4a64) - School Books
- Faded Brick Red (#8b2331) - AIDS Resources
- Muted Gold (#b88a2e) - Teaching Materials
- Lighter Slate (#4a5a74) - Policy Documents

## Usage

### Adding the Block

1. Go to **Structure > Block layout**
2. Click **Place block** in desired region
3. Search for "Educational Resources"
4. Configure block settings
5. Save

### Front Page Placement

Recommended regions:
- **Content** - for main content area
- **Sidebar First/Second** - for sidebar placement
- **Footer** - for bottom-of-page educational resources

## Dependencies

- Drupal Core 10/11
- SAHO Utils module
- Content: Article nodes with relevant keywords

## Services Used

This block leverages the SAHO service architecture:
- `ConfigurationFormHelperService` - reusable form elements
- `CacheHelperService` - standardized caching
- `ImageExtractorService` - image handling
- `EntityTypeManager` - entity queries

## Technical Details

### File Structure
```
educational_resources/
├── css/
│   └── educational-resources.css
├── src/
│   └── Plugin/
│       └── Block/
│           └── EducationalResourcesBlock.php
├── templates/
│   └── educational-resources-block.html.twig
├── educational_resources.info.yml
├── educational_resources.libraries.yml
├── educational_resources.module
└── README.md
```

### Caching

- Cache tags: `taxonomy_term_list`, `node_list:article`
- Cache contexts: `languages:language_interface`, `theme`
- Cache max-age: 3600 seconds (1 hour)

### Content Discovery

Resources are discovered by searching article titles for relevant keywords:
- CAPS Documents: "CAPS", "curriculum", "assessment policy"
- School Books: "book", "textbook", "reading"
- AIDS Resources: "AIDS", "HIV", "health"
- Teaching Materials: "teaching", "lesson", "educator"
- Policy Documents: "policy", "regulation", "guideline"

## Customization

### Custom Colors

To override resource colors, add to your theme CSS:
```css
.resource-card.resource-caps { --resource-color: #YOUR-COLOR; }
```

### Custom Templates

Copy `educational-resources-block.html.twig` to your theme's templates directory and customize.

### Adding Resource Types

To add new resource types, edit the `RESOURCE_TYPES` constant in `EducationalResourcesBlock.php`:

```php
'new_type' => [
  'label' => 'New Resource Type',
  'description' => 'Description of this resource type',
  'keywords' => ['keyword1', 'keyword2', 'keyword3'],
  'icon' => 'fa-icon-name',
  'color' => '#HEXCOLOR',
],
```

## Accessibility

- All images/icons have alt text
- Proper heading hierarchy
- Keyboard navigable
- ARIA labels for carousel controls
- High contrast color combinations
- Focus indicators

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Version

1.0 - Initial release

## Author

SAHO Development Team

## License

GPL-2.0+
