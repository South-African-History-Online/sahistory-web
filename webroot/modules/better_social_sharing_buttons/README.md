INTRODUCTION
------------
Allows you to add social sharing buttons to your website.

This module generates a block, a node field, and a paragraph field so you can
choose how and where you add the social sharing buttons on your website.

Why yet another module for social sharing buttons? Because most or even all
existing modules seem to make a lot of external calls, have tracker scripts, or
even connect to ad servers. This is a clean solution without any of that
bloatware.

Most social platforms have a sharing link you can use to share content. The
buttons of this module directly open those links in a new tab without having to
call any other api, service, website, or script. This provides for a very clean
and fast bloatware free solution.

If any of those services make changes to their url's then yes, this module will
need to be updated as well. Simply create an issue if you find a sharing button
no longer working and it will be updated as soon as possible.

There is a settings form where you can set which services you would like to use:
- Facebook
- Twitter
- Whatsapp
- Facebook Messenger (requires a Facebook App ID)
- Email
- Pinterest
- Linkedin
- Digg
- Slashdot
- Tumblr
- Reddit
- Evernote
- Print (requires print css file)


You can also adjust these settings for the icons:
- size
- border-radius

And there are 2 icon sets to choose from:
- Colored square icons (you can adjust the border radius, even making them
rounded by setting border radius to 100%)
- Flat icons no color or background, (you can give these any color you want by
using the fill property via css)

All svgs are minified and the module uses an svg sprite so there is only a one
time resource load needed to further decrease the (already small) resource
footprint of this module.

REQUIREMENTS
------------

This module has no module requirements to work, but:
- It shares node title and url, so use it on node entities
- If you want to add the buttons via a field, you must enable the field in
the configuration. The field display will use the set configuration values.
- You can easily place the block in any node twig file using twig_tweak module
(see instructions below)

INSTALLATION
-----------
- require the repository:
```
composer require drupal/better_social_sharing_buttons --prefer-dist
```
- enable the module:
```
drush en better_social_sharing_buttons -y
```

CONFIGURATION
--------------
- modify default/global settings at admin/config/services/better_social_sharing_buttons/config
- place the buttons where you want using the block, node field, paragraph field
or directly in a twig
  file (see description below)

Add social sharing buttons via twig (Twig Tweak module v2.0 or higher)
---

Twig Tweak version 2.0 and above can print blocks that are not instantiated by
using the block id:

```{{ drupal_block("social_sharing_buttons_block") }}```

Add social sharing buttons via twig (Twig Tweak module v1.9 or lower)
---
If you use a version of Twig Tweak below 2.0 (like 1.9) then you cannot print a
block that is not instantiated. The block must be enabled somewhere in
structure/block.

You can for example create a region 'hidden' in your theme which you render
nowhere and place the block in there. Once there is an instance of the block you
can place it anywhere in any twig file using:

```{{ drupal_block("bettersocialsharingbuttons") }}```

Add social sharing buttons via a block
--

In admin/block you can add a block (Better Social Sharing Buttons block)

Add social sharing buttons via a field
---

This module also provides a field (Better Social Sharing Buttons field) through
a pseudo field. To see this field, you must enable the feature in the
configuration and then adjust the display mode of your nodes. When the feature
is enabled, the field is enabled for all content types. You will need to adjust
it for each content type as desired.

Upgrade from 2.x version
--

If you used the Display Suite field, you will need to enable the field feature
and then adjust as needed. There is no direct migration of the Display Suite
settings. The Display Suite field is no longer supported in version 3.0 and
greater.
