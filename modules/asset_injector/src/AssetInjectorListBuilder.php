<?php

namespace Drupal\asset_injector;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Asset Injector entities.
 */
class AssetInjectorListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Injector');
    $header['conditions'] = $this->t('Conditions');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $data['label'] = $entity->label();

    $data['conditions'] = [];

    /** @var \Drupal\Core\Condition\ConditionPluginBase $condition */
    foreach ($entity->getConditionsCollection() as $condition_id => $condition) {
      if ($condition_id == 'current_theme') {
        $config = $condition->getConfiguration();
        $condition->setConfiguration(['theme' => implode(', ', $config['theme'])] + $config);
      }

      $data['conditions'][$condition_id] = $this->t('%plugin is configured.', ['%plugin' => $condition->getPluginDefinition()['label']]);
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $summary */
      if ($summary = $condition->summary()) {
        $data['conditions'][$condition_id] = Html::decodeEntities($summary->render());
      }
    }

    $data['conditions'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => empty($data['conditions']) ? [$this->t('Global')] : $data['conditions'],
    ];
    $data['conditions'] = render($data['conditions']);

    $row = [
      'class' => $entity->status() ? 'enabled' : 'disabled',
      'data' => $data + parent::buildRow($entity),
    ];
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->hasLinkTemplate('duplicate-form')) {
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'weight' => 15,
        'url' => $entity->toUrl('duplicate-form'),
      ];
    }

    // Only alter edit link if we have access.
    if (isset($operations['edit']['url'])) {
      // Remove query option to allow the save and continue to correctly function.
      $options = $operations['edit']['url']->getOptions();
      unset($options['query']);
      $operations['edit']['url']->setOptions($options);
    }
    return $operations;
  }

}
