<?php

namespace Drupal\glazed_builder\Service\Handler;

use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;

class BlockHandler implements BlockHandlerInterface {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The block manager
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The current user
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The renderer service
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity repository service
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Construct a BlockHandler entity
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager for content blocks created through the admin interface
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager for blocks created through plugins
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, BlockManagerInterface $blockManager, AccountProxyInterface $currentUser, RendererInterface $renderer, EntityRepositoryInterface $entityRepository) {
    $this->entityTypeManager = $entityTypeManager;
    $this->blockManager = $blockManager;
    $this->currentUser = $currentUser;
    $this->renderer = $renderer;
    $this->entityRepository = $entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlock($blockInfo, AttachedAssets $assets, array $data) {
    $output = '';

    if ($blockInfo['provider'] == 'block_content') {
      // Content blocks are loaded by UUID
      $block = $this->entityRepository->loadEntityByUuid('block_content', $blockInfo['uuid']);
      if ($block && $block->access('view', $this->currentUser)) {
        $render = $this->entityTypeManager->getViewBuilder('block_content')->view($block);
      }
    }
    else {
      $block = $this->blockManager->createInstance($blockInfo['id'], []);

      if ($block && $block->access($this->currentUser)) {

        $definition = $block->getPluginDefinition();
        if (isset($data['display_title']) && $data['display_title'] == 'yes' && $definition['admin_label']) {
          $render['title'] = array(
            '#type' => 'container',
            '#attributes' => array(
              'class' => array('views-title'),
            ),
            'title' => array(
              '#type' => 'html_tag',
              '#tag' => 'h2',
              '#value' => $definition['admin_label'],
            ),
          );
        }

        $render['content'] = $block->build();
      }
    }

    $rendered = $this->renderer->renderRoot($render);

    if (isset($render['#attached'], $render['#attached']['library']) && is_array($render['#attached']['library'])) {
      $assets->setLibraries($render['#attached']['library']);
    }

    if(isset($render['#attached'], $render['#attached']['drupalSettings'])) {
      $assets->setSettings($render['#attached']['drupalSettings']);
    }

    if (is_string($rendered)) {
      return $rendered;
    }

    return $rendered->__toString();
  }
}
