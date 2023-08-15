<?php

namespace Drupal\entity_import\Plugin\migrate\source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;

/**
 * Define entity import source interface.
 */
interface EntityImportSourceInterface extends MigrateSourceInterface, PluginFormInterface {

  /**
   * Source is valid.
   *
   * @return boolean
   */
  public function isValid();

  /**
   * Get source label.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Skip source cleanup process.
   *
   * @return $this
   */
  public function skipCleanup();

  /**
   * Run source clean up tasks.
   */
  public function runCleanup();

  /**
   * Check source required.
   *
   * @return bool
   */
  public function isRequired();

  /**
   * Set source as required.
   *
   * @return $this
   */
  public function setRequired();

  /**
   * Build import form.
   *
   * @param array $form
   *   An array of the form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form elements.
   */
  public function buildImportForm(array $form, FormStateInterface $form_state);

  /**
   * Validate import form.
   *
   * @param array $form
   *   An array of the form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateImportForm(array $form, FormStateInterface $form_state);

  /**
   * Submit import form.
   *
   * @param array $form
   *   An array of the form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitImportForm(array &$form, FormStateInterface $form_state);
}
