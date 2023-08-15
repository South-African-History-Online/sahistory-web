<?php

namespace Drupal\custom_geolocation_migrate\plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
* Geolocation field migration.
*
* @MigrateField(
*   id = "geolocation",
*   type_map = {
*     "geolocation_latlng" = "geolocation",
*   },
*   core = {7},
*   source_module = "geolocation",
*   destination_module = "geolocation"
* )
*/
class Geolocation extends FieldPluginBase {

/**
* {@inheritdoc}
*/
  public function getFieldFormatterMap() {
    return [
      'geolocation_text' => 'geolocation_token',
      'geolocation_latitude' => 'geolocation_latlng',
      'geolocation_longitude' => 'geolocation_latlng',
      'geolocation_googlemaps_static' => 'geolocation_map',
      'geolocation_googlemaps_dynamic' => 'geolocation_map',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'geolocation_latlng' => 'geolocation_latlng',
      'geolocation_googlemap' => 'geolocation_googlegeocoder',
    ];
  }

}