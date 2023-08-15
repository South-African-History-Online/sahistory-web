# About Media Bulk Upload

This is the Drupal 8 module to bulk upload files and create the media
entities automatically for them. It uses [DropzoneJS](http://www.drupal.org/project/dropzonejs)
to quickly upload multiple files.

## How to install:
1. Download this module, and [DropzoneJS](http://www.drupal.org/project/dropzonejs).
2. Make sure you follow the install instructions of DropzoneJS.
3. Install media_bulk_upload the [usual way](https://www.drupal.org/documentation/install/modules-themes/modules-8).

You will now have the media bulk upload configuration at your disposal.
Just go to the ``` /admin/config/media/media-bulk-config ``` page and
configure a media bulk upload form. After creating the configuration you
can go to `` /admin/content/media/media-bulk-config `` and use your configured
form.

Maximum file size is currently determined based on the selected media
type with the highest maximum upload size.

To configure the fields that are shown on the bulk upload form for the
media, just configure the form mode chosen in your configuration for
each media type. It has a fallback to the default form if the selected
mode is not found in one of the media types. The fields are then shown
based on the fact that they are shared between all the selected media
types in the configured bulk upoad form.

## Future plans:
- Test coverage.
- Max file size validation handling per media type on upload.
- Handling other types of upload validations
(min/max resolution, min size,...).

## Project page:
[drupal.org project page](https://www.drupal.org/project/media_bulk_upload)

## Maintainers:
+ Jeffrey Bertoen (@jbertoen) drupal.org/u/jbertoen
+ Marnix van Balgooi (@mirnaxvb) drupal.org/u/mirnaxvb
