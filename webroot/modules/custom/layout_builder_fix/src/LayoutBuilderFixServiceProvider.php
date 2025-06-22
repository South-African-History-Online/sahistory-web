<?php

namespace Drupal\layout_builder_fix;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

/**
 * Service provider for the layout_builder_fix module.
 *
 * Ensures that this module is loaded before the layout_builder module
 * so that our LoggerInterface is available when the InlineBlock class is loaded.
 */
class LayoutBuilderFixServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Ensure this module is loaded before layout_builder.
    $modules = $container->getParameter('container.modules');
    if (isset($modules['layout_builder']) && isset($modules['layout_builder_fix'])) {
      // Set a higher weight for layout_builder to ensure it loads after our module.
      $modules['layout_builder']['weight'] = isset($modules['layout_builder_fix']['weight'])
        ? $modules['layout_builder_fix']['weight'] + 1
        : 1;
      $container->setParameter('container.modules', $modules);
    }
  }

}