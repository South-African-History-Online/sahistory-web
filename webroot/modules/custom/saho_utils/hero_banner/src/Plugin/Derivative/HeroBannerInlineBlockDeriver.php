<?php

namespace Drupal\hero_banner\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for hero banner inline blocks.
 */
class HeroBannerInlineBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The block content storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * Constructs a HeroBannerInlineBlockDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_storage
   *   The block content storage.
   */
  public function __construct(EntityStorageInterface $block_content_storage) {
    $this->blockContentStorage = $block_content_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')->getStorage('block_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $block_contents = $this->blockContentStorage->loadByProperties([
      'type' => 'hero_banner',
    ]);

    foreach ($block_contents as $block_content) {
      $this->derivatives[$block_content->id()] = $base_plugin_definition;
      $this->derivatives[$block_content->id()]['admin_label'] = t('Hero Banner: @label', ['@label' => $block_content->label()]);
    }

    return $this->derivatives;
  }

}
