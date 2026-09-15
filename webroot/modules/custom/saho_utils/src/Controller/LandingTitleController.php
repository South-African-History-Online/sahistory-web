<?php

declare(strict_types=1);

namespace Drupal\saho_utils\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\saho_utils\LandingTypes;

/**
 * Route title for the /index/{type} landing.
 */
final class LandingTitleController {

  use StringTranslationTrait;

  /**
   * Returns the landing label for the type argument.
   *
   * @param string $arg_0
   *   The node type machine name from the route (views names the argument
   *   parameter arg_0).
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The landing title.
   */
  public function title(string $arg_0 = '') {
    $label = LandingTypes::label($arg_0);
    return $label !== NULL ? $this->t($label) : $this->t('Browse the archive');
  }

}
