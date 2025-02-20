<?php

namespace Drupal\entity_overview\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides an "Entity Overview" block.
 *
 * @Block(
 *   id = "entity_overview_block",
 *   admin_label = @Translation("Entity Overview Block"),
 *   category = @Translation("SAHO Utilities")
 * )
 */
class EntityOverviewBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content_type' => 'article',
      'filter_term_id' => '',
      'sort_order' => 'latest',
      'limit' => 5,
      'intro_text' => 'Displaying the latest content from the %title section of the site.',
    ] + parent::defaultConfiguration();
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

    $form['content_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('Enter the machine name of the content type (e.g., article).'),
      '#default_value' => $this->configuration['content_type'],
    ];

    $form['filter_term_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Filter by Taxonomy Term'),
      '#description' => $this->t('Select a taxonomy term to filter the entities. Leave empty for no filtering.'),
      '#target_type' => 'taxonomy_term',
      '#default_value' => !empty($this->configuration['filter_term_id']) ? Term::load($this->configuration['filter_term_id']) : NULL,
      '#selection_settings' => [
        'target_bundles' => ['tags'],
      ],
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
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    // We no longer store a custom block title.
    $this->configuration['intro_text'] = $form_state->getValue('intro_text');
    $this->configuration['content_type'] = $form_state->getValue('content_type');
    $this->configuration['filter_term_id'] = $form_state->getValue('filter_term_id');
    $this->configuration['sort_order'] = $form_state->getValue('sort_order');
    $this->configuration['limit'] = $form_state->getValue('limit');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content_type = $this->configuration['content_type'];
    $filter_term_id = $this->configuration['filter_term_id'];
    $sort_order = $this->configuration['sort_order'];
    $limit = $this->configuration['limit'];
    // Use the built-in block label as the block title.
    $block_title = isset($this->configuration['label']) ? $this->configuration['label'] : '';
    $intro_text = $this->configuration['intro_text'];

    $query = \Drupal::entityQuery('node')
      ->condition('type', $content_type)
      ->condition('status', 1)
      ->range(0, $limit)
      ->accessCheck(TRUE);

    if (!empty($filter_term_id)) {
      $query->condition('field_tags.target_id', $filter_term_id);
    }

    if ($sort_order == 'latest') {
      $query->sort('created', 'DESC');
    }
    else {
      $query->sort('created', 'ASC');
    }

    $nids = $query->execute();
    $nodes = !empty($nids) ? Node::loadMultiple($nids) : [];
    $items = [];
    foreach ($nodes as $node) {
      $items[] = $this->buildEntityItem($node);
    }

    return [
      '#theme' => 'entity_overview_block',
      '#items' => $items,
      '#block_title' => $block_title,
      '#intro_text' => $intro_text,
    ];
  }

  /**
   * Builds a data array from the node.
   */
  protected function buildEntityItem(Node $node) {
    $image_url = '';
    if ($node->hasField('field_article_image') && !$node->get('field_article_image')->isEmpty()) {
      $file = $node->get('field_article_image')->entity;
      if ($file) {
        $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
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
