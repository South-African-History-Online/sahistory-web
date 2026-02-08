# South African Provinces Block

A visual, educational block that showcases South Africa's 9 provinces with alphabetical organization for viewing places, cities, towns, and historical sites.

## Features

- **9 Province Cards**: All South African provinces displayed alphabetically
- **Place Counts**: Shows number of places available per province
- **Featured Places**: Displays a featured place for each province
- **Multiple Display Modes**:
  - Grid (default) - responsive card grid
  - Carousel - mobile-friendly slideshow
  - List - stacked vertical layout
- **SAHO Heritage Colors**: Professional color palette for each province
- **Province Abbreviations**: Visual letter badges (EC, FS, GP, KZN, LP, MP, NW, NC, WC)
- **Fully Responsive**: Optimized for desktop, tablet, and mobile
- **Accessible**: WCAG 2.1 AA compliant with proper ARIA labels and keyboard navigation

## Provinces (Alphabetical Order)

### Eastern Cape (EC)
- **Color**: Deep Heritage Red (#990000)
- **Description**: Birthplace of many anti-apartheid leaders

### Free State (FS)
- **Color**: Dark Red (#8B0000)
- **Description**: Heart of South Africa's goldfields

### Gauteng (GP)
- **Color**: Muted Gold (#b88a2e)
- **Description**: Economic hub and Johannesburg

### KwaZulu-Natal (KZN)
- **Color**: Slate Blue (#3a4a64)
- **Description**: Rich Zulu heritage and coastal beauty

### Limpopo (LP)
- **Color**: Lighter Slate (#4a5a74)
- **Description**: Ancient kingdoms and Mapungubwe

### Mpumalanga (MP)
- **Color**: Lighter Gold (#c89a3e)
- **Description**: Land of the rising sun

### North West (NW)
- **Color**: Faded Brick Red (#8b2331)
- **Description**: Cradle of humankind region

### Northern Cape (NC)
- **Color**: Firebrick Red (#B22222)
- **Description**: Diamond fields and vast landscapes

### Western Cape (WC)
- **Color**: Dark Gold (#8B6914)
- **Description**: Cape of Good Hope and rich colonial history

## Configuration

### Block Settings

1. **Block Title**: Custom title for the block (default: "South African Provinces")
2. **Introduction Text**: Brief description shown below title
3. **Display Mode**: Choose between Grid, Carousel, or List
4. **Show Place Count**: Toggle place counts
5. **Show Featured Place**: Toggle featured places

## Usage

### Adding the Block

1. Go to **Structure > Block layout**
2. Click **Place block** in desired region
3. Search for "South African Provinces"
4. Configure block settings
5. Save

### Front Page Placement

Recommended regions:
- **Content** - for main content area
- **Sidebar First/Second** - for sidebar placement
- **Footer** - for bottom-of-page exploration

## Dependencies

- Drupal Core 10/11
- SAHO Utils module
- Taxonomy: field_places_level3 vocabulary
- Content: Place nodes with province references

## Services Used

This block leverages the SAHO service architecture:
- `ConfigurationFormHelperService` - reusable form elements
- `CacheHelperService` - standardized caching
- `EntityTypeManager` - entity queries

## Technical Details

### File Structure
```
sa_provinces/
├── css/
│   └── sa-provinces.css
├── src/
│   └── Plugin/
│       └── Block/
│           └── SaProvincesBlock.php
├── templates/
│   └── sa-provinces-block.html.twig
├── sa_provinces.info.yml
├── sa_provinces.libraries.yml
├── sa_provinces.module
└── README.md
```

### Caching

- Cache tags: `taxonomy_term_list:field_places_level3`, `node_list:place`
- Cache contexts: `languages:language_interface`, `theme`
- Cache max-age: 3600 seconds (1 hour)

### Province Lookup

Provinces are loaded from the `field_places_level3` taxonomy vocabulary by matching province names. Place nodes are counted by checking the `field_places_level3` field reference.

## Customization

### Custom Colors

To override province colors, add to your theme CSS:
```css
.province-card.province-gauteng { --province-color: #YOUR-COLOR; }
```

### Custom Templates

Copy `sa-provinces-block.html.twig` to your theme's templates directory and customize.

### Changing Province Order

Provinces are displayed in the order defined in the `PROVINCES` constant in `SaProvincesBlock.php`. To change the order, reorder the array keys.

## Accessibility

- All icons have proper ARIA labels
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

## Integration

### Search Integration

Province cards link to `/places?province=[Province Name]`. Ensure your places view or search page accepts this parameter.

### Content Types

The block expects:
- **Content Type**: place
- **Field**: field_places_level3 (taxonomy reference to provinces)

## Version

1.0 - Initial release

## Author

SAHO Development Team

## License

GPL-2.0+
