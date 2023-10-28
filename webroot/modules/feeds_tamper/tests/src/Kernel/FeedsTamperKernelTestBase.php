<?php

namespace Drupal\Tests\feeds_tamper\Kernel;

use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * Provides a base class for Feeds Tamper kernel tests.
 */
abstract class FeedsTamperKernelTestBase extends FeedsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'feeds',
    'feeds_tamper',
    'tamper',
  ];

}
