<?php

namespace Drupal\entity_overview\Plugin\Block;

use Drupal\file\FileInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides an "Entity Overview" block.
 *
 * @Block(
 *   id = "entity_overview_block",
 *   admin_label = @Translation("Entity Overview Block"),
 *   category = @Translation("All custom")
 * )
 */
class EntityOverviewBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content_type' => 'article',
      'sort_order' => 'latest',
      'limit' => 5,
      'intro_text' => 'Displaying the latest content from the %title section of the site.',
      'enable_filtering' => TRUE,
      'enable_sorting' => TRUE,
      // Default to hiding display toggle from frontend users.
      'show_display_toggle' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * Gets taxonomy fields for a content type.
   *
   * @param string $content_type
   *   The content type machine name.
   *
   * @return array
   *   An array of taxonomy field information.
   */
  protected function getTaxonomyFields($content_type) {
    $fields = [];
    // Get all field definitions for the content type.
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $field_definitions = $entity_field_manager->getFieldDefinitions('node', $content_type);
    // Filter for taxonomy term reference fields.
    foreach ($field_definitions as $field_name => $field_definition) {
      if ($field_definition->getType() == 'entity_reference' &&
          $field_definition->getSetting('target_type') == 'taxonomy_term') {
        $handler_settings = $field_definition->getSetting('handler_settings');
        $target_bundles = $handler_settings['target_bundles'] ?? [];

        // Get the field label.
        $label = $field_definition->getLabel();

        // Add the field to the list with its vocabularies.
        $fields[$field_name] = [
          'label' => $label,
          'vocabularies' => $target_bundles,
        ];
      }
    }
    return $fields;
  }

  /**
   * Gets taxonomy terms organized by vocabulary.
   *
   * @param string $content_type
   *   The content type machine name.
   *
   * @return array
   *   An array of terms organized by vocabulary.
   */
  protected function getTermsByVocabulary($content_type) {
    $terms_by_vocabulary = [];
    $vocabulary_labels = [];

    // Get taxonomy fields for the content type.
    $taxonomy_fields = $this->getTaxonomyFields($content_type);

    if (!empty($taxonomy_fields)) {
      $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $vocabulary_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary');

      // Get all vocabularies used by this content type.
      $all_vocabularies = [];
      foreach ($taxonomy_fields as $field_info) {
        $all_vocabularies = array_merge($all_vocabularies, $field_info['vocabularies']);
      }
      $all_vocabularies = array_unique($all_vocabularies);

      // Load vocabulary labels.
      foreach ($all_vocabularies as $vocabulary_id) {
        $vocabulary = $vocabulary_storage->load($vocabulary_id);
        if ($vocabulary) {
          $vocabulary_labels[$vocabulary_id] = $vocabulary->label();
        }
      }

      // Load terms for each vocabulary.
      foreach ($all_vocabularies as $vocabulary_id) {
        $term_query = $term_storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('vid', $vocabulary_id);
        $tids = $term_query->execute();

        if (!empty($tids)) {
          $terms = $term_storage->loadMultiple($tids);
          foreach ($terms as $term) {
            $terms_by_vocabulary[$vocabulary_id][$term->id()] = $term->label();
          }
        }
      }
    }

    return [
      'terms' => $terms_by_vocabulary,
      'vocabulary_labels' => $vocabulary_labels,
    ];
  }

  /**
   * Gets taxonomy term options for a select list.
   *
   * @param string $content_type
   *   The content type machine name.
   *
   * @return array
   *   An array of term options.
   */
  protected function getTermOptions($content_type) {
    $term_options = ['' => $this->t('- None -')];
    $terms_data = $this->getTermsByVocabulary($content_type);

    foreach ($terms_data['terms'] as $vocabulary_id => $terms) {
      $vocabulary_label = $terms_data['vocabulary_labels'][$vocabulary_id] ?? $vocabulary_id;
      $term_options[$vocabulary_label] = $terms;
    }

    return $term_options;
  }

  /**
   * AJAX callback to update taxonomy terms based on content type.
   */
  public function updateTaxonomyTerms(array &$form, FormStateInterface $form_state) {
    return $form['settings']['filter_term_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['intro_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Intro Text'),
      '#description' => $this->t('The introductory text describing the block. Use %title to insert the block title.'),
      '#default_value' => $this->configuration['intro_text'],
    ];

    // Get available content types for the dropdown.
    $content_types = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');
    $allowed_types = ['place', 'event', 'upcomingevent', 'archive', 'article', 'biography'];
    $content_type_options = [];
    foreach ($content_types as $machine_name => $content_type) {
      if (in_array($machine_name, $allowed_types)) {
        $content_type_options[$machine_name] = $content_type['label'];
      }
    }

    $form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('Select the content type to display.'),
      '#options' => $content_type_options,
      '#default_value' => $this->configuration['content_type'],
      '#ajax' => [
        'callback' => [$this, 'updateTaxonomyTerms'],
        'wrapper' => 'taxonomy-term-wrapper',
        'event' => 'change',
      ],
    ];

    // No longer using taxonomy term filter in configuration form.
    // Add options to enable/disable filtering, sorting, and display toggle.
    $form['enable_filtering'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable filtering for frontend users'),
      '#description' => $this->t('Allow users to filter content by taxonomy terms.'),
      '#default_value' => $this->configuration['enable_filtering'],
    ];

    $form['enable_sorting'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable sorting for frontend users'),
      '#description' => $this->t('Allow users to sort content by different criteria.'),
      '#default_value' => $this->configuration['enable_sorting'],
    ];

    $form['show_display_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show display mode toggle'),
      '#description' => $this->t('Show the display mode toggle (Default, Compact, Full Width) to frontend users. This is typically only needed for backend administration.'),
      '#default_value' => $this->configuration['show_display_toggle'],
    ];

    $form['sort_order'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sort Order'),
      '#options' => [
        'latest' => $this->t('Latest'),
        'oldest' => $this->t('Oldest'),
      ],
      '#default_value' => $this->configuration['sort_order'],
    ];

    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items'),
      '#default_value' => $this->configuration['limit'],
      '#min' => 1,
      '#max' => 50,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['intro_text'] = $form_state->getValue('intro_text');
    $this->configuration['content_type'] = $form_state->getValue('content_type');
    $this->configuration['sort_order'] = $form_state->getValue('sort_order');
    $this->configuration['limit'] = $form_state->getValue('limit');
    $this->configuration['enable_filtering'] = $form_state->getValue('enable_filtering');
    $this->configuration['enable_sorting'] = $form_state->getValue('enable_sorting');
    $this->configuration['show_display_toggle'] = $form_state->getValue('show_display_toggle');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content_type = $this->configuration['content_type'];
    $sort_order = $this->configuration['sort_order'];
    $limit = $this->configuration['limit'];
    $enable_filtering = $this->configuration['enable_filtering'];
    $enable_sorting = $this->configuration['enable_sorting'];
    // Use the built-in block label as the block title.
    $block_title = $this->configuration['label'] ?? '';
    $intro_text = $this->configuration['intro_text'];

    // Generate a unique ID for this block instance.
    $block_id = 'entity-overview-' . substr(hash('sha256', $this->getPluginId() . serialize($this->configuration)), 0, 8);

    // Build the query to fetch entities.
    $query = \Drupal::entityQuery('node')
      ->condition('type', $content_type)
      ->condition('status', 1)
      ->range(0, $limit)
      ->accessCheck(TRUE);

    // Removed filter_term_id condition from query.
    if ($sort_order == 'latest') {
      $query->sort('created', 'DESC');
    }
    else {
      $query->sort('created', 'ASC');
    }

    // Execute the query and load the entities.
    $nids = $query->execute();
    $nodes = !empty($nids) ? Node::loadMultiple($nids) : [];
    $items = [];
    foreach ($nodes as $node) {
      $items[] = $this->buildEntityItem($node);
    }

    // Check if there are more entities.
    $total_count = $this->getEntityCount($content_type);
    $has_more = count($items) < $total_count;

    // Build filter options if filtering is enabled.
    $filter_options = $enable_filtering ? $this->buildFilterOptions($content_type) : [];

    // Build sort options if sorting is enabled.
    $sort_options = $enable_sorting ? $this->buildSortOptions() : [];

    // Return the render array.
    return [
      '#theme' => 'entity_overview_block',
      '#items' => $items,
      '#block_title' => $block_title,
      '#intro_text' => $intro_text,
      '#block_id' => $block_id,
      '#filter_options' => $filter_options,
      '#sort_options' => $sort_options,
      '#has_more' => $has_more,
      '#show_display_toggle' => $this->configuration['show_display_toggle'],
      // Default display mode.
      '#display_mode' => 'default',
      '#cache' => [
        'contexts' => ['url.query_args'],
        'tags' => ['node_list:' . $content_type],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Gets the total count of entities matching the filter.
   *
   * @param string $content_type
   *   The content type.
   *
   * @return int
   *   The total count.
   */
  protected function getEntityCount($content_type) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', $content_type)
      ->condition('status', 1)
      ->accessCheck(TRUE);

    return $query->count()->execute();
  }

  /**
   * Builds filter options for the block.
   *
   * @param string $content_type
   *   The content type.
   *
   * @return array
   *   An array of filter options.
   */
  protected function buildFilterOptions($content_type) {
    $options = [];
    $terms_data = $this->getTermsByVocabulary($content_type);

    // Organize terms by vocabulary for the frontend.
    foreach ($terms_data['terms'] as $vocabulary_id => $terms) {
      $vocabulary_label = $terms_data['vocabulary_labels'][$vocabulary_id] ?? $vocabulary_id;

      foreach ($terms as $term_id => $term_label) {
        $options[] = [
          'id' => $term_id,
          'label' => $term_label,
          'vocabulary' => $vocabulary_id,
          'vocabulary_label' => $vocabulary_label,
          // For grouping in frontend filters.
          'group' => $vocabulary_label,
        ];
      }
    }

    return $options;
  }

  /**
   * Builds sort options for the block.
   *
   * @return array
   *   An array of sort options.
   */
  protected function buildSortOptions() {
    return [
      [
        'id' => 'latest',
        'label' => $this->t('Latest'),
      ],
      [
        'id' => 'oldest',
        'label' => $this->t('Oldest'),
      ],
      [
        'id' => 'title',
        'label' => $this->t('Title'),
      ],
    ];
  }

  /**
   * Builds a data array from the node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity.
   *
   * @return array
   *   The entity item data.
   */
  protected function buildEntityItem(Node $node) {
    $image_url = '';

    // Define image field names for different content types.
    $image_field_map = [
      'article' => 'field_article_image',
      'biography' => 'field_bio_pic',
      'place' => 'field_place_image',
      'event' => 'field_event_image',
      'upcomingevent' => 'field_upcomingevent_image',
      'archive' => 'field_archive_image',
      // Add more content types and their image field names as needed.
    ];

    // Get the content type of the node.
    $content_type = $node->bundle();

    // Get the appropriate image field name for this content type.
    $image_field = $image_field_map[$content_type] ?? 'field_article_image';

    // Check if the node has the image field and it's not empty.
    if ($node->hasField($image_field) && !$node->get($image_field)->isEmpty()) {
      $file = $node->get($image_field)->entity;
      if ($file) {
        // Check if entity implements FileInterface or has getFileUri method.
        if (($file instanceof FileInterface) ||
            (method_exists($file, 'getFileUri') &&
             $file->getEntityTypeId() === 'file')
        ) {
          $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        }
      }
    }

    return [
      'id' => $node->id(),
      'title' => $node->label(),
      'url' => $node->toUrl()->toString(),
      'image' => $image_url,
      'created' => $node->getCreatedTime(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
