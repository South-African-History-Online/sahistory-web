<?php

namespace Drupal\media_bulk_upload\Form;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MediaBulkConfigForm.
 */
class MediaBulkConfigForm extends EntityForm {

  /**
   * Entity Display Repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * MediaBulkConfigForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   Entity Display repository.
   */
  public function __construct(EntityDisplayRepositoryInterface $entityDisplayRepository) {
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\media_bulk_upload\Entity\MediaBulkConfigInterface $mediaBulkConfig */
    $mediaBulkConfig = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $mediaBulkConfig->label(),
      '#description' => $this->t("Label for the Media Bulk Config."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $mediaBulkConfig->id(),
      '#machine_name' => [
        'exists' => '\Drupal\media_bulk_upload\Entity\MediaBulkConfig::load',
      ],
      '#disabled' => !$mediaBulkConfig->isNew(),
    ];

    $media_types = $mediaBulkConfig->get('media_types');
    $form['media_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Media Types'),
      '#description' => $this->t('Choose the media types that will be 
        used to create new media entities based on matching extensions. Please be 
        aware that if file extensions overlap between the media types that are 
        chosen, that the media entity will be assigned automatically to one of 
        these types.'),
      '#options' => $this->getMediaTypeOptions(),
      '#default_value' => isset($media_types) ? $media_types : [],
      '#size' => 20,
      '#multiple' => TRUE,
    ];

    $form['form_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Form Mode'),
      '#description' => $this->t('Based on the form mode the upload form 
        can be enriched with fields that are available, improving the speed and 
        usability to add (meta)data to your media entities.'),
      '#options' => $this->entityDisplayRepository->getFormModeOptions('media'),
      "#empty_option" => t('- None -'),
      '#default_value' => $mediaBulkConfig->get('form_mode'),
    ];

    return $form;
  }

  /**
   * Get the available media type options.
   */
  private function getMediaTypeOptions() {
    $mediaTypeStorage = $this->entityTypeManager->getStorage('media_type');
    $mediaTypes = $mediaTypeStorage->loadMultiple();

    foreach ($mediaTypes as $mediaType) {
      $mediaTypeOptions[$mediaType->id()] = $mediaType->label();
    }
    natsort($mediaTypeOptions);

    return $mediaTypeOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $media_bulk_config = $this->entity;
    $status = $media_bulk_config->save();

    $save_message = $this->t('Saved the %label Media Bulk Config.', [
      '%label' => $media_bulk_config->label(),
    ]);

    if ($status == SAVED_NEW) {
      $save_message = $this->t('Created the %label Media Bulk Config.', [
        '%label' => $media_bulk_config->label(),
      ]);
    }

    drupal_set_message($save_message);

    $form_state->setRedirectUrl($media_bulk_config->toUrl('collection'));
  }

}
