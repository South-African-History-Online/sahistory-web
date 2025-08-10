<?php

namespace Drupal\hero_banner\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Hero Banner' block.
 *
 * @Block(
 *   id = "hero_banner_block",
 *   admin_label = @Translation("Hero Banner Block"),
 *   category = @Translation("All custom"),
 * )
 */
class HeroBannerBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new HeroBannerBlock instance.
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
      'title' => '',
      'subtitle' => '',
      'body' => [
        'value' => '',
        'format' => 'basic_html',
      ],
      'background_image' => NULL,
      'call_to_action' => [
        'title' => '',
        'uri' => '',
      ],
      'overlay_opacity' => 50,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config['title'],
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subtitle'),
      '#default_value' => $config['subtitle'],
      '#maxlength' => 255,
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $config['body']['value'],
      '#format' => $config['body']['format'],
      '#rows' => 5,
    ];

    // Load media entity for default value if available.
    $default_media_entity = NULL;
    if (!empty($config['background_image'])) {
      $default_media_entity = $this->entityTypeManager->getStorage('media')->load($config['background_image']);
    }

    $form['background_image'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'media',
      '#selection_settings' => [
        'target_bundles' => ['image'],
      ],
      '#title' => $this->t('Hero Banner Image'),
      '#default_value' => $default_media_entity,
      '#description' => $this->t('Start typing to search for an image, or upload a new one through the media library.'),
      '#maxlength' => 1024,
    ];

    $form['overlay_opacity'] = [
      '#type' => 'range',
      '#title' => $this->t('Overlay Opacity'),
      '#default_value' => $config['overlay_opacity'],
      '#min' => 0,
      '#max' => 100,
      '#step' => 10,
      '#description' => $this->t('Set the overlay opacity for the background image (0 = transparent, 100 = opaque).'),
    ];

    $form['call_to_action'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Call to Action Button'),
    ];

    $form['call_to_action']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#default_value' => $config['call_to_action']['title'],
      '#maxlength' => 255,
    ];

    $form['call_to_action']['uri'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Link'),
      '#target_type' => 'node',
      '#default_value' => $this->getNodeFromUri($config['call_to_action']['uri']),
      '#description' => $this->t('Start typing the title of a piece of content to select it. You can also enter an external URL such as https://example.com.'),
      '#process_default_value' => FALSE,
      '#element_validate' => [[$this, 'validateUriElement']],
    ];

    return $form;
  }

  /**
   * Form element validation handler for the 'uri' element.
   */
  public function validateUriElement($element, FormStateInterface $form_state, $form) {
    $uri = $this->getUserEnteredStringAsUri($element['#value']);
    $form_state->setValueForElement($element, $uri);
  }

  /**
   * Gets the user-entered string as a URI.
   *
   * @param string $string
   *   The user-entered string.
   *
   * @return string
   *   The URI.
   */
  protected function getUserEnteredStringAsUri($string) {
    if (empty($string)) {
      return '';
    }

    if (strpos($string, '(') !== FALSE && preg_match('/^(.*)\s\((\d+)\)$/', $string, $matches)) {
      return 'entity:node/' . $matches[2];
    }

    if (parse_url($string, PHP_URL_SCHEME) === NULL) {
      if (strpos($string, '<front>') === 0) {
        return 'internal:' . $string;
      }
      if (strpos($string, '/') === 0) {
        return 'internal:' . $string;
      }
      return 'internal:/' . $string;
    }

    return $string;
  }

  /**
   * Get node from URI.
   *
   * @param string $uri
   *   The URI.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The node entity or NULL.
   */
  protected function getNodeFromUri($uri) {
    if (empty($uri)) {
      return NULL;
    }

    if (strpos($uri, 'entity:node/') === 0) {
      $nid = substr($uri, strlen('entity:node/'));
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      return $node;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['title'] = $values['title'];
    $this->configuration['subtitle'] = $values['subtitle'];
    $this->configuration['body'] = $values['body'];
    // Entity autocomplete returns an entity object, store just the ID.
    if (!empty($values['background_image'])) {
      if (is_object($values['background_image'])) {
        $this->configuration['background_image'] = $values['background_image']->id();
      }
      else {
        $this->configuration['background_image'] = $values['background_image'];
      }
    }
    else {
      $this->configuration['background_image'] = NULL;
    }
    $this->configuration['overlay_opacity'] = $values['overlay_opacity'];
    $this->configuration['call_to_action']['title'] = $values['call_to_action']['title'];
    $this->configuration['call_to_action']['uri'] = $values['call_to_action']['uri'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $build = [];

    $background_image_url = NULL;
    if (!empty($config['background_image'])) {
      $media_id = $config['background_image'];
      $media = $this->entityTypeManager->getStorage('media')->load($media_id);
      if ($media && $media->hasField('field_media_image')) {
        $image_field = $media->get('field_media_image');
        if (!$image_field->isEmpty()) {
          $file = $image_field->entity;
          if ($file) {
            $background_image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
          }
        }
      }
    }

    $button_url = NULL;
    $button_attributes = ['class' => ['hero-banner__button', 'btn', 'btn-primary']];
    if (!empty($config['call_to_action']['uri'])) {
      try {
        $url = Url::fromUri($config['call_to_action']['uri']);
        $button_url = $url->toString();
        if ($url->isExternal()) {
          $button_attributes['target'] = '_blank';
          $button_attributes['rel'] = 'noopener noreferrer';
        }
      }
      catch (\Exception $e) {
        $button_url = NULL;
      }
    }

    $build = [
      '#theme' => 'hero_banner_block',
      '#title' => $config['title'],
      '#subtitle' => $config['subtitle'],
      '#body' => check_markup($config['body']['value'], $config['body']['format']),
      '#background_image' => $background_image_url,
      '#button_text' => $config['call_to_action']['title'],
      '#button_url' => $button_url,
      '#button_attributes' => $button_attributes,
      '#overlay_opacity' => $config['overlay_opacity'] / 100,
      '#attached' => [
        'library' => [
          'hero_banner/hero_banner',
        ],
      ],
    ];

    return $build;
  }

}
