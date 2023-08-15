<?php

namespace Drupal\uc_ups;

/**
 * Utility routines for UPS Shipping.
 */
class UPSUtilities {

  /**
   * Convenience function to get UPS codes for their services.
   */
  public static function services() {
    return array(
      // Domestic services.
      '03' => t('UPS Ground'),
      '01' => t('UPS Next Day Air'),
      '13' => t('UPS Next Day Air Saver'),
      '14' => t('UPS Next Day Early A.M.'),
      '02' => t('UPS 2nd Day Air'),
      '59' => t('UPS 2nd Day Air A.M.'),
      '12' => t('UPS 3 Day Select'),
      // International services.
      '11' => t('UPS Standard'),
      '07' => t('UPS Worldwide Express'),
      '08' => t('UPS Worldwide Expedited'),
      '54' => t('UPS Worldwide Express Plus'),
      '65' => t('UPS Worldwide Saver'),
      // Poland to Poland shipments only.
      //'82' => t('UPS Today Standard'),
      //'83' => t('UPS Today Dedicated Courrier'),
      //'84' => t('UPS Today Intercity'),
      //'85' => t('UPS Today Express'),
      //'86' => t('UPS Today Express Saver'),
    );
  }

  /**
   * Convenience function to get UPS codes for their package types.
   */
  public static function packageTypes() {
    return array(
      // Customer Supplied Page is first so it will be the default.
      '02' => t('Customer Supplied Package'),
      '01' => t('UPS Letter'),
      '03' => t('Tube'),
      '04' => t('PAK'),
      '21' => t('UPS Express Box'),
      '24' => t('UPS 25KG Box'),
      '25' => t('UPS 10KG Box'),
      '30' => t('Pallet'),
      '2a' => t('Small Express Box'),
      '2b' => t('Medium Express Box'),
      '2c' => t('Large Express Box'),
    );
  }

}
