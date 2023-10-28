<?php

namespace Drupal\glazed_builder;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a glazed builder profile entity type.
 */
interface GlazedBuilderProfileInterface extends ConfigEntityInterface {

  /**
   * Loads the first profile available for specified roles.
   *
   * @return self
   */
  public static function loadByRoles(array $roles);

}
