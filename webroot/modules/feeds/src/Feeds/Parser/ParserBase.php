<?php

namespace Drupal\feeds\Feeds\Parser;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\MappingPluginFormInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;

/**
 * Base class for Feeds parsers.
 */
abstract class ParserBase extends PluginBase implements ParserInterface, MappingPluginFormInterface {

  /**
   * Returns the label for single source.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   A translated string if the source has a special name. Null otherwise.
   */
  protected function configSourceLabel() {
    return NULL;
  }

  /**
   * Returns the description for single source.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   A translated string if there's a description. Null otherwise.
   */
  protected function configSourceDescription() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormAlter(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function mappingFormValidate(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function mappingFormSubmit(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function getSupportedCustomSourcePlugins(): array {
    return [];
  }

}
