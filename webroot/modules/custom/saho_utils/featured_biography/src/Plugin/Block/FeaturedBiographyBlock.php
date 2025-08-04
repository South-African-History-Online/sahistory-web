<?php

namespace Drupal\featured_biography\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Featured Biography' block.
 *
 * @Block(
 *   id = "featured_biography_block",
 *   admin_label = @Translation("Featured Biography Block"),
 *   category = @Translation("All custom"),
 * )
 */
class FeaturedBiographyBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new FeaturedBiographyBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'selection_method' => 'specific',
      'specific_nid' => '',
      'specific_nids' => '',
      'category' => '',
      'display_mode' => 'full',
      'highlight_category' => FALSE,
      'entity_count' => 1,
      'category_label' => '',
      'enable_carousel' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['selection_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Selection Method'),
      '#description' => $this->t('Choose how to select biographies to display.'),
      '#options' => [
        'specific' => $this->t('Specific Biography (manual selection)'),
        'category' => $this->t('By Category (all biographies in selected category)'),
      ],
      '#default_value' => $config['selection_method'],
      '#required' => TRUE,
    ];

    $form['entity_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of Biographies'),
      '#description' => $this->t('How many biographies to display (1-9).'),
      '#options' => [
        1 => '1 Biography',
        2 => '2 Biographies',
        3 => '3 Biographies',
        4 => '4 Biographies',
        5 => '5 Biographies',
        6 => '6 Biographies',
        7 => '7 Biographies',
        8 => '8 Biographies',
        9 => '9 Biographies',
      ],
      '#default_value' => $config['entity_count'],
      '#required' => TRUE,
    ];

    $form['specific_nid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Biography Node ID'),
      '#description' => $this->t('Enter the node ID of the specific biography to feature (for single selection).'),
      '#default_value' => $config['specific_nid'],
      '#states' => [
        'visible' => [
          ':input[name="settings[selection_method]"]' => ['value' => 'specific'],
          ':input[name="settings[entity_count]"]' => ['value' => '1'],
        ],
        'required' => [
          ':input[name="settings[selection_method]"]' => ['value' => 'specific'],
          ':input[name="settings[entity_count]"]' => ['value' => '1'],
        ],
      ],
    ];

    // Get all taxonomy terms for people categories.
    $terms = [];
    try {
      // Use the standard people category vocabulary.
      $vocab_name = 'field_people_category';
      $terms_entities = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => $vocab_name]);
      
      foreach ($terms_entities as $term) {
        $terms[$term->id()] = $term->label();
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('featured_biography')->error('Error loading taxonomy terms: @message', ['@message' => $e->getMessage()]);
    }

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Biography Category'),
      '#description' => $this->t('Select a category to feature biographies from.'),
      '#options' => $terms,
      '#empty_option' => $this->t('- Select a category -'),
      '#default_value' => $config['category'],
      '#states' => [
        'visible' => [
          ':input[name="settings[selection_method]"]' => ['value' => 'category'],
        ],
        'required' => [
          ':input[name="settings[selection_method]"]' => ['value' => 'category'],
        ],
      ],
    ];

    // Add multiple node IDs field for specific selection.
    $form['specific_nids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Biography Node IDs'),
      '#description' => $this->t('Enter node IDs separated by commas (e.g., 123, 456, 789) for multiple specific biographies.'),
      '#default_value' => $config['specific_nids'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="settings[selection_method]"]' => ['value' => 'specific'],
          ':input[name="settings[entity_count]"]' => ['!value' => '1'],
        ],
        'required' => [
          ':input[name="settings[selection_method]"]' => ['value' => 'specific'],
          ':input[name="settings[entity_count]"]' => ['!value' => '1'],
        ],
      ],
      '#rows' => 3,
    ];

    // Add helpful message if no categories are found.
    if (empty($terms)) {
      $form['category_help'] = [
        '#type' => 'item',
        '#title' => $this->t('No Categories Found'),
        '#description' => $this->t('No biography categories were found. Please create a "People Category" vocabulary and add some terms, or check that biography nodes have the field_people_category field.'),
        '#states' => [
          'visible' => [
            ':input[name="settings[selection_method]"]' => ['value' => 'category'],
          ],
        ],
      ];
    }

    $form['display_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display Mode'),
      '#options' => [
        'full' => $this->t('Full (with image and details)'),
        'compact' => $this->t('Compact (minimal details)'),
      ],
      '#default_value' => $config['display_mode'],
      '#required' => TRUE,
    ];

    // Remove duplicate entity_count field - using the select version above

    $form['highlight_category'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Highlight Category'),
      '#description' => $this->t('Display the category label above the biography.'),
      '#default_value' => $config['highlight_category'],
    ];

    $form['category_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Category Label'),
      '#description' => $this->t('Custom label to display when highlighting category (leave empty to use category name).'),
      '#default_value' => $config['category_label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[highlight_category]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['enable_carousel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Carousel/Scroller'),
      '#description' => $this->t('Enable carousel functionality for multiple biographies (great for mobile and desktop engagement).'),
      '#default_value' => $config['enable_carousel'],
      '#states' => [
        'visible' => [
          [':input[name="settings[entity_count]"]' => ['!value' => '1']],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['selection_method'] = $form_state->getValue('selection_method');
    $this->configuration['specific_nid'] = $form_state->getValue('specific_nid');
    $this->configuration['specific_nids'] = $form_state->getValue('specific_nids');
    $this->configuration['category'] = $form_state->getValue('category');
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
    $this->configuration['highlight_category'] = $form_state->getValue('highlight_category');
    $this->configuration['entity_count'] = $form_state->getValue('entity_count');
    $this->configuration['category_label'] = $form_state->getValue('category_label');
    $this->configuration['enable_carousel'] = $form_state->getValue('enable_carousel');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $biography_data = $this->getBiographyItem();

    if (!$biography_data) {
      // Create a sample/demo biography to show the layout
      $demo_biography = [
        'nid' => 0,
        'title' => $this->t('Sample Biography'),
        'url' => '#',
        'birth_date' => '1 January 1900',
        'death_date' => '31 December 1990',
        'categories' => [
          ['name' => $this->t('Freedom Fighter'), 'url' => '#'],
        ],
        'position' => $this->t('Political Activist'),
        'body_summary' => $this->t('This is a sample biography to demonstrate the layout. Configure this block to show real biographies from your content.'),
        'is_demo' => TRUE,
      ];
      
      // Provide helpful error message for admins
      $current_user = \Drupal::currentUser();
      $show_admin_info = $current_user->hasPermission('administer blocks');
      
      return [
        '#theme' => 'featured_biography_block',
        '#biography_item' => $demo_biography,
        '#display_mode' => $this->configuration['display_mode'],
        '#is_demo' => TRUE,
        '#show_admin_info' => $show_admin_info,
        '#selection_method' => $this->configuration['selection_method'],
        '#cache' => [
          'max-age' => 300,
          'contexts' => ['user.permissions'],
        ],
      ];
    }

    // For backward compatibility, if we have a single item,
    // pass it as biography_item.
    if (isset($biography_data['nid'])) {
      // Cache for 1 hour.
      return [
        '#theme' => 'featured_biography_block',
        '#biography_item' => $biography_data,
        '#display_mode' => $this->configuration['display_mode'],
        '#cache' => [
          'max-age' => 3600,
          'contexts' => ['url'],
          'tags' => ['node_list:biography'],
        ],
      ];
    }

    // For multiple items or category highlighting.
    return [
      '#theme' => 'featured_biography_block',
      '#biography_data' => $biography_data,
      '#display_mode' => $this->configuration['display_mode'],
      '#highlight_category' => $this->configuration['highlight_category'],
      '#entity_count' => $this->configuration['entity_count'],
      '#enable_carousel' => $this->configuration['enable_carousel'],
      '#cache' => [
        'max-age' => 3600,
        'contexts' => ['url'],
        'tags' => ['node_list:biography'],
      ],
    ];
  }

  /**
   * Gets the biography item based on the configuration.
   *
   * @return array|null
   *   The biography item data or NULL if none found.
   */
  protected function getBiographyItem() {
    $nodes = [];

    try {
      // Get the configured entity count (1-9).
      $entity_count = max(1, min((int) $this->configuration['entity_count'], 9));

      // Use the standard biography content type.
      $content_type = 'biography';
      
      // Verify the content type exists.
      $type_exists = $this->entityTypeManager->getStorage('node_type')->load($content_type);
      if (!$type_exists) {
        \Drupal::logger('featured_biography')->warning('Biography content type not found. Please ensure "biography" content type exists.');
        return NULL;
      }

      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('status', 1)
        ->condition('type', $content_type)
        ->accessCheck(TRUE);

      // Apply selection criteria.
      switch ($this->configuration['selection_method']) {
        case 'specific':
          $nids_to_load = [];
          
          // Handle single specific node ID.
          if ($entity_count == 1 && !empty($this->configuration['specific_nid'])) {
            $nids_to_load[] = $this->configuration['specific_nid'];
          }
          // Handle multiple specific node IDs.
          elseif ($entity_count > 1 && !empty($this->configuration['specific_nids'])) {
            $nids_string = str_replace([' ', "\n", "\r"], '', $this->configuration['specific_nids']);
            $nids_array = array_filter(explode(',', $nids_string));
            $nids_to_load = array_slice($nids_array, 0, $entity_count);
          }
          
          if (!empty($nids_to_load)) {
            $query->condition('nid', $nids_to_load, 'IN');
          }
          break;

        case 'category':
          if (!empty($this->configuration['category'])) {
            $query->condition('field_people_category', $this->configuration['category']);
          }
          break;
      }

      // Apply range after all conditions.
      $query->range(0, $entity_count);

      // Execute the query.
      $nids = $query->execute();

      if (!empty($nids)) {
        // Load all selected biographies.
        $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      }
    }
    catch (\Exception $e) {
      // Log the error or handle it appropriately.
      return NULL;
    }

    if (empty($nodes)) {
      return NULL;
    }

    // If only one biography is requested, return a single item for
    // backward compatibility.
    if ($entity_count == 1) {
      $node = reset($nodes);
      if ($node) {
        return $this->buildBiographyItem($node);
      }
      return NULL;
    }

    // Otherwise, build multiple items.
    $items = [];
    foreach ($nodes as $node) {
      $items[] = $this->buildBiographyItem($node);
    }

    // Add category information if highlighting is enabled.
    if ($this->configuration['highlight_category'] && !empty($this->configuration['category'])) {
      $category_info = NULL;
      try {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($this->configuration['category']);
        if ($term) {
          $category_info = [
            'id' => $term->id(),
            'name' => $term->label(),
            'url' => $term->toUrl()->toString(),
            'custom_label' => !empty($this->configuration['category_label']) ? $this->configuration['category_label'] : NULL,
          ];
        }
      }
      catch (\Exception $e) {
        // Log the error or handle it appropriately.
      }

      if ($category_info) {
        return [
          'items' => $items,
          'category' => $category_info,
        ];
      }
    }

    return ['items' => $items];
  }

  /**
   * Builds a biography item from a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return array
   *   The biography item data.
   */
  protected function buildBiographyItem($node) {
    $item = [
      'nid' => $node->id(),
      'title' => $node->getTitle(),
      'url' => $node->toUrl()->toString(),
    ];

    // Get the biography image.
    if ($node->hasField('field_bio_pic') && !$node->get('field_bio_pic')->isEmpty()) {
      $image_field = $node->get('field_bio_pic');
      $image_entity = $this->entityTypeManager->getStorage('file')
        ->load($image_field->getValue()[0]['target_id']);
      if ($image_entity) {
        // Extract the string value from the URI field.
        $image_uri = $image_entity->uri->value;
        $item['image'] = $this->fileUrlGenerator->generateAbsoluteString($image_uri);
      }
    }

    // Get the biography dates and format them consistently.
    if ($node->hasField('field_dob') && !$node->get('field_dob')->isEmpty()) {
      $item['birth_date'] = $this->formatDateString($node->get('field_dob')->value);
    }
    if ($node->hasField('field_dod') && !$node->get('field_dod')->isEmpty()) {
      $item['death_date'] = $this->formatDateString($node->get('field_dod')->value);
    }

    // Get the biography categories.
    if ($node->hasField('field_people_category') && !$node->get('field_people_category')->isEmpty()) {
      $categories = [];
      foreach ($node->get('field_people_category') as $category) {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')
          ->load($category->getValue()['target_id']);
        if ($term) {
          $categories[] = [
            'id' => $term->id(),
            'name' => $term->label(),
            'url' => $term->toUrl()->toString(),
          ];
        }
      }
      $item['categories'] = $categories;
    }

    // Get the biography body.
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $item['body'] = $node->get('body')->value;
      // Get summary - if summary is empty, create one from the body.
      if (!empty($node->get('body')->summary)) {
        $item['body_summary'] = $node->get('body')->summary;
      }
      elseif (!empty($node->get('body')->value)) {
        // Create a summary from the body if no summary exists.
        $body_text = $node->get('body')->value;
        // Create a simple summary by stripping tags and truncating.
        $plain_text = strip_tags($body_text);
        $item['body_summary'] = mb_substr($plain_text, 0, 200) . (mb_strlen($plain_text) > 200 ? '...' : '');
      }
    }

    // Get additional fields if needed.
    if ($node->hasField('field_position') && !$node->get('field_position')->isEmpty()) {
      $item['position'] = $node->get('field_position')->value;
    }

    return $item;
  }

  /**
   * Format date string to SAHO standard format (j F Y).
   *
   * @param string $date_string
   *   The original date string in various formats.
   *
   * @return string
   *   The formatted date string or original if conversion fails.
   */
  private function formatDateString($date_string) {
    if (empty($date_string)) {
      return $date_string;
    }

    // Try to parse various date formats and convert to "j F Y"
    $formats = [
      'd-F-Y',         // 18-July-1918
      'd F Y',         // 18 July 1918
      'd-M-Y',         // 18-Jul-1918
      'd M Y',         // 18 Jul 1918
      'j-F-Y',         // 5-December-2013
      'j F Y',         // 5 December 2013
      'j-M-Y',         // 5-Dec-2013
      'j M Y',         // 5 Dec 2013
    ];

    foreach ($formats as $format) {
      $date = \DateTime::createFromFormat($format, $date_string);
      if ($date !== false) {
        return $date->format('j F Y');
      }
    }

    // If no format matches, return original string
    return $date_string;
  }

}
