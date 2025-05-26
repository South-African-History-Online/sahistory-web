<?php

namespace Drupal\saho_tools\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Service for generating citations.
 */
class CitationService {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a CitationService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Generates citations for a node in different formats.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to generate citations for.
   *
   * @return array
   *   An array of citations in different formats.
   */
  public function generateCitations(NodeInterface $node) {
    // Extract node data.
    $data = $this->extractNodeData($node);

    // Generate citations in different formats.
    return [
      'harvard' => $this->generateHarvardCitation($data),
      'apa' => $this->generateApaCitation($data),
      'chicago' => $this->generateChicagoCitation($data),
    ];
  }

  /**
   * Extracts relevant data from a node for citation generation.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to extract data from.
   *
   * @return array
   *   An array of node data for citation generation.
   */
  public function extractNodeData(NodeInterface $node) {
    // Get the node title.
    $title = $node->getTitle();

    // Get the node URL.
    $url = $node->toUrl()->setAbsolute()->toString();

    // Get the node creation date.
    $created = $node->getCreatedTime();
    $created_date = DrupalDateTime::createFromTimestamp($created);
    $created_year = $created_date->format('Y');
    $created_formatted = $created_date->format('F j, Y');

    // Always use South African History Online (SAHO) as the author.
    $author = 'South African History Online (SAHO)';

    // Get the current date for "accessed on" part of citations.
    $current_date = new DrupalDateTime();
    $accessed_date = $current_date->format('F j, Y');
    
    // Get image information if available.
    $image_info = $this->extractImageData($node);
    
    // Get content type specific information.
    $content_type_info = $this->getContentTypeSpecificInfo($node);

    // Return the extracted data.
    return [
      'title' => $title,
      'url' => $url,
      'created_year' => $created_year,
      'created_formatted' => $created_formatted,
      'author' => $author,
      'accessed_date' => $accessed_date,
      'site_name' => 'South African History Online',
      'image_info' => $image_info,
      'content_type_info' => $content_type_info,
    ];
  }
  
  /**
   * Extracts image data from a node for citation generation.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to extract image data from.
   *
   * @return array
   *   An array of image data for citation generation.
   */
  protected function extractImageData(NodeInterface $node) {
    $image_info = [
      'has_image' => FALSE,
      'image_title' => '',
      'image_alt' => '',
      'image_url' => '',
      'photographer' => '',
      'copyright' => '',
    ];
    
    // Check for common image field names.
    $image_field_names = [
      'field_image',
      'field_article_image',
      'field_bio_pic',
      'field_place_image',
      'field_event_image',
      'field_tdih_image',
      'field_archive_image',
    ];
    
    foreach ($image_field_names as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        $image_field = $node->get($field_name);
        $image_info['has_image'] = TRUE;
        
        // Get image entity if it's a reference.
        if ($image_field->entity) {
          $image_entity = $image_field->entity;
          
          // Handle Media entity.
          if ($image_entity->getEntityTypeId() === 'media') {
            if ($image_entity->hasField('field_media_image') && !$image_entity->get('field_media_image')->isEmpty()) {
              $file_entity = $image_entity->get('field_media_image')->entity;
              if ($file_entity) {
                $image_info['image_url'] = $file_entity->createFileUrl(FALSE);
                
                // Get alt and title if available.
                if ($image_entity->hasField('field_media_image')) {
                  $image_info['image_alt'] = $image_entity->get('field_media_image')->alt ?? '';
                  $image_info['image_title'] = $image_entity->get('field_media_image')->title ?? '';
                }
              }
            }
          }
          // Handle File entity directly.
          elseif ($image_entity->getEntityTypeId() === 'file') {
            $image_info['image_url'] = $image_entity->createFileUrl(FALSE);
            
            // Get alt and title if available on the field.
            $image_info['image_alt'] = $image_field->alt ?? '';
            $image_info['image_title'] = $image_field->title ?? '';
          }
        }
        
        // Check for photographer or copyright information.
        if ($node->hasField('field_image_originator') && !$node->get('field_image_originator')->isEmpty()) {
          $image_info['photographer'] = $node->get('field_image_originator')->value;
        }
        
        if ($node->hasField('field_copyright') && !$node->get('field_copyright')->isEmpty()) {
          $image_info['copyright'] = $node->get('field_copyright')->value;
        }
        
        // We found an image, no need to check other fields.
        break;
      }
    }
    
