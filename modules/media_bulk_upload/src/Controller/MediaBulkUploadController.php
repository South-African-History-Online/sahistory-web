<?php

namespace Drupal\media_bulk_upload\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MediaUploadController.
 *
 * @package Drupal\media_upload\Controller
 */
class MediaBulkUploadController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new MediaBulkUploadController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Displays add links for the available bundles.
   *
   * Redirects to the add form if there's only one bundle available.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   If there's only one available bundle, a redirect response.
   *   Otherwise, a render array with the add links for each bundle.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function addLinksList() {
    $build = [
      '#theme' => 'entity_add_list',
      '#bundles' => [],
    ];
    $entity_type = $this->entityTypeManager->getDefinition('media_bulk_config');
    $entity_type_label = $entity_type->getLowercaseLabel();
    $build['#cache']['tags'] = $entity_type->getListCacheTags();

    $link_text = $this->t('Add a new @entity_type.', ['@entity_type' => $entity_type_label]);
    $link_route_name = 'entity.' . $entity_type->id() . '.add_form';
    $build['#add_bundle_message'] = $this->t('There is no @entity_type yet. @add_link', [
      '@entity_type' => $entity_type_label,
      '@add_link' => Link::createFromRoute($link_text, $link_route_name)
        ->toString(),
    ]);

    $mediaBulkConfigStorage = $this->entityTypeManager->getStorage('media_bulk_config');
    $mediaBulkConfigEntities = $mediaBulkConfigStorage->loadMultiple();

    $form_route_name = 'media_bulk_upload.upload_form';
    if (count($mediaBulkConfigEntities) == 1) {
      $mediaBulkConfigEntity = reset($mediaBulkConfigEntities);
      return $this->redirect($form_route_name, ['media_bulk_config' => $mediaBulkConfigEntity->id()]);
    }

    foreach ($mediaBulkConfigEntities as $mediaBulkConfigEntity) {
      $link = Link::createFromRoute($mediaBulkConfigEntity->label(), $form_route_name, ['media_bulk_config' => $mediaBulkConfigEntity->id()]);
      if (!$link->getUrl()->access()) {
        continue;
      }

      $build['#bundles'][$mediaBulkConfigEntity->id()] = [
        'label' => $mediaBulkConfigEntity->label(),
        'description' => '',
        'add_link' => Link::createFromRoute($mediaBulkConfigEntity->label(), $form_route_name, ['media_bulk_config' => $mediaBulkConfigEntity->id()]),
      ];
    }

    return $build;
  }

  /**
   * Access callback to validate if the user has access to the upload form list.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to validate access on.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function accessList(AccountInterface $account) {
    if ($account->hasPermission('administer media_bulk_upload configuration')) {
      return AccessResult::allowed();
    }

    $mediaBulkConfigStorage = $this->entityTypeManager->getStorage('media_bulk_config');
    $mediaBulkConfigEntities = $mediaBulkConfigStorage->loadMultiple();
    foreach ($mediaBulkConfigEntities as $mediaBulkConfig) {
      $url = Url::fromRoute('media_bulk_upload.upload_form', ['media_bulk_config' => $mediaBulkConfig->id()]);
      if ($url->access()) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden('No media bulk config entity accessible for the user.');
  }

  /**
   * Access callback to validate if the user has access to a bulk upload form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to validate access on.
   * @param \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface $media_bulk_config
   *   The media bulk config entity the upload form belongs to.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   */
  public function accessForm(AccountInterface $account, MediaBulkConfigInterface $media_bulk_config) {
        $mediaBulkConfigId = $media_bulk_config->id();

    if(!$account->hasPermission("use $mediaBulkConfigId bulk upload form")) {
      return AccessResult::forbidden('Media Bulk Upload form is not accessible for the user.');
    }

    return AccessResult::allowed();
  }

}
