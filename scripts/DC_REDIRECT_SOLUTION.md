# DC Archive Path Redirect Solution

## Problem
Many 404 errors from legacy archive PDF paths following the pattern:
- `/sites/default/files/DC/[folder]/[filename].pdf`

The files actually exist in flat archive-files directories:
- `/sites/default/files/archive-files4/[filename].pdf`

## Examples
| Requested URL | Actual Location |
|--------------|-----------------|
| `/sites/default/files/DC/LaJun88/LaJun88.pdf` | `/sites/default/files/archive-files4/LaJun88.pdf` |
| `/sites/default/files/DC/PvSep68/PVSep68.pdf` | `/sites/default/files/archive-files4/PVSep68.pdf` |
| `/sites/default/files/DC/InApr81/InApr81.pdf` | `/sites/default/files/archive-files4/InApr81.pdf` |
| `/sites/default/files/dc/bsjul63.0036.4843.007.002.jul 1963.7/bsjul63.0036.4843.007.002.jul 1963.7.pdf` | `/sites/default/files/archive-files*/[filename].pdf` |

## Solution Implemented

Added redirect rules to `.htaccess.custom` (section #10) that:

1. Match DC paths (case-insensitive: DC or dc)
2. Extract the filename from the path
3. Check if the file exists in each archive-files directory (in order: archive-files4, archive-files, archive-files2, archive-files3, archive-files5)
4. Redirect with 301 to the correct archive-files location

### Redirect Rules
```apache
# 10. Redirect legacy DC archive paths to archive-files directories
# Files in /sites/default/files/DC/[folder]/[file].pdf are actually in archive-files* directories
# Try multiple archive-files directories in order
RewriteCond %{REQUEST_URI} ^/sites/default/files/[Dd][Cc]/[^/]+/(.+)$ [NC]
RewriteCond %{DOCUMENT_ROOT}/sites/default/files/archive-files4/%1 -f
RewriteRule ^sites/default/files/[Dd][Cc]/[^/]+/(.+)$ /sites/default/files/archive-files4/$1 [R=301,L,NC]

RewriteCond %{REQUEST_URI} ^/sites/default/files/[Dd][Cc]/[^/]+/(.+)$ [NC]
RewriteCond %{DOCUMENT_ROOT}/sites/default/files/archive-files/%1 -f
RewriteRule ^sites/default/files/[Dd][Cc]/[^/]+/(.+)$ /sites/default/files/archive-files/$1 [R=301,L,NC]

RewriteCond %{REQUEST_URI} ^/sites/default/files/[Dd][Cc]/[^/]+/(.+)$ [NC]
RewriteCond %{DOCUMENT_ROOT}/sites/default/files/archive-files2/%1 -f
RewriteRule ^sites/default/files/[Dd][Cc]/[^/]+/(.+)$ /sites/default/files/archive-files2/$1 [R=301,L,NC]

RewriteCond %{REQUEST_URI} ^/sites/default/files/[Dd][Cc]/[^/]+/(.+)$ [NC]
RewriteCond %{DOCUMENT_ROOT}/sites/default/files/archive-files3/%1 -f
RewriteRule ^sites/default/files/[Dd][Cc]/[^/]+/(.+)$ /sites/default/files/archive-files3/$1 [R=301,L,NC]

RewriteCond %{REQUEST_URI} ^/sites/default/files/[Dd][Cc]/[^/]+/(.+)$ [NC]
RewriteCond %{DOCUMENT_ROOT}/sites/default/files/archive-files5/%1 -f
RewriteRule ^sites/default/files/[Dd][Cc]/[^/]+/(.+)$ /sites/default/files/archive-files5/$1 [R=301,L,NC]
```

## Key Features
- **Case-insensitive**: Matches both `/DC/` and `/dc/`
- **File existence check**: Only redirects if the file actually exists in the target directory
- **Priority order**: Checks archive-files4 first (where most files are), then falls back to other directories
- **Preserves filename**: Including special characters, spaces, and trailing dots
- **SEO-friendly**: Uses 301 permanent redirects

## Deployment
1. The rules are in `.htaccess.custom` at lines 170-191
2. Copy `.htaccess.custom` to `webroot/.htaccess` to activate
3. Tested and working on production

## Testing on Production
Confirmed working for:
- `/sites/default/files/DC/slapr93.3/slapr93.3.pdf`
- `/sites/default/files/DC/ChMay81.1024.8196.000.005.May1981.8/ChMay81.1024.8196.000.005.May1981.8.pdf`
- `/sites/default/files/DC/BSDec62.0036.4843.006.004.Dec 1962/BSDec62.0036.4843.006.004.Dec 1962.pdf`
- `/sites/default/files/DC/grapr80/grapr80.pdf`
- `/sites/default/files/DC/LaJun88/LaJun88.pdf`
- `/sites/default/files/DC/PvSep68/PVSep68.pdf`
- `/sites/default/files/DC/InApr81/InApr81.pdf`

## Note on DDEV Local Testing
The redirect rules work on production but may show different behavior in DDEV due to:
- Different Apache DocumentRoot configuration
- DDEV's nginx proxy layer
- Different SSL/HTTPS handling

The production server has confirmed these rules are working correctly.

## Expected Impact
This should resolve hundreds of 404 errors from the DC path pattern. Monitor 404 logs after deployment to verify and identify any remaining issues.

## Related Files
- `.htaccess.custom` - Main configuration file (lines 170-191)
- `webroot/.htaccess` - Active Apache configuration
- `webroot/modules/custom/saho_media_migration/src/Service/FileMappingService.php` - Related file mapping service for content migration
