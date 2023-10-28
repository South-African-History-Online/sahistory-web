# Migrate File (extended)

Defines additional migrate plugins for importing files.

These plugins are meant to facilitate importing files in the same migration as other data. Typically, with a D7 to D8 migration for example, you would first migrate all your files in a dedicated files migration, and then your nodes after so they can reference the already migrated files. But what if you're just importing nodes from a CSV or external non-drupal API where some of the fields are external file paths? These plugins make it so the files can be imported in the same migration as the rest of the data.

## Plugins Provided

### File Import

Imports a file from an local or external source. Files will be downloaded or copied from the source if necessary and a file entity will be created for it. The file can be moved, reused, or set to be automatically renamed if a duplicate exists.

### Image Import

Imports an image from an local or external source. Extends the file_import plugin (described above) and adds the following additional optional configuration keys for the image alt, title, width and height attributes.

### Remote File Url / Remote Image

Create a file entity with a remote url (i.e. without downloading the file). It is assumed if you're using this process plugin that you have something in place to properly handle the external uri on the file object (e.g. the Remote Stream Wrapper module).

*For all the plugins, see the corresponding plugin class file for a detailed description of the config and examples.*
