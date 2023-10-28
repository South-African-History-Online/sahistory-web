<?php

namespace Drupal\entity_import_plus\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_import\Plugin\migrate\process\EntityImportProcessInterface;
use Drupal\entity_import\Plugin\migrate\process\EntityImportProcessTrait;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate_plus\Plugin\migrate\process\StrReplace;

/**
 * Define entity import plus string replace process.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_import_plus_str_replace",
 *   label = @Translation("String Replace")
 * )
 */
class EntityImportPlusStrReplace extends StrReplace implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfigurations() {
    return [
      'search' => [],
      'replace' => [],
      'regex' => FALSE,
      'case_insensitive' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['search'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Search'),
      '#description' => $this->t(
        'Input a search term. If multiple, each term will need to be placed on a 
        separate line.'
      ),
      '#required' => TRUE,
      '#default_value' => implode(
        "\r\n",
        $this->getFormStateValue('search', $form_state, [])
      ),
    ];
    $form['replace'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Replace'),
      '#description' => $this->t(
        'Input a replace term. If multiple, each term will need to be placed on a 
        separate line. '
      ),
      '#required' => TRUE,
      '#default_value' => implode(
        "\r\n",
        $this->getFormStateValue('replace', $form_state, [])
      ),
    ];
    $form['regex'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Regex'),
      '#default_value' => $this->getFormStateValue('regex', $form_state)
    ];
    $form['case_insensitive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Case Insensitive'),
      '#default_value' => $this->getFormStateValue(
        'case_insensitive', $form_state
      )
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $search = $this->formatReplacementToArray(
      $form_state->getValue('search')
    );

    $replace = $this->formatReplacementToArray(
      $form_state->getValue('replace')
    );

    $elements = NestedArray::getValue(
      $form_state->getCompleteForm(), $form['#parents']
    );

    if (count($search) !== count($replace)) {
      $form_state->setError(
        $elements['search'],
        $this->t('The search terms need to contain the same amount as replace.')
      );
      $form_state->setError(
        $elements['replace'],
        $this->t('The replace terms need to contain the same amount as search.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach (['search', 'replace'] as $key) {
      $this->configuration[$key] = $this->formatReplacementToArray(
        $form_state->getValue($key)
      );
    }
  }

  /**
   * Format replacement values.
   *
   * @param $value
   *   The string value.
   *
   * @return array
   *   An array of replacement values.
   */
  protected function formatReplacementToArray($value) {
    $replacements = array_map('trim', explode("\r\n", $value));

    foreach ($replacements as &$replacement) {
      if (strpos($replacement, 'CHR:') !== FALSE) {
        $code = substr($replacement, 4);
        if (!is_numeric($code)) {
          continue;
        }
        $replacement = chr($code);
      }
    }

    return $replacements;
  }
}
