<?php

namespace Drupal\saho\TwigExtension;

use Drupal\image\Entity\ImageStyle;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for image style URLs.
 */
class ImageStyleExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('image_style_url', [$this, 'imageStyleUrl']),
    ];
  }

  /**
   * Get image style URL for a file URI.
   *
   * @param string $uri
   *   The file URI (e.g., 'public://bio_pics/image.jpg').
   * @param string $style_name
   *   The image style machine name (e.g., 'large', 'thumbnail').
   *
   * @return string
   *   The image style URL or original file URL if style doesn't exist.
   */
  public function imageStyleUrl($uri, $style_name) {
    if (empty($uri) || empty($style_name)) {
      return $uri;
    }

    $style = ImageStyle::load($style_name);
    if ($style) {
      return $style->buildUrl($uri);
    }

    // Fallback to file_create_url if style doesn't exist.
    return \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'saho.image_style_extension';
  }

}
