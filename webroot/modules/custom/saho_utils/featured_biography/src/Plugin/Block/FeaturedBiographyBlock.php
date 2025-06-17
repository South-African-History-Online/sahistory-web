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
 *   category = @Translation("SAHO"),
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
      'selection_method' => 'random',
      'specific_nid' => '',
      'category' => '',
      'display_mode' => 'full',
      'highlight_category' => FALSE,
      'entity_count' => 1,
      'category_label' => '',
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
      '#options' => [
        'random' => $this->t('Random Biography'),
        'specific' => $this->t('Specific Biography'),
        'category' => $this->t('By Category'),
      ],
      '#default_value' => $config['selection_method'],
      '#required' => TRUE,
    ];

    $form['specific_nid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Biography Node ID'),
      '#description' => $this->t('Enter the node ID of the specific biography to feature.'),
      '#default_value' => $config['specific_nid'],
      '#states' => [
        'visible' => [
          ':input[name="settings[selection_method]"]' => ['value' => 'specific'],
        ],
        'required' => [
          ':input[name="settings[selection_method]"]' => ['value' => 'specific'],
        ],
      ],
    ];

    // Get all taxonomy terms for people categories.
    $terms = [];
    try {
      $terms_entities = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => 'field_people_category']);
      foreach ($terms_entities as $term) {
        $terms[$term->id()] = $term->label();
      }
    }
    catch (\Exception $e) {
      // Log the error or handle it appropriately.
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

    $form['entity_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of Biographies'),
      '#description' => $this->t('Number of biographies to display (max 5). Only applicable for @type1 or @type2 selection.',
        ['@type1' => 'Random', '@type2' => 'Category']),
      '#default_value' => $config['entity_count'],
      '#min' => 1,
      '#max' => 5,
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          [':input[name="settings[selection_method]"]' => ['value' => 'random']],
          'or',
          [':input[name="settings[selection_method]"]' => ['value' => 'category']],
        ],
        'disabled' => [
          ':input[name="settings[selection_method]"]' => ['value' => 'specific'],
        ],
      ],
    ];

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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['selection_method'] = $form_state->getValue('selection_method');
    $this->configuration['specific_nid'] = $form_state->getValue('specific_nid');
    $this->configuration['category'] = $form_state->getValue('category');
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
    $this->configuration['highlight_category'] = $form_state->getValue('highlight_category');
    $this->configuration['entity_count'] = $form_state->getValue('entity_count');
    $this->configuration['category_label'] = $form_state->getValue('category_label');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $biography_data = $this->getBiographyItem();

    if (!$biography_data) {
      return [
        '#markup' => $this->t('No biography available.'),
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
      // For specific biography, always use entity_count = 1.
      $entity_count = $this->configuration['selection_method'] === 'specific'
        ? 1
        : min((int) $this->configuration['entity_count'], 5);
      $entity_count = max($entity_count, 1);

      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('status', 1)
        ->condition('type', 'biography')
        ->accessCheck(TRUE);

      // Apply selection criteria.
      switch ($this->configuration['selection_method']) {
        case 'specific':
          if (!empty($this->configuration['specific_nid'])) {
            $query->condition('nid', $this->configuration['specific_nid']);
          }
          break;

        case 'category':
          if (!empty($this->configuration['category'])) {
            $query->condition('field_people_category', $this->configuration['category']);
          }
          break;

        case 'random':
        default:
          // Random selection will be handled after query execution.
          break;
      }

      // Apply range after all conditions.
      $query->range(0, $entity_count);

      // Execute the query.
      $nids = $query->execute();

      // Debug output.
      \Drupal::messenger()->addMessage(
        'Query executed. Found @count biographies.',
        ['@count' => count($nids)]
      );
      if (!empty($nids)) {
        \Drupal::messenger()->addMessage(
          'Node IDs: @nids',
          ['@nids' => implode(', ', $nids)]
        );
      }

      if (!empty($nids)) {
        if ($this->configuration['selection_method'] == 'random' && count($nids) > $entity_count) {
          // Randomly select the specified number of biographies.
          $selected_nids = [];
          $keys = array_rand($nids, $entity_count);
          if (!is_array($keys)) {
            $keys = [$keys];
          }
          foreach ($keys as $key) {
            $selected_nids[] = $nids[$key];
          }
          $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($selected_nids);
        }
        else {
          // Load all biographies up to the specified limit.
          $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
        }
      }
    }
    catch (\Exception $e) {
      // Log the error or handle it appropriately.
      return NULL;
    }

    if (empty($nodes)) {
      return NULL;
    }

    // If only one biography is requested or found, return a single item
    // for backward compatibility.
    if ($entity_count == 1 || count($nodes) == 1) {
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

    // Get the biography dates.
    if ($node->hasField('field_dob') && !$node->get('field_dob')->isEmpty()) {
      $item['birth_date'] = $node->get('field_dob')->value;
    }
    if ($node->hasField('field_dod') && !$node->get('field_dod')->isEmpty()) {
      $item['death_date'] = $node->get('field_dod')->value;
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

}
