<?php

namespace Drupal\uc_country;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Substitutes the uc_country manager service for the core country_manager.
 */
class UcCountryServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('country_manager');

    // Overrides country_manager class to add additional functionality.
    $definition->setClass('Drupal\uc_country\CountryManager');
    // Inject the entity_type.manager service, which is not available in
    // the core country_manager.
    $definition->addArgument(new Reference('entity_type.manager'));

  }
}
