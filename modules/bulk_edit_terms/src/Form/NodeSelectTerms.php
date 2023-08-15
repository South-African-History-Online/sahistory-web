<?php

namespace Drupal\bulk_edit_terms\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a confirmation form for cancelling multiple user accounts.
 */
class NodeSelectTerms extends ConfirmFormBase {

  /**
   * The array of nodes to edit terms from.
   *
   * @var string[][]
   */
  protected $nodeInfo = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager;
   */
  protected $manager;

  /**
   * Constructs a NodeSelectTerms form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param  \Drupal\Core\Entity\EntityTypeManager $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManager $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_select_terms';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Which term reference fields do you want to update?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Update terms');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->nodeInfo = $this->tempStoreFactory->get('node_edit_terms')->get(\Drupal::currentUser()->id());
    if (empty($this->nodeInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $form['intro'] = [
      '#markup' => $this->t('<p>The following fields appear in at least one of the selected nodes.<br/>Select values and these will be applied only to the nodes that have that field.</p>'),
    ];

    // Gather all taxonomy fields from the nodes and offer the options available
    // to select.
    $terms_fields_present = FALSE;
    foreach ($this->nodeInfo as $node) {
      /** @var \Drupal\node\Entity\Node $node */
      $fields = $node->getFieldDefinitions();
      foreach ($fields as $name => $field) {
        if (empty($form[$name])) {
          /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
          if ($field->getType() == 'entity_reference' && strpos($name, 'field_') !== FALSE) {
            $terms_fields_present = TRUE;

            $field_info = $field->getFieldStorageDefinition();
            $field_settings = $field->getSettings();
            $target_bundles = $field_settings['handler_settings']['target_bundles'];

            $auto_create = (!empty($field->getSettings()['handler_settings']['auto_create'])) ?
              $field->getSettings()['handler_settings']['auto_create'] :
              FALSE;

            $auto_create_bundle = ($auto_create) ?
              $field_settings['handler_settings']['auto_create_bundle'] :
              NULL;

            $description = ($field_info->getCardinality() == 1) ?
              'Change the existing value to this one.' :
              'Selected terms will be added.';

            if ($field_info->getCardinality() == 1 && $target_bundles && !$auto_create) {
              // Search terms and create select element.
              $terms = [];
              foreach ($target_bundles as $target_bundle) {
                $tree = $this->manager->getStorage('taxonomy_term')->loadTree($target_bundle);
                if (!empty($tree)) {
                  foreach ($tree as $item) {
                    $terms[$item->tid] = $this->t($item->name);
                  }
                }
              }

              // Create form select element.
              $options = ['' => '- Select -'];
              $options += $terms;
              $form[$name] = [
                '#type' => 'select',
                '#title' => $field->getLabel(),
                '#options' => $options,
                '#description' => $this->t($description),
              ];
            }
            else {
              // Offer auto-complete field.
              $form[$name] = [
                '#type' => 'entity_autocomplete',
                '#target_type' => $field_settings['target_type'],
                '#title' => $field->getLabel(),
                '#description' => $this->t($description),
                '#default_value' => FALSE,
                '#tags' => TRUE,
                '#selection_settings' => [
                  'target_bundles' => $target_bundles,
                ],
              ];

              if ($auto_create && $auto_create_bundle) {
                $form[$name]['#autocreate'] = [
                  'bundle' => $auto_create_bundle,
                ];
              }
            }
          }
        }
      }// endforeach
    }// endforeach
    if (!$terms_fields_present) {
      $form['warning'] = [
        '#markup' => $this->t('<p><b>No term reference fields were found in the selected nodes.</b></p>'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->nodeInfo)) {
      $values = $form_state->getValues();
      foreach ($this->nodeInfo as $node) {
        /** @var \Drupal\node\Entity\Node $node */
        foreach ($values as $name => $value) {
          $field = $node->getFieldDefinition($name);
          // Does this node have the field and is there any value provided?
          if ($field && $value) {
            if ($field->getFieldStorageDefinition()->getCardinality() !== 1) {
              $value = array_merge($value, $node->get($name)->getValue());
            }
            $node->set($name, $value);
          }
        }
        $node->save();
      }

      $this->messenger()->addStatus(count($this->nodeInfo) . ' nodes were updated');
    }
  }

}
