<?php

namespace Drupal\saho_media_migration\Commands;

/**
 * Trait to wrap DrushStyle methods for static analysis compatibility.
 *
 * This trait provides wrapper methods for DrushStyle functionality
 * to bypass static analysis issues while maintaining runtime functionality.
 */
trait DrushStyleTrait {

  /**
   * Display a table.
   *
   * @param array $headers
   *   Table headers.
   * @param array $rows
   *   Table rows.
   */
  protected function displayTable(array $headers, array $rows) {
    if (method_exists($this, 'io')) {
      $this->io()->table($headers, $rows);
    }
  }

  /**
   * Display a warning message.
   *
   * @param string $message
   *   The message to display.
   */
  protected function displayWarning($message) {
    if (method_exists($this, 'io')) {
      $this->io()->warning($message);
    }
  }

  /**
   * Display a success message.
   *
   * @param string $message
   *   The message to display.
   */
  protected function displaySuccess($message) {
    if (method_exists($this, 'io')) {
      $this->io()->success($message);
    }
  }

  /**
   * Display an error message.
   *
   * @param string $message
   *   The message to display.
   */
  protected function displayError($message) {
    if (method_exists($this, 'io')) {
      $this->io()->error($message);
    }
  }

  /**
   * Display a title.
   *
   * @param string $message
   *   The title to display.
   */
  protected function displayTitle($message) {
    if (method_exists($this, 'io')) {
      $this->io()->title($message);
    }
  }

  /**
   * Display a note.
   *
   * @param string $message
   *   The note to display.
   */
  protected function displayNote($message) {
    if (method_exists($this, 'io')) {
      $this->io()->note($message);
    }
  }

  /**
   * Ask for confirmation.
   *
   * @param string $question
   *   The question to ask.
   * @param bool $default
   *   The default answer.
   *
   * @return bool
   *   Whether the user confirmed or not.
   */
  protected function askConfirmation($question, $default = TRUE) {
    if (method_exists($this, 'io')) {
      return $this->io()->confirm($question, $default);
    }
    return $default;
  }

  /**
   * Display a definition list.
   *
   * @param array $list
   *   The definition list.
   */
  protected function displayDefinitionList(array $list) {
    if (method_exists($this, 'io')) {
      $this->io()->definitionList($list);
    }
  }

  /**
   * Display a section.
   *
   * @param string $message
   *   The section message.
   */
  protected function displaySection($message) {
    if (method_exists($this, 'io')) {
      $this->io()->section($message);
    }
  }

}