    return $image_info;
  }
  
  /**
   * Gets content type specific information for citation generation.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to get content type specific information from.
   *
   * @return array
   *   An array of content type specific information.
   */
  protected function getContentTypeSpecificInfo(NodeInterface $node) {
    $info = [];
    $node_type = $node->getType();
    
    switch ($node_type) {
      case 'biography':
        if ($node->hasField('field_firstname') && !$node->get('field_firstname')->isEmpty()) {
          $info['first_name'] = $node->get('field_firstname')->value;
        }
        if ($node->hasField('field_lastnamebio') && !$node->get('field_lastnamebio')->isEmpty()) {
          $info['last_name'] = $node->get('field_lastnamebio')->value;
        }
        if ($node->hasField('field_dob') && !$node->get('field_dob')->isEmpty()) {
          $info['birth_date'] = $node->get('field_dob')->value;
        }
        if ($node->hasField('field_dod') && !$node->get('field_dod')->isEmpty()) {
          $info['death_date'] = $node->get('field_dod')->value;
        }
        break;
        
      case 'event':
        if ($node->hasField('field_this_day_in_history_date_2') && !$node->get('field_this_day_in_history_date_2')->isEmpty()) {
          $info['event_date'] = $node->get('field_this_day_in_history_date_2')->value;
        }
        break;
        
      case 'place':
        if ($node->hasField('field_geolocation') && !$node->get('field_geolocation')->isEmpty()) {
          $geolocation = $node->get('field_geolocation')->first();
          $info['latitude'] = $geolocation->lat;
          $info['longitude'] = $geolocation->lng;
        }
        break;
        
      case 'article':
        // Additional article-specific fields.
        if ($node->hasField('field_article_type') && !$node->get('field_article_type')->isEmpty()) {
          $info['article_type'] = $node->get('field_article_type')->value;
        }
        break;
    }
    
    // Add node type to the info array.
    $info['node_type'] = $node_type;
    
    return $info;
  }

  /**
   * Generates a Harvard style citation.
   *
   * @param array $data
   *   The node data for citation generation.
   *
   * @return string
   *   The Harvard style citation.
   */
  protected function generateHarvardCitation(array $data) {
    // Modified Harvard format: Author Title. Available at: URL (Accessed: Date).
    $citation = sprintf(
      '%s <em>%s</em>',
      $data['author'],
      $data['title']
    );
    
    // Add photographer information if available.
    if (!empty($data['image_info']['has_image']) && $data['image_info']['has_image'] && !empty($data['image_info']['photographer'])) {
      $citation .= sprintf(' [Photograph by %s].', $data['image_info']['photographer']);
    }
    
    // Add content type specific information.
    if (!empty($data['content_type_info']['node_type'])) {
      switch ($data['content_type_info']['node_type']) {
        case 'biography':
          if (!empty($data['content_type_info']['birth_date']) && !empty($data['content_type_info']['death_date'])) {
            $citation .= sprintf(' Biography of person (%s-%s).', 
              $data['content_type_info']['birth_date'],
              $data['content_type_info']['death_date']
            );
          }
          break;
          
        case 'event':
          if (!empty($data['content_type_info']['event_date'])) {
            $citation .= sprintf(' Historical event (%s).', $data['content_type_info']['event_date']);
          }
          break;
      }
    }
    
    // Add URL and access date.
    $citation .= sprintf('. Available at: %s (Accessed: %s).',
      $data['url'],
      $data['accessed_date']
    );

    return $citation;
  }

  /**
   * Generates an APA style citation.
   *
   * @param array $data
   *   The node data for citation generation.
   *
   * @return string
   *   The APA style citation.
   */
  protected function generateApaCitation(array $data) {
    // Basic APA format: Author. (Year, Month Day). Title. Site Name. URL
    $citation = sprintf(
      '%s. (%s). <em>%s</em>',
      $data['author'],
      $data['created_formatted'],
      $data['title']
    );
    
    // Add image information if available.
    if (!empty($data['image_info']['has_image']) && $data['image_info']['has_image']) {
      if (!empty($data['image_info']['image_title'])) {
        $citation .= sprintf(' [%s]', $data['image_info']['image_title']);
      }
      else {
        $citation .= ' [Image]';
      }
      
      // If we have a photographer, include it.
      if (!empty($data['image_info']['photographer'])) {
        $citation .= sprintf(' (Photograph by %s)', $data['image_info']['photographer']);
      }
    }
    
    // Add content type specific information.
    if (!empty($data['content_type_info']['node_type'])) {
      switch ($data['content_type_info']['node_type']) {
        case 'biography':
          $citation .= ' [Biography]';
          break;
          
        case 'event':
          $citation .= ' [Historical event]';
          break;
          
        case 'place':
          $citation .= ' [Place]';
          break;
      }
    }
    
    // Add site name and URL.
    $citation .= sprintf('. %s. %s',
      $data['site_name'],
      $data['url']
    );

    return $citation;
  }

  /**
   * Generates a Chicago style citation.
   *
   * @param array $data
   *   The node data for citation generation.
   *
   * @return string
   *   The Chicago style citation.
   */
  protected function generateChicagoCitation(array $data) {
    // Basic Chicago format: Author. "Title." Site Name. Month Day, Year. Accessed Date. URL.
    $citation = sprintf(
      '%s. "%s."',
      $data['author'],
      $data['title']
    );
    
    // Oxford format should not include image information
    // Explicitly ensure no [Image] text is added
    
    // Add content type specific information.
    if (!empty($data['content_type_info']['node_type'])) {
      switch ($data['content_type_info']['node_type']) {
        case 'biography':
          if (!empty($data['content_type_info']['first_name']) && !empty($data['content_type_info']['last_name'])) {
            $citation .= sprintf(' Biography of %s %s', 
              $data['content_type_info']['first_name'],
              $data['content_type_info']['last_name']
            );
          }
          break;
          
        case 'event':
          if (!empty($data['content_type_info']['event_date'])) {
            $citation .= sprintf(' Event of %s', $data['content_type_info']['event_date']);
          }
          break;
      }
    }
    
    // Add site name, date, access date, and URL.
    $citation .= sprintf(' <em>%s</em>. %s. Accessed %s. %s.',
      $data['site_name'],
      $data['created_formatted'],
      $data['accessed_date'],
      $data['url']
    );

    return $citation;
  }

}