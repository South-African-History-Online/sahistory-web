<?php

namespace Drupal\csv_importer\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a base class for ImporterBase plugins.
 *
 * @see \Drupal\csv_importer\Annotation\Importer
 * @see \Drupal\csv_importer\Plugin\ImporterManager
 * @see \Drupal\csv_importer\Plugin\ImporterInterface
 * @see plugin_api
 */
abstract class ImporterBase extends PluginBase implements ImporterInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs ImporterBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function data() {
    $csv = $this->configuration['csv'];
    $return = [];

    if ($csv && is_array($csv)) {
      $csv_fields = $csv[0];
      unset($csv[0]);
      foreach ($csv as $index => $data) {
        foreach ($data as $key => $content) {
          if ($content && isset($csv_fields[$key])) {
            $content = Unicode::convertToUtf8($content, mb_detect_encoding($content));
            $fields = explode('|', $csv_fields[$key]);

            if ($fields[0] == 'translation') {
              if (count($fields) > 3) {
                $return['translations'][$index][$fields[3]][$fields[1]][$fields[2]] = $content;
              }
              else {
                $return['translations'][$index][$fields[2]][$fields[1]] = $content;
              }
            }
            else {
              $field = $fields[0];
              if (count($fields) > 1) {
                foreach ($fields as $key => $in) {
                  $return['content'][$index][$field][$in] = $content;
                }
              }
              else if (isset($return['content'][$index][$field])) {
                $prev = $return['content'][$index][$field];
                $return['content'][$index][$field] = [];

                if (is_array($prev)) {
                  $prev[] = $content;
                  $return['content'][$index][$field] = $prev;
                }
                else {
                  $return['content'][$index][$field][] = $prev;
                  $return['content'][$index][$field][] = $content;
                }
              }
              else {
                $return['content'][$index][current($fields)] = $content;
              }
            }
          }
        }

        if (isset($return[$index])) {
          $return['content'][$index] = array_intersect_key($return[$index], array_flip($this->configuration['fields']));
        }
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function add($content, array &$context) {
    if (!$content) {
      return NULL;
    }

    $entity_type = $this->configuration['entity_type'];
    $entity_type_bundle = $this->configuration['entity_type_bundle'];
    $entity_definition = $this->entityTypeManager->getDefinition($entity_type);

    $added = 0;
    $updated = 0;

    foreach ($content['content'] as $key => $data) {
      if ($entity_definition->hasKey('bundle') && $entity_type_bundle) {
        $data[$entity_definition->getKey('bundle')] = $this->configuration['entity_type_bundle'];
      }
      
      /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $entity_storage  */
      $entity_storage = $this->entityTypeManager->getStorage($this->configuration['entity_type']);
      
      try {
        if (isset($data[$entity_definition->getKeys()['id']]) && $entity = $entity_storage->load($data[$entity_definition->getKeys()['id']])) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity  */
          foreach ($data as $id => $set) {
            $entity->set($id, $set);
          }

          $this->preSave($entity, $data, $context);
  
          if ($entity->save()) {
            $updated++;
          }
        }
        else {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity  */
          $entity = $this->entityTypeManager->getStorage($this->configuration['entity_type'])->create($data);
          
          $this->preSave($entity, $data, $context);

          if ($entity->save()) {
            $added++;
          }
        }

        if (isset($content['translations'][$key]) && is_array($content['translations'][$key])) {
          foreach ($content['translations'][$key] as $code => $translations) {
            $entity_data = array_replace($translations, $translations);

            if ($entity->hasTranslation($code)) {
              $entity_translation = $entity->getTranslation($code);

              foreach ($entity_data as $key => $translation_data) {
                $entity_translation->set($key, $translation_data);
              } 
            }
            else {
              $entity_translation = $entity->addTranslation($code, $entity_data);
            }

            $entity_translation->save();
          }
        }
      }
      catch (\Exception $e) {
      }
    }

    $context['results'] = [$added, $updated];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    $operations[] = [
      [$this, 'add'],
      [$this->data()],
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function finished($success, $results, array $operations) {
    $message = '';

    if ($success) {
      $message = $this->t('@count_added content added and @count_updated updated', ['@count_added' => isset($results[0]) ? $results[0] : 0, '@count_updated' => isset($results[1]) ? $results[1] : 0]);
    }

    drupal_set_message($message);
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    $process = [];
    if ($operations = $this->getOperations()) {
      $process['operations'] = $operations;
    }

    $process['finished'] = [$this, 'finished'];

    batch_set($process);
  }

  /**
   * Override entity before run Entity::save().
   *
   * @param mixed $entity
   *   The entity object.
   * @param array $content
   *   The content array to be saved.
   * @param array $context
   *   The batch context array.
   */
  public function preSave(&$entity, array $content, array &$context) {}

}
