<?php

namespace Drupal\featured_articles\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Featured Article" block.
 *
 * @Block(
 *   id = "featured_article_block",
 *   admin_label = @Translation("Featured Article Block"),
 *   category = @Translation("All custom")
 * )
 */
class FeaturedArticleBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a FeaturedArticleBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $file_url_generator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'use_manual_override' => FALSE,
      'manual_entity_id' => NULL,
      'sort_by' => 'none',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['use_manual_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Manual Override?'),
      '#description' => $this->t('Select a specific article instead of a random featured one.'),
      '#default_value' => $this->configuration['use_manual_override'],
    ];

    // Validate entity ID before loading to prevent errors from
    // invalid/deleted nodes.
    $default_node = NULL;
    $manual_id = $this->configuration['manual_entity_id'];
    if ($manual_id && is_numeric($manual_id) && (int) $manual_id > 0) {
      $default_node = $this->entityTypeManager->getStorage('node')->load($manual_id);
    }

    $form['manual_entity_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Manual Article'),
      '#description' => $this->t('Choose the article to display if override is enabled.'),
      '#target_type' => 'node',
      '#selection_handler' => 'default:node',
      '#selection_settings' => [
        'target_bundles' => ['article'],
      ],
      '#default_value' => $default_node,
      '#states' => [
        'visible' => [
          ':input[name="use_manual_override"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['sort_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort By'),
      '#description' => $this->t('Choose how to sort featured articles. "Random" shuffles results each time.'),
      '#options' => [
        'none' => $this->t('Random (shuffle)'),
        'title' => $this->t('Title (alphabetical)'),
        'changed' => $this->t('Recently updated'),
        'created' => $this->t('Recently created'),
      ],
      '#default_value' => $this->configuration['sort_by'],
      '#states' => [
        'visible' => [
          ':input[name="settings[use_manual_override]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['use_manual_override'] = $form_state->getValue('use_manual_override');
    $this->configuration['manual_entity_id'] = $form_state->getValue('manual_entity_id');
    $this->configuration['sort_by'] = $form_state->getValue('sort_by');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $manual_override = $this->configuration['use_manual_override'];
    $manual_entity_id = $this->configuration['manual_entity_id'];

    // If manual override is set, display that specific article.
    // Validate entity ID is a positive integer before loading for security.
    if ($manual_override && $manual_entity_id && is_numeric($manual_entity_id) && (int) $manual_entity_id > 0) {
      $node = $this->entityTypeManager->getStorage('node')->load((int) $manual_entity_id);
      if ($node) {
        return [
          '#theme' => 'featured_article_block',
          '#article_item' => $this->buildArticleItem($node),
        ];
      }
    }

    // Otherwise, pick an article based on sort configuration.
    // Build base query for featured articles.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_home_page_feature', 1)
      ->condition('field_staff_picks', 1)
      ->accessCheck(TRUE);

    // Apply sorting based on configuration.
    $sort_by = $this->configuration['sort_by'] ?? 'none';

    if ($sort_by !== 'none') {
      // Sorted selection - get top result after sorting.
      switch ($sort_by) {
        case 'title':
          $query->sort('title', 'ASC');
          break;

        case 'changed':
          $query->sort('changed', 'DESC');
          break;

        case 'created':
          $query->sort('created', 'DESC');
          break;
      }
      $query->range(0, 1);
      $nids = $query->execute();
      $nid = !empty($nids) ? reset($nids) : NULL;
    }
    else {
      // Random selection - existing shuffle logic.
      $query->range(0, 50);
      $nids = $query->execute();

      if (!empty($nids)) {
        $nids_array = array_values($nids);
        shuffle($nids_array);
        $nid = reset($nids_array);
      }
      else {
        $nid = NULL;
      }
    }

    if (empty($nid)) {
      return [
        '#theme' => 'featured_article_block',
        '#article_item' => NULL,
      ];
    }

    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    return [
      '#theme' => 'featured_article_block',
      '#article_item' => $node ? $this->buildArticleItem($node) : NULL,
      '#cache' => [
        'max-age' => 3600,
        'contexts' => ['url'],
        'tags' => ['node_list:article'],
        'keys' => ['featured_article_block', $sort_by],
      ],
    ];
  }

  /**
   * Builds a data array from the article node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity to build the article item from.
   *
   * @return array
   *   An array containing the article data with keys:
   *   - id: The node ID
   *   - title: The node title
   *   - url: The node URL
   *   - image: The image URL if available
   */
  protected function buildArticleItem(NodeInterface $node) {
    $image_url = '';
    if ($node->hasField('field_article_image') && !$node->get('field_article_image')->isEmpty()) {
      $file = $node->get('field_article_image')->entity;
      if ($file) {
        // Check if the entity implements FileInterface or is a File entity.
        if ($file instanceof FileInterface) {
          $image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        }
        // Fallback: check if the entity has a getFileUri method
        // and is a file entity.
        elseif ($file->getEntityTypeId() === 'file' && method_exists($file, 'getFileUri')) {
          $image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        }
        // Last resort: try to load it as a media entity and
        // get the source file.
        elseif ($file->getEntityTypeId() === 'media') {
          // Check if this is a media entity that has a source plugin.
          if (method_exists($file, 'getSource') && $file->getSource()) {
            $source_field = $file->getSource()->getConfiguration()['source_field'];
            // Check if the media entity has the source field.
            if (method_exists($file, 'get') && !empty($file->get($source_field)->entity)) {
              $source_file = $file->get($source_field)->entity;
              if ($source_file instanceof FileInterface) {
                $image_url = $this->fileUrlGenerator->generateAbsoluteString($source_file->getFileUri());
              }
            }
          }
        }
      }
    }

    return [
      'id' => $node->id(),
      'title' => $node->label(),
      'url' => $node->toUrl()->toString(),
      'image' => $image_url,
    ];
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
      $container->get('file_url_generator'),
    );
  }

}
