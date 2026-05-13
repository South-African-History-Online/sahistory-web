<?php

namespace Drupal\saho_publications\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Site\Settings;

/**
 * Front-page SAHO Press / Publications discovery block.
 *
 * Phase A: surfaces a curated set of featured publications from the shop with
 * covers + metadata. Links out to shop product pages for purchase. The
 * featured set is configurable via settings.php so the homepage can be
 * curated without a code change:
 *
 * @code
 * $settings['saho_publications_featured'] = [
 *   ['title' => 'Title', 'author' => '...', 'year' => '2023', 'price' => 'R250', 'url' => '...', 'cover' => '...'],
 *   // ...
 * ];
 * @endcode
 *
 * Phase B (planned follow-up): fetch live via shop JSON:API with cron-warmed
 * cache, dropping the Settings array.
 *
 * @Block(
 *   id = "saho_publications_block",
 *   admin_label = @Translation("SAHO Publications (front-page)"),
 *   category = @Translation("SAHO"),
 * )
 */
class PublicationsBlock extends BlockBase {

  /**
   * Default curated set used when no Settings override is present.
   *
   * Six titles, spread across decades, all with cover art already on the
   * shop. Cover URLs are absolute so the block works on any environment
   * (local DDEV main-site can read shop.sahistory.org.za assets directly).
   */
  protected function getDefaultFeatured(): array {
    $shop = 'https://shop.sahistory.org.za';
    return [
      [
        'title' => 'The True Confessions of an Unrehabilitated Terrorist',
        'author' => 'Stephen Johannes Marais',
        'year' => '2018',
        'price' => 'R200',
        'url' => $shop . '/product/true-confessions-unrehabilitated-terrorist',
        'cover' => $shop . '/sites/shop.sahistory.org.za/files/styles/publication_cover/public/2025-11/TRUECONFESSIONSTERRORISTMARAIS.webp',
      ],
      [
        'title' => 'Kora',
        'subtitle' => 'A Lost Khoisan Language of the early Cape',
        'author' => 'Menán du Plessis',
        'year' => '2019',
        'price' => 'R250',
        'url' => $shop . '/product/kora',
        'cover' => $shop . '/sites/shop.sahistory.org.za/files/styles/publication_cover/public/2025-11/du_plessis_kora_cover_front_shop.jpg',
      ],
      [
        'title' => 'My Life',
        'author' => 'Stephanie Kemp',
        'year' => '2017',
        'price' => 'R200',
        'url' => $shop . '/product/my-life',
        'cover' => $shop . '/sites/shop.sahistory.org.za/files/styles/publication_cover/public/product-covers/cover_my_life_by_stephanie_kemp.jpg',
      ],
      [
        'title' => 'Collected Poems',
        'author' => 'Mafika Gwala',
        'year' => '2016',
        'price' => 'R200',
        'url' => $shop . '/product/9',
        'cover' => $shop . '/sites/shop.sahistory.org.za/files/styles/publication_cover/public/product-covers/bookcover_mafika_gwala_collected_poems.jpg',
      ],
      [
        'title' => 'Cape Flats Details',
        'subtitle' => "Life and Popular Art in Cape Town's Townships",
        'author' => 'Chris Ledochowski',
        'year' => '2003',
        'price' => 'R500',
        'url' => $shop . '/product/8',
        'cover' => NULL,
      ],
      [
        'title' => 'Seedtimes',
        'author' => 'Omar Badsha',
        'year' => '2017',
        'price' => 'R500',
        'url' => $shop . '/product/25',
        'cover' => NULL,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $items = Settings::get('saho_publications_featured', NULL);
    if (!is_array($items) || !$items) {
      $items = $this->getDefaultFeatured();
    }

    return [
      '#theme' => 'saho_publications_block',
      '#eyebrow' => Settings::get('saho_publications_eyebrow', 'SAHO Press'),
      '#title' => Settings::get('saho_publications_title', 'Publications'),
      '#subtitle' => Settings::get('saho_publications_subtitle', 'Books, essays, and primary sources curated by South African History Online.'),
      '#items' => $items,
      '#shop_url' => Settings::get('saho_publications_shop_url', 'https://shop.sahistory.org.za/publications'),
      '#all_label' => Settings::get('saho_publications_all_label', 'View all publications'),
      '#attached' => [
        'library' => ['saho_publications/block'],
      ],
      '#cache' => [
        'max-age' => 3600,
      ],
    ];
  }

}
