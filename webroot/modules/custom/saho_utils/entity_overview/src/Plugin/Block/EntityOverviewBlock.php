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
      'custom_header' => '',
      'intro_text' => 'Displaying the latest content from the %title section of the site.',
      'enable_filtering' => FALSE,
      'enable_sorting' => FALSE,
      // No display toggles needed.
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['custom_header'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Header'),
      '#description' => $this->t('Custom header text for the block. Leave empty to use the default block title. You can use %content_type to insert the content type name.'),
      '#default_value' => $this->configuration['custom_header'],
      '#maxlength' => 255,
    ];

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

    // No longer using filtering - focus on sorting and display modes.
    // Sorting is handled via backend configuration only.
    // No display mode toggle needed - clean content display only.
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
      '#description' => $this->t('Number of items to show initially. Additional items can be loaded with "Load More" if enabled below.'),
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
    $this->configuration['custom_header'] = $form_state->getValue('custom_header');
    $this->configuration['intro_text'] = $form_state->getValue('intro_text');
    $this->configuration['content_type'] = $form_state->getValue('content_type');
    $this->configuration['sort_order'] = $form_state->getValue('sort_order');
    $this->configuration['limit'] = $form_state->getValue('limit');
    $this->configuration['enable_filtering'] = FALSE;
    // No frontend sorting controls needed
    // No display toggle configuration needed.
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content_type = $this->configuration['content_type'];
    $sort_order = $this->configuration['sort_order'];
    $limit = $this->configuration['limit'];
    // Simplified - backend configuration only
    // Use custom header if provided, otherwise fall back to block label.
    $custom_header = $this->configuration['custom_header'];
    if (!empty($custom_header)) {
      // Get content type label for token replacement.
      $content_types = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');
      $content_type_label = $content_types[$content_type]['label'] ?? $content_type;

      $block_title = str_replace('%content_type', $content_type_label, $custom_header);
    }
    else {
      $block_title = $this->configuration['label'] ?? '';
    }
    $intro_text = $this->configuration['intro_text'];

    // Generate a unique ID for this block instance.
    $block_id = 'entity-overview-' . substr(hash('sha256', $this->getPluginId() . serialize($this->configuration)), 0, 8);

    // Build the query to fetch entities.
    $query = \Drupal::entityQuery('node')
      ->condition('type', $content_type)
      ->condition('status', 1)
      ->range(0, $limit)
      ->accessCheck(TRUE);

    // Apply sorting based on sort_order configuration.
    if ($sort_order == 'latest') {
      $query->sort('changed', 'DESC');
    }
    elseif ($sort_order == 'oldest') {
      $query->sort('changed', 'ASC');
    }
    else {
      // Default to latest changed.
      $query->sort('changed', 'DESC');
    }

    // Execute the query and load the entities.
    $nids = $query->execute();
    $nodes = !empty($nids) ? Node::loadMultiple($nids) : [];
    $items = [];
    foreach ($nodes as $node) {
      $items[] = $this->buildEntityItem($node);
    }

    // Simple display - no load more functionality needed.
    // Return the render array.
    $build = [
      '#theme' => 'entity_overview_block',
      '#items' => $items,
      '#block_title' => $block_title,
      '#intro_text' => $intro_text,
      '#block_id' => $block_id,
      // No filter options
      // Backend sorting only.
      '#current_sort_order' => $sort_order,
      '#cache' => [
        'contexts' => ['url.query_args'],
        'tags' => ['node_list:' . $content_type],
        'max-age' => 3600,
      ],
      '#attached' => [
        'library' => ['entity_overview/entity_overview'],
        'drupalSettings' => [
          'entityOverview' => [
            $block_id => [
              'blockId' => $block_id,
              'contentType' => $content_type,
              'currentSortOrder' => $sort_order,
              'limit' => $limit,
            ],
          ],
        ],
      ],
    ];

    return $build;
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

    // Extract teaser text from body field.
    $teaser_text = '';
    $body_field_candidates = ['body', 'field_body', 'field_description', 'field_summary'];

    foreach ($body_field_candidates as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        $body_field = $node->get($field_name)->first();
        if ($body_field) {
          $body_value = $body_field->getValue();
          $body_text = $body_value['value'] ?? '';

          // Strip HTML and create teaser (around 150 characters)
          $teaser_text = strip_tags($body_text);
          $teaser_text = substr($teaser_text, 0, 150);

          // Find the last complete sentence or word within the limit.
          if (strlen($teaser_text) == 150) {
            $last_period = strrpos($teaser_text, '.');
            $last_space = strrpos($teaser_text, ' ');

            if ($last_period !== FALSE && $last_period > 100) {
              $teaser_text = substr($teaser_text, 0, $last_period + 1);
            }
            elseif ($last_space !== FALSE) {
              $teaser_text = substr($teaser_text, 0, $last_space) . '...';
            }
            else {
              $teaser_text .= '...';
            }
          }

          // Use first available body field.
          break;
        }
      }
    }

    return [
      'id' => $node->id(),
      'title' => $node->label(),
      'url' => $node->toUrl()->toString(),
      'image' => $image_url,
      'teaser' => $teaser_text,
      'created' => $node->getCreatedTime(),
      'changed' => $node->getChangedTime(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
