<?php

namespace Drupal\Tests\ludwig\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the Downloader works.
 *
 * @group ludwig
 */
class LudwigIntegrationTest extends KernelTestBase implements ServiceModifierInterface {

  /**
   * The array of dependencies.
   *
   * @var array
   */
  public static $modules = ['system', 'ludwig', 'ludwig_test'];

  /**
   * Tests that packages are downloaded, added to namespaces, classes available.
   */
  public function testIntegration() {
    /** @var \Drupal\ludwig\PackageManagerInterface $package_manager */
    $package_manager = $this->container->get('ludwig.package_manager');
    /** @var \Drupal\ludwig\PackageDownloader $package_downloader */
    $package_downloader = $this->container->get('ludwig.package_downloader');

    $packages = $package_manager->getPackages();
    foreach ($packages as $package) {
      $package_downloader->download($package);
    }

    $this->container->get('kernel')->rebuildContainer();
    $packages = $package_manager->getPackages();

    $namespaces = $this->container->getParameter('container.namespaces');
    foreach ($packages as $package) {
      $this->assertTrue(isset($namespaces[$package['namespace']]), 'Could not find namespace ' . $package['namespace']);
    }

    $this->assertTrue(class_exists('CommerceGuys\Intl\Calculator'));
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->removeDefinition('test.http_client.middleware');
  }

}
