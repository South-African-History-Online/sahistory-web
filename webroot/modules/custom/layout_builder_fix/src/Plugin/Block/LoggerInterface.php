<?php

namespace Drupal\layout_builder\Plugin\Block;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Interface LoggerInterface.
 *
 * This interface extends the PSR LoggerInterface to fix a type error in the
 * InlineBlock class after updating to Drupal 11.
 */
interface LoggerInterface extends PsrLoggerInterface {
}