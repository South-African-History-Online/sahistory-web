# SAHO Critical CSS Files

This directory contains critical CSS files that are automatically inlined by the Critical CSS module for improved performance.

## File Structure

- `default-critical.css` - Default critical CSS for all pages
- `page.css` - Generic page-specific critical CSS 
- `article.css` - Article pages critical CSS
- `biography.css` - Biography pages critical CSS  
- `front.css` - Homepage critical CSS

## How It Works

The Critical CSS module automatically:

1. Checks the current page type/content
2. Looks for a matching CSS file in this directory
3. Inlines the CSS in the `<head>` for immediate rendering
4. Loads the rest of the CSS asynchronously

## Naming Convention

Files should match:
- Bundle type (e.g., `article.css`, `page.css`)
- Entity ID (e.g., `123.css`)
- URL alias (e.g., `about-us.css`)
- Default fallback: `default-critical.css`

## Performance Impact

Critical CSS reduces:
- First Contentful Paint (FCP)
- Largest Contentful Paint (LCP) 
- Cumulative Layout Shift (CLS)
- Render-blocking resources

## Configuration

Module settings: `/admin/config/development/performance/critical-css`

## Regenerating Critical CSS

To generate new critical CSS:

1. Use tools like Addy Osmani's `critical` package
2. Or online generators like https://www.sitelocity.com/critical-path-css-generator
3. Extract above-the-fold CSS for each page type
4. Save to appropriate filename in this directory