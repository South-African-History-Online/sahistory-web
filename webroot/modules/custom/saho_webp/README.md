# SAHO WebP Module

Automatic WebP image conversion module for the South African History Online (SAHO) website.

## Overview

This module automatically converts uploaded JPEG and PNG images to WebP format to improve website performance and reduce bandwidth usage. It provides seamless WebP conversion without affecting existing functionality.

## Features

- **Automatic Conversion**: Converts images to WebP on upload via `hook_file_insert` and `hook_file_update`
- **Non-Destructive**: Keeps original images intact while creating WebP versions
- **Smart Naming**: Creates correctly named WebP files (e.g., `image.jpg` → `image.webp`)
- **Quality Optimization**: Uses 80% quality setting for optimal file size/quality balance
- **Error Handling**: Gracefully handles conversion failures without breaking uploads
- **Drush Commands**: Provides command-line tools for bulk operations

## Installation

1. Enable the module:
   ```bash
   drush en saho_webp
   ```

2. Clear caches:
   ```bash
   drush cache:rebuild
   ```

## Usage

### Automatic Conversion

Once enabled, the module automatically converts new image uploads:

- Supports JPEG and PNG formats
- Creates WebP versions alongside originals
- Works with all file upload fields
- Preserves file permissions

### Drush Commands

#### Convert All Images
```bash
# Convert all existing images to WebP
drush saho:webp-convert
drush swc  # Short alias
```

#### Fix Naming Issues
```bash
# Fix WebP files with double extensions
drush saho:webp-fix
drush swf  # Short alias
```

#### Check Status
```bash
# View conversion statistics
drush saho:webp-status
drush sws  # Short alias
```

## Configuration

The module works out-of-the-box with sensible defaults:

- **Quality**: 80% (good balance of file size and quality)
- **Formats**: JPEG, PNG → WebP
- **Transparency**: Preserved for PNG images
- **Error Handling**: Silent failures (won't break uploads)

## Performance Impact

Based on SAHO's conversion results:
- **97.1% success rate** (54,722 of 56,332 images converted)
- **68.4% bandwidth savings** on average
- **Automatic serving** via .htaccess rules when browser supports WebP

## Technical Details

### File Structure
```
saho_webp/
├── saho_webp.info.yml
├── saho_webp.module
├── src/
│   └── Commands/
│       └── WebpCommands.php
└── README.md
```

### Key Functions

- `saho_webp_file_insert()`: Converts images on new uploads
- `saho_webp_file_update()`: Converts images on file updates
- `saho_webp_convert_file()`: Core conversion logic
- `saho_webp_supports_webp()`: Checks GD WebP support

### Dependencies

- **PHP GD Extension**: With WebP support
- **Drupal Core**: File API
- **ImageMagick** (optional): Alternative to GD

## Troubleshooting

### Common Issues

1. **WebP not supported**: Ensure PHP GD has WebP support
   ```bash
   php -m | grep -i gd
   php -r "var_dump(function_exists('imagewebp'));"
   ```

2. **Permissions errors**: Check file system permissions
   ```bash
   ls -la sites/default/files/
   ```

3. **Memory issues**: Increase PHP memory limit for large images
   ```php
   ini_set('memory_limit', '512M');
   ```

### Debugging

Check conversion status:
```bash
drush saho:webp-status
```

Review recent log entries:
```bash
drush watchdog:show --type=saho_webp
```

## Integration

### Theme Integration

The module works automatically with:
- Image styles
- Media entities  
- File fields
- Image formatters

### .htaccess Rules

Ensure your .htaccess includes WebP serving rules:
```apache
# WebP Auto-serving
RewriteCond %{HTTP_ACCEPT} image/webp
RewriteCond %{REQUEST_URI} \.(jpe?g|png)$ [NC]
RewriteCond %{REQUEST_FILENAME} ^(.+)\.(jpe?g|png)$
RewriteCond %1.webp -f
RewriteRule ^(.+)\.(jpe?g|png)$ $1.webp [T=image/webp,E=accept:1,L]
```

## Contributing

When contributing to this module:

1. Follow Drupal coding standards
2. Test with various image formats and sizes
3. Ensure backward compatibility
4. Update documentation for new features

## Support

For issues or questions:
- Check the module's issue queue
- Review Drupal logs for error messages
- Test with different image formats
- Verify server WebP support

## License

This module is licensed under the GPL-2.0-or-later license, same as Drupal core.