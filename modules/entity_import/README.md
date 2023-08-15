Entity Importer
===========

The entity importer module provides a user interface for site administrators to import various entities. At the moment the module only support CSV files as the source, but due to the plugin type architecture this can easily be expanded to other file formats. There are also plans in the road-map to support other common source types moving forward.

The entity importer module was built on top of the migration API. Importers are able to support migration dependencies within one interface, this allows site administrators to manage all related imports without having to navigate to multiple screens. The module exposes a robust configuration interface, which allows a user to point-and-click to build a simple or complex importer. This includes field mappings and data processing/transformation (migration lookups, entity lookup, string replacement, etc.), which you would expect if you were writing a custom migration using YAML.
 
Installation
------------

* Normal module installation procedure. See
  https://www.drupal.org/documentation/install/modules-themes/modules-8
  
Initial Setup
------------

After the installation, you'll need to create an entity importer.

* Navigate to the entity importer page `/admin/config/system/entity-importer`.

* Click the `Add importer` action button (e.g. `/admin/config/system/entity-importer/add`).

* Input a label and description if needed.

* You'll need to check the `Display page` checkbox to display the import page. In most cases this is needed, unless the importer is referenced from a migration lookup. 

* Next, you'll need to configure the source and entity information. Currently only the CSV source is available. You can choose from a variety of entity types and bundles.

* (optional) You can also relate the importer to other migrations. This option is needed more when setting up a complex importer that relies on other migrations to be ran prior.

* Click save.

Next, you'll need to assign field mappings to the newly created entity importer.

* Navigate to the entity importer `Field Mapping` tab.

* Click `Add mapping`

* You'll need to define a source label, which creates a source name. This source name needs to relate to one of the CSV headers (if using the entity importer CSV source plugin).

* If you selected multiple bundles for the entity importer you'll need to select a target bundle. If not, then this input is hidden and is not required.

* Now you'll need to define a field destination. This can either be a field name or property within a field.

* If you need to do any data processing/transformation on the source data. Then, select the process plugin from the options (if you need to select multiple then cmd click). Fill out all required data processing inputs for each plugin. Next, arrange the data processes in the order you would like them executed.

* Click save, and repeat until all field mappings have been defined for the entity importer.

* You'll need to define at least one unique identifier. Which can be accomplished by clicking `Add identifier` from the option form on the `Field Mapping` tab.

Finally, if you've checked the `Display page` checkbox option on the entity importer configuration. You'll be able to navigate to the import page located at `/admin/content/importer-pages`.

* Click on the entity importer that you've created.

* (optional) If you've configured the entity importer to support multiple entity bundles, you'll have to choose an import bundle from the dropdown; otherwise this is hidden.

* Upload the required file(s) for the entity importer.

* (optional) If you've already uploaded data. Then you can check the `Update` checkbox, which will update the related entities with the changes defined in the source data.

* Click `Import`.

If anything goes wrong with the entity importer you can rollback the entities from the action tab, and/or review the log message(s) to gain more insight on what caused the issue.
