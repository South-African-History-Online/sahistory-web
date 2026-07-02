# SAHO Chrome

Open Record site chrome support for the main site (sahistory.org.za).

Provides the `saho_primary` menu with its six navigation links defined in
code (`saho_chrome.links.menu.yml`), so the primary nav is `cim`-safe,
reviewable in git, and reversible by uninstalling the module. Editors do
not reorder these links through the UI by design.

The `saho` theme header and footer render this menu via
`drupal_menu('saho_primary')`.

Enable on the MAIN site only - the shop runs its own theme and chrome:

```
ddev drush en saho_chrome -y
```
