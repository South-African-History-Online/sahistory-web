<?php

namespace Drupal\custom_url_migrate\plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 * id = "url",
 * core = {7},
 * type_map = {
 * "url" = "link"
 * },
 * source_module = "url",
 * destination_module = "link"
 * )
 */
class url extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'url' => 'link',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'default' => 'link',
      'url' => 'link',
      'url_external' => 'link',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'uri' => 'value',
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}