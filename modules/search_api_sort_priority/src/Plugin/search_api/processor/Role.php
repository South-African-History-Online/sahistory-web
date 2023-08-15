<?php

namespace Drupal\search_api_sort_priority\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\user\RoleInterface;
use Drupal\Component\Utility\Html;
use Drupal\node\NodeInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\user\Entity\User;

/**
 * Adds customized sort priority by Role.
 *
 * @SearchApiProcessor(
 *   id = "role",
 *   label = @Translation("Sort Priority by Role"),
 *   description = @Translation("Sort Priority by Role."),
 *   stages = {
 *     "add_properties" = 20,
 *     "pre_index_save" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class Role extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  protected $targetFieldId = 'role_weight';

  /**
   * Can only be enabled for an index that indexes user related entity.
   *
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'node') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        // TODO Come up with better label.
        'label' => $this->t('Sort Priority by Role'),
        // TODO Come up with better description.
        'description' => $this->t('Sort Priority by Role.'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
        // This will be a hidden field,
        // not something a user can add/remove manually.
        'hidden' => TRUE,
      ];
      $properties[$this->targetFieldId] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get default weight.
    $weight = $this->configuration['weight'];

    // Only run for node and comment items.
    // TODO Extend for other entities.
    $entity_type_id = $item->getDatasource()->getEntityTypeId();
    if (!in_array($entity_type_id, $this->configuration['allowed_entity_types'])) {
      return;
    }

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, $this->targetFieldId);

    // TODO Extend for other entities.
    switch ($entity_type_id) {
      case 'node':
        // Get the node object.
        $node = $this->getNode($item->getOriginalObject());

        // Get the user associated with this node.
        $user = User::load($node->getOwnerId());

        // Get user roles.
        $user_roles = $user->getRoles();

        // Construct array for role sorting.
        foreach ($user_roles as $role_id) {
          $weight = $this->configuration['sorttable'][$role_id]['weight'];
          $role_weights[$role_id]['role'] = $role_id;
          $role_weights[$role_id]['weight'] = $weight;
        }

        // Sort the roles by weight.
        uasort($role_weights, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
        $highest_role_weight = array_values($role_weights)[0];

        // Set the weight on all the configured fields.
        foreach ($fields as $field) {
          $field->addValue($highest_role_weight['weight']);
        }
        break;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'weight' => 0,
      'allowed_entity_types' => [
        'node',
        'comment',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['sorttable'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Role'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'sorttable-order-weight',
        ],
      ],
    ];

    $master_roles = user_roles();
    $roles = array_map(function (RoleInterface $role) {
      return Html::escape($role->label());
    }, $master_roles);

    // Make a dummy array to add custom weight.
    foreach ($roles as $role_id => $role_name) {
      $weight = $master_roles[$role_id]->getWeight();
      if (isset($this->configuration['sorttable']) && isset($this->configuration['sorttable'][$role_id]['weight'])) {
        $weight = $this->configuration['sorttable'][$role_id]['weight'];
      }

      $role_weight[$role_id]['bundle_id'] = $role_id;
      $role_weight[$role_id]['bundle_name'] = $role_name;
      $role_weight[$role_id]['weight'] = $weight;
    }

    // Sort weights.
    uasort($role_weight, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Loop over each role and create a form row.
    foreach ($role_weight as $role_id => $role) {
      $weight = $role['weight'];
      $role_name = $role['bundle_name'];

      // Add form with weights
      // Mark the table row as draggable.
      $form['sorttable'][$role_id]['#attributes']['class'][] = 'draggable';

      // Sort the table row according to its existing/configured weight.
      $form['sorttable'][$role_id]['#weight'] = $weight;

      // Table columns containing raw markup.
      $form['sorttable'][$role_id]['label']['#plain_text'] = $role_name;

      // Weight column element.
      $form['sorttable'][$role_id]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $role_name]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        // Classify the weight element for #tabledrag.
        '#attributes' => ['class' => ['sorttable-order-weight']],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    // Automatically add field to index if processor is enabled.
    $field = $this->ensureField(NULL, $this->targetFieldId, 'integer');
    // Hide the field.
    $field->setHidden();
  }

  /**
   * Retrieves the node related to an indexed search object.
   *
   * Will be either the node itself, or the node the comment is attached to.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   A search object that is being indexed.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node related to that search object.
   */
  protected function getNode(ComplexDataInterface $item) {
    $item = $item->getValue();
    if ($item instanceof CommentInterface) {
      $item = $item->getCommentedEntity();
    }
    if ($item instanceof NodeInterface) {
      return $item;
    }

    return NULL;
  }

}
