<?php

namespace Drupal\ludwig;

/**
 * An interface for PackageDownloader.
 */
interface PackageDownloaderInterface {

  /**
   * Downloads and places packages into their modules.
   *
   * @param array $package
   *   The package.
   *
   * @throws \Exception
   * @throws \Drupal\Core\FileTransfer\FileTransferException
   */
  public function download(array $package);

}
