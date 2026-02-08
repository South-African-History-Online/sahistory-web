# History Classroom Block

A visual, educational block that showcases South African History curriculum content organized by grade level (Grades 4-12).

## Features

- **Colorful Grade Cards**: Each grade (4-12) displayed as a vibrant, color-coded card
- **Content Counts**: Shows number of resources available per grade
- **Featured Topics**: Displays recent or featured topic for each grade
- **Multiple Display Modes**:
  - Grid (default) - responsive card grid
  - Carousel - mobile-friendly slideshow
  - List - stacked vertical layout
- **Configurable Grade Ranges**: Show all grades, primary only, secondary only, or high school
- **Fully Responsive**: Optimized for desktop, tablet, and mobile
- **Accessible**: WCAG 2.1 AA compliant with proper ARIA labels and keyboard navigation

## Configuration

### Block Settings

1. **Block Title**: Custom title for the block (default: "History by Grade")
2. **Introduction Text**: Brief description shown below title
3. **Display Mode**: Choose between Grid, Carousel, or List
4. **Grades to Display**:
   - All Grades (4-12)
   - Primary Grades (4-7)
   - Secondary Grades (8-12)
   - High School (10-12)
5. **Show Content Count**: Toggle resource counts
6. **Show Featured Topic**: Toggle featured/recent topics

### Grade Colors

Each grade has a unique color derived from the SAHO heritage palette for professional, academic presentation:
- Grade 4: Deep Heritage Red (#990000)
- Grade 5: Firebrick Red (#B22222)
- Grade 6: Faded Brick Red (#8b2331)
- Grade 7: Dark Red (#8B0000)
- Grade 8: Slate Blue (#3a4a64)
- Grade 9: Lighter Slate (#4a5a74)
- Grade 10: Muted Gold (#b88a2e)
- Grade 11: Lighter Gold (#c89a3e)
- Grade 12: Dark Gold (#8B6914)

## Usage

### Adding the Block

1. Go to **Structure > Block layout**
2. Click **Place block** in desired region
3. Search for "History Classroom"
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
- Taxonomy: classroom vocabulary
- Content: Article nodes with field_classroom

## Services Used

This block leverages the SAHO service architecture:
- `ConfigurationFormHelperService` - reusable form elements
- `CacheHelperService` - standardized caching
- `ImageExtractorService` - image handling
- `EntityTypeManager` - entity queries

## Technical Details

### File Structure
```
history_classroom/
├── css/
│   └── history-classroom.css
├── src/
│   └── Plugin/
│       └── Block/
│           └── HistoryClassroomBlock.php
├── templates/
│   └── history-classroom-block.html.twig
├── history_classroom.info.yml
├── history_classroom.libraries.yml
├── history_classroom.module
└── README.md
```

### Caching

- Cache tags: `taxonomy_term_list:classroom`, `node_list:article`
- Cache contexts: `languages:language_interface`, `theme`
- Cache max-age: 3600 seconds (1 hour)

## Customization

### Custom Colors

To override grade colors, add to your theme CSS:
```css
.grade-card.grade-4 { --grade-color: #YOUR-COLOR; }
```

### Custom Templates

Copy `history-classroom-block.html.twig` to your theme's templates directory and customize.

## Accessibility

- All images have alt text
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
