<?php

namespace Drupal\featured_articles\Plugin\Block;

use Drupal\file\FileInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\Node;
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
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'use_manual_override' => FALSE,
      'manual_entity_id' => NULL,
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

    $form['manual_entity_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Manual Article'),
      '#description' => $this->t('Choose the article to display if override is enabled.'),
      '#target_type' => 'node',
      '#selection_handler' => 'default:node',
      '#selection_settings' => [
        'target_bundles' => ['article'],
      ],
      '#default_value' => $this->configuration['manual_entity_id']
        ? Node::load($this->configuration['manual_entity_id'])
        : NULL,
      '#states' => [
        'visible' => [
          ':input[name="use_manual_override"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['use_manual_override'] = $form_state->getValue('use_manual_override');
    $this->configuration['manual_entity_id'] = $form_state->getValue('manual_entity_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $manual_override = $this->configuration['use_manual_override'];
    $manual_entity_id = $this->configuration['manual_entity_id'];

    // If manual override is set, display that specific article.
    if ($manual_override && $manual_entity_id) {
      $node = Node::load($manual_entity_id);
      if ($node) {
        return [
          '#theme' => 'featured_article_block',
          '#article_item' => $this->buildArticleItem($node),
        ];
      }
    }

    // Otherwise, pick a random article that has BOTH field_home_page_feature=1 AND field_staff_picks=1.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->condition('field_home_page_feature', 1)
      ->condition('field_staff_picks', 1)
      ->range(0, 50)
      ->accessCheck(TRUE)
      ->execute();

    if (empty($nids)) {
      return [
        '#theme' => 'featured_article_block',
        '#article_item' => NULL,
      ];
    }

    $nids_array = array_values($nids);
    shuffle($nids_array);
    $nid = reset($nids_array);
    $node = Node::load($nid);

    return [
      '#theme' => 'featured_article_block',
      '#article_item' => $node ? $this->buildArticleItem($node) : NULL,
    ];
  }

  /**
   * Builds a data array from the article node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity to build the article item from.
   *
   * @return array
   *   An array containing the article data with keys:
   *   - id: The node ID
   *   - title: The node title
   *   - url: The node URL
   *   - image: The image URL if available
   */
  protected function buildArticleItem(Node $node) {
    $image_url = '';
    if ($node->hasField('field_article_image') && !$node->get('field_article_image')->isEmpty()) {
      $file = $node->get('field_article_image')->entity;
      if ($file) {
        // Check if the entity implements FileInterface or is a File entity.
        if ($file instanceof FileInterface) {
          $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        }
        // Fallback: check if the entity has a getFileUri method and is a file entity.
        elseif ($file->getEntityTypeId() === 'file' && method_exists($file, 'getFileUri')) {
          $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        }
        // Last resort: try to load it as a media entity and get the source file.
        elseif ($file->getEntityTypeId() === 'media') {
          // Check if this is a media entity that has a source plugin.
          if (method_exists($file, 'getSource') && $file->getSource()) {
            $source_field = $file->getSource()->getConfiguration()['source_field'];
            // Check if the media entity has the source field.
            if (method_exists($file, 'get') && !empty($file->get($source_field)->entity)) {
              $source_file = $file->get($source_field)->entity;
              if ($source_file instanceof FileInterface) {
                $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($source_file->getFileUri());
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
    // If you need to inject services, do so here:
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

}
