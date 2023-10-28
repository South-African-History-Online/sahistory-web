<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for filtering based on a list of words/phrases.
 *
 * @Tamper(
 *   id = "keyword_filter",
 *   label = @Translation("Keyword filter"),
 *   description = @Translation("Filter based on a list of words/phrases."),
 *   category = "Text",
 *   handle_multiples = TRUE
 * )
 */
class KeywordFilter extends TamperBase {

  /**
   * A list of words/phrases appearing in the text. Enter one value per line.
   */
  const WORDS = 'words';

  /**
   * If checked, then "book" will match "book" but not "bookcase"..
   */
  const WORD_BOUNDARIES = 'word_boundaries';

  /**
   * A list of words/phrases appearing in the text. Enter one value per line.
   */
  const EXACT = 'exact';

  /**
   * If checked -> "book" === "book". Override the "Respect word boundaries".
   */
  const CASE_SENSITIVE = 'case_sensitive';

  /**
   * If checked, then "book" will match "book" but not "Book" or "BOOK".
   */
  const INVERT = 'invert';

  /**
   * Index for the word list configuration option.
   *
   * The word list option holds the calculated value of the word list after all
   * settings are applied.
   */
  const WORD_LIST = 'word_list';

  /**
   * Flags whether or not we'll be using regex to match.
   *
   * This value is calculated by other options.
   */
  const REGEX = 'regex';

  /**
   * Holds which string position function will be used.
   *
   * This value is calculated by other options.
   */
  const FUNCTION = 'function';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::WORDS] = '';
    $config[self::WORD_BOUNDARIES] = FALSE;
    $config[self::EXACT] = FALSE;
    $config[self::CASE_SENSITIVE] = FALSE;
    $config[self::INVERT] = FALSE;
    $config[self::WORD_LIST] = [];
    $config[self::REGEX] = FALSE;
    $config[self::FUNCTION] = 'matchRegex';

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::WORDS] = [
      '#type' => 'textarea',
      '#title' => $this->t('Words or phrases to filter on'),
      '#default_value' => $this->getSetting(self::WORDS),
      '#description' => $this->t('A list of words/phrases that need to appear in the text. Enter one value per line.'),
    ];

    $form[self::WORD_BOUNDARIES] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Respect word boundaries'),
      '#default_value' => $this->getSetting(self::WORD_BOUNDARIES),
      '#description' => $this->t('If checked, then "book" will match "book" but not "bookcase".'),
    ];

    $form[self::EXACT] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exact'),
      '#default_value' => $this->getSetting(self::EXACT),
      '#description' => $this->t('If checked, then "book" will only match "book". This will override the "Respect word boundaries" setting above.'),
    ];

    $form[self::CASE_SENSITIVE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Case sensitive'),
      '#default_value' => $this->getSetting(self::CASE_SENSITIVE),
      '#description' => $this->t('If checked, then "book" will match "book" but not "Book" or "BOOK".'),
    ];

    $form[self::INVERT] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Invert filter'),
      '#default_value' => $this->getSetting(self::INVERT),
      '#description' => $this->t('Inverting the filter will remove items with the specified text.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::WORDS => $form_state->getValue(self::WORDS),
      self::WORD_BOUNDARIES => $form_state->getValue(self::WORD_BOUNDARIES),
      self::EXACT => $form_state->getValue(self::EXACT),
      self::CASE_SENSITIVE => $form_state->getValue(self::CASE_SENSITIVE),
      self::INVERT => $form_state->getValue(self::INVERT),
      self::WORD_LIST => $form_state->getValue(self::WORD_LIST),
      self::REGEX => $form_state->getValue(self::REGEX),
      self::FUNCTION => $form_state->getValue(self::FUNCTION),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $is_multibyte = (Unicode::getStatus() == Unicode::STATUS_MULTIBYTE) ? TRUE : FALSE;

    $word_list = str_replace("\r", '', $form_state->getValue(self::WORDS));
    $word_list = explode("\n", $word_list);
    $word_list = array_map('trim', $word_list);
    $setting_regex = FALSE;
    $setting_function = 'matchRegex';

    $exact = $form_state->getValue(self::EXACT);
    $word_boundaries = $form_state->getValue(self::WORD_BOUNDARIES);
    $case_sensitive = $form_state->getValue(self::CASE_SENSITIVE);
    if (!empty($exact) || $word_boundaries) {
      foreach ($word_list as &$word) {
        if (!empty($exact)) {
          $word = '/^' . preg_quote($word, '/') . '$/u';
        }
        elseif ($word_boundaries) {
          // Word boundaries can only match a word with letters at the end.
          if (!preg_match('/^\w(.*\w)?$/u', $word)) {
            $form_state->setErrorByName(self::WORDS, $this->t('Search text must begin and end with a letter, number, or underscore to use the %option option.', ['%option' => t('Respect word boundaries')]));
          }
          $word = '/\b' . preg_quote($word, '/') . '\b/u';
        }
        if (!$case_sensitive) {
          $word .= 'i';
        }
      }
      $setting_regex = TRUE;
    }
    elseif (!$word_boundaries && $case_sensitive) {
      $setting_function = $is_multibyte ? 'mb_strpos' : 'strpos';
    }
    elseif (!$word_boundaries && !$case_sensitive) {
      $setting_function = $is_multibyte ? 'mb_stripos' : 'stripos';
    }

    $form_state->setValue(self::WORD_LIST, $word_list);
    $form_state->setValue(self::REGEX, $setting_regex);
    $form_state->setValue(self::FUNCTION, $setting_function);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    $match_func = $this->getSetting(self::FUNCTION);

    $match = FALSE;
    $word_list = $this->getSetting(self::WORD_LIST);

    if (is_array($data)) {
      foreach ($data as $value) {
        if ($this->match($match_func, $value, $word_list)) {
          $match = TRUE;
          break;
        }
      }
      reset($data);
    }
    else {
      $match = $this->match($match_func, $data, $word_list);
    }

    if (!$match && empty($this->getSetting(self::INVERT))) {
      return '';
    }

    if ($match && !empty($this->getSetting(self::INVERT))) {
      return '';
    }

    return $data;
  }

  /**
   * Determines whether we get a keyword filter match.
   */
  protected function match($match_func, $field, array $word_list) {
    foreach ($word_list as $word) {
      if ($match_func == "matchRegex") {
        if ($this->$match_func($field, $word) !== FALSE) {
          return TRUE;
        }
      }
      elseif ($match_func($field, $word) !== FALSE) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Determines whether we get a keyword filter match using regex.
   */
  protected function matchRegex($field, $word) {
    return preg_match($word, $field) > 0;
  }

}
