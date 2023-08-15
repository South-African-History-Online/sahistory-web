<?php

namespace Drupal\media_entity_bulk_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity_bulk_upload\Services\ZipUploadService;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media_entity_bulk_upload\Utility\FieldUtility;

/**
 * Upload form to render UI.
 */
class BulkForm extends FormBase {

  /**
   * The upload service.
   *
   * @var \Drupal\media_entity_bulk_upload\Services\ZipUploadService
   */
  protected $uploadService;

  /**
   * Constructor.
   */
  public function __construct(ZipUploadService $uploadService) {
    $this->uploadService = $uploadService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_entity_bulk_upload.bulk_upload')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_entity_bulk_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['target_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#description' => $this->t('The target image field of your media entitiy'),
      '#empty_option' => sprintf('- %s -', $this->t('Please select')),
      '#required' => TRUE,
      '#options' => FieldUtility::getMediaImageFields(),
    ];
    $form['target_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#description' => $this->t('The target media bundle of your entity'),
      '#empty_option' => sprintf('- %s -', $this->t('Please select')),
      '#required' => TRUE,
      '#options' => FieldUtility::getMediaFieldBundles(),
    ];
    $form['zip'] = [
      '#type' => 'managed_file',
      '#title' => t('Upload Zip File'),
      '#upload_location' => 'temporary://' . $this->getFormId() . '/',
      '#description' => t('The .ZIP containing image files'),
      '#upload_validators' => [
        'file_validate_extensions' => ['zip'],
      ],
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = $form_state->getValue(['zip', 0]);
    $field = $form_state->getValue('target_field');
    $bundle = $form_state->getValue('target_bundle');
    if (!empty($fid)) {
      $file = File::load($fid);
      try {
        $media = $this->uploadService->uploadMedia('temporary://' . $this->getFormId() . '/', $file, $bundle, $field);
        drupal_set_message($this->t('Success. Saved :size media entities.', [':size' => count($media)]));
      }
      catch (Exception $e) {
        drupal_set_message($e->getMessage());
      }
    }
  }

}
