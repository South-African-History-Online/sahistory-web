<?php

namespace Drupal\saho\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Drupal\image\Entity\ImageStyle;

/**
 * Custom Twig extension for SAHO theme.
 */
class SahoTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('saho_get_responsive_image', [$this, 'getResponsiveImage'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Generate responsive image markup with multiple sizes and proper dimensions.
   *
   * @param \Drupal\file\Entity\File|null $file_entity
   *   The file entity to render.
   * @param string $alt
   *   The alt text for the image.
   * @param array $styles
   *   Array of image style machine names to use for srcset.
   * @param string $sizes
   *   The sizes attribute value.
   * @param string $loading
   *   Loading strategy: 'lazy' or 'eager'.
   * @param string $class
   *   CSS class(es) to add to the image.
   * @param bool $get_dimensions
   *   Whether to fetch and include width/height attributes.
   *
   * @return array
   *   Render array with responsive image markup.
   */
  public function getResponsiveImage($file_entity, $alt = '', array $styles = ['saho_large', 'saho_medium'], $sizes = '100vw', $loading = 'lazy', $class = '', $get_dimensions = TRUE) {
    if (!$file_entity) {
      return ['#markup' => ''];
    }

    $uri = $file_entity->getFileUri();
    $srcset = [];

    // Build srcset from image styles
    foreach ($styles as $style_name) {
      $style = ImageStyle::load($style_name);
      if ($style) {
        $url = $style->buildUrl($uri);

        // Get width from style configuration
        $config = $style->getEffects()->getConfiguration();
        $width = 0;

        foreach ($config as $effect) {
          if (isset($effect['data']['width'])) {
            $width = $effect['data']['width'];
            break;
          }
        }

        if ($width) {
          $srcset[] = "$url {$width}w";
        }
      }
    }

    // Fallback to first style if available
    $fallback_style = ImageStyle::load($styles[0]);
    $fallback_url = $fallback_style ? $fallback_style->buildUrl($uri) : file_create_url($uri);

    // Get original dimensions for proper aspect ratio
    $width = NULL;
    $height = NULL;

    if ($get_dimensions) {
      $image = \Drupal::service('image.factory')->get($uri);
      if ($image->isValid()) {
        $original_width = $image->getWidth();
        $original_height = $image->getHeight();

        // Calculate aspect ratio and set dimensions based on first style
        if ($fallback_style && $original_width && $original_height) {
          $style_config = $fallback_style->getEffects()->getConfiguration();
          $target_width = NULL;
          $target_height = NULL;

          foreach ($style_config as $effect) {
            if (isset($effect['data']['width'])) {
              $target_width = $effect['data']['width'];
            }
            if (isset($effect['data']['height'])) {
              $target_height = $effect['data']['height'];
            }
          }

          if ($target_width) {
            $width = $target_width;
            // Calculate proportional height
            $height = $target_height ?? round(($original_height / $original_width) * $target_width);
          }
          else {
            $width = $original_width;
            $height = $original_height;
          }
        }
        else {
          $width = $original_width;
          $height = $original_height;
        }
      }
    }

    // Build attributes array
    $attributes = [
      'src' => $fallback_url,
      'alt' => $alt,
      'class' => $class,
      'loading' => $loading,
      'decoding' => 'async',
    ];

    if (!empty($srcset)) {
      $attributes['srcset'] = implode(', ', $srcset);
      $attributes['sizes'] = $sizes;
    }

    if ($width && $height) {
      $attributes['width'] = $width;
      $attributes['height'] = $height;
    }

    // Build attribute string
    $attr_string = '';
    foreach ($attributes as $key => $value) {
      $attr_string .= ' ' . $key . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
    }

    $markup = '<img' . $attr_string . '>';

    return ['#markup' => \Drupal\Core\Render\Markup::create($markup)];
  }

}
