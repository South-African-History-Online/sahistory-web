<?php

namespace Drupal\entity_import\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\entity_import\Form\EntityImporterOptionsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Define the entity import mapping list controller.
 */
class EntityImporterFieldMappingList extends ConfigEntityListBuilder {

  /**
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    FormBuilderInterface $form_builder,
    RequestStack $request_stack,
    EntityStorageInterface $storage
  ) {
    parent::__construct($entity_type, $storage);
    $this->request = $request_stack->getCurrentRequest();
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    return new static (
      $entity_type,
      $container->get('form_builder'),
      $container->get('request_stack'),
      $container->get('entity_type.manager')->getStorage($entity_type->id())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
        $this->t('Label'),
        $this->t('Machine Name'),
        $this->t('Source Name'),
        $this->t('Destination'),
        $this->t('Target Bundle'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\entity_import\Entity\EntityImporterFieldMapping $entity */
    return [
        $entity->label(),
        $entity->name(),
        $entity->getSource(),
        $entity->getDestination(),
        $entity->getImporterBundle(),
    ] + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return parent::render() + [
      'options' => $this->buildFieldMappingOptionForm()
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    if ($this->request->attributes->has('entity_importer')) {
      $entity_importer_type = $this->request->get('entity_importer');

      $query = $this->getStorage()->getQuery()
        ->condition('importer_type', $entity_importer_type)
        ->sort($this->entityType->getKey('id'));

      if ($this->limit) {
        $query->pager($this->limit);
      }

      return $query->execute();
    }

    return [];
  }

  /**
   * Builder field mapping option form.
   *
   * @return array
   *   The form render array.
   */
  protected function buildFieldMappingOptionForm() {
    $entity_importer_type = $this->request->get('entity_importer');

    return $this->formBuilder->getForm(
      EntityImporterOptionsForm::class,
      $entity_importer_type
    );
  }
}
