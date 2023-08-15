<?php

namespace Drupal\media_entity_bulk_upload\Utility;

/**
 * Helper class to grab media field information.
 */
class FieldUtility {

  /**
   * Get media entity bundles.
   */
  public static function getMediaFieldBundles() {
    $field_map = \Drupal::entityManager()->getFieldMap();
    $media_field_map = $field_map['media'];
    $bundle_array = [];
    foreach ($media_field_map as $field_key => $field) {
      if (isset($field['bundles'])) {
        foreach ($field['bundles'] as $bundle_name) {
          $bundle_array[$bundle_name] = $bundle_name;
        }
      }
    }
    return $bundle_array;
  }

  /**
   * Get all possible media entity image fields.
   */
  public static function getMediaImageFields() {
    $field_map = \Drupal::entityManager()->getFieldMap();
    $media_field_map = $field_map['media'];
    $field_array = [];
    foreach ($media_field_map as $field_key => $field) {
      if (isset($field['type']) && $field['type'] === "image" && $field_key !== "thumbnail") {
        $field_array[$field_key] = $field_key;
      }
    }
    return $field_array;
  }

}
