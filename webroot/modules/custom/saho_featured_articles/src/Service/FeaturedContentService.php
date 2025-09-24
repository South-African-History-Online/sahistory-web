<?php

namespace Drupal\saho_featured_articles\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service for managing featured content queries and operations.
 */
class FeaturedContentService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;


  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Feature field mappings.
   *
   * @var array
   */
  protected $fieldMappings = [
    'staff-picks' => 'field_staff_picks',
    'home-features' => 'field_home_page_feature',
    'most-read' => 'field_most_read',
    'africa-section' => 'field_home_page_feature_africa_s',
    'politics-society' => 'field_home_page_politics_and_soc',
    'timelines' => 'field_home_page_feature_timeline',
  ];

  /**
   * Constructs a FeaturedContentService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->configFactory = $config_factory;
  }

  /**
   * Get all featured content using entity query with conditional groups.
   *
   * @param int $limit
   *   Maximum number of items to return.
   *
   * @return \Drupal\node\Entity\Node[]
   *   Array of loaded node entities.
   */
  public function getAllFeaturedContent($limit = 50) {

    try {
      $query = $this->entityTypeManager->getStorage('node')->getQuery();

      // Create OR condition group for all featured fields.
      $orGroup = $query->orConditionGroup();
      foreach ($this->fieldMappings as $field_name) {
        $orGroup->condition($field_name, 1);
      }

      $query->condition($orGroup);
      $query->condition('status', 1);
      $query->accessCheck(TRUE);
      $query->sort('changed', 'DESC');
      $query->range(0, $limit);

      $nids = $query->execute();

      return $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Get content for a specific featured section.
   *
   * @param string $section
   *   The section name.
   * @param int $limit
   *   Maximum number of items to return.
   *
   * @return \Drupal\node\Entity\Node[]
   *   Array of loaded node entities for the section.
   */
  public function getSectionContent($section, $limit = 8) {

    if (!isset($this->fieldMappings[$section])) {
      return [];
    }

    try {
      $field_name = $this->fieldMappings[$section];

      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->condition($field_name, 1);
      $query->condition('status', 1);
      $query->accessCheck(TRUE);
      $query->sort('changed', 'DESC');
      $query->range(0, $limit);

      $nids = $query->execute();

      return $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Get section content count.
   *
   * @param string $section
   *   The section name.
   *
   * @return int
   *   The number of items in the section.
   */
  public function getSectionCount($section) {
    if (!isset($this->fieldMappings[$section])) {
      return 0;
    }

    try {
      $field_name = $this->fieldMappings[$section];

      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->condition($field_name, 1);
      $query->condition('status', 1);
      $query->accessCheck(TRUE);
      $query->count();

      $count = $query->execute();

      return $count;
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Get available field mappings.
   *
   * @return array
   *   Array of section => field mappings.
   */
  public function getFieldMappings() {
    return $this->fieldMappings;
  }

}
