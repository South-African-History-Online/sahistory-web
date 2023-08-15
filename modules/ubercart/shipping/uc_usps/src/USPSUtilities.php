<?php

namespace Drupal\uc_usps;

/**
 * Utility routines for USPS Shipping.
 */
class USPSUtilities {

  /**
   * Maps envelope shipment services to their IDs.
   */
  public static function envelopeServices() {
    return array(
      'zero' => t('U.S.P.S. First-Class Mail Postcard'),
      'zeroFlat' => t('U.S.P.S. First-Class Mail Letter'),
      12 => t('U.S.P.S. First-Class Postcard Stamped'),
      1 => t('U.S.P.S. Priority Mail'),
      16 => t('U.S.P.S. Priority Mail Flat-Rate Envelope'),
      3 => t('U.S.P.S. Express Mail'),
      13 => t('U.S.P.S. Express Mail Flat-Rate Envelope'),
      23 => t('U.S.P.S. Express Mail Sunday/Holiday Guarantee'),
      25 => t('U.S.P.S. Express Mail Flat-Rate Envelope Sunday/Holiday Guarantee'),
    );
  }

  /**
   * Maps parcel shipment services to their IDs.
   */
  public static function services() {
    return array(
      'zeroFlat' => t('U.S.P.S. First-Class Mail Letter'),
      'zeroParcel' => t('U.S.P.S. First-Class Mail Parcel'),
      1 => t('U.S.P.S. Priority Mail'),
      28 => t('U.S.P.S. Priority Mail Small Flat-Rate Box'),
      17 => t('U.S.P.S. Priority Mail Regular/Medium Flat-Rate Box'),
      22 => t('U.S.P.S. Priority Mail Large Flat-Rate Box'),
      3 => t('U.S.P.S. Express Mail'),
      23 => t('U.S.P.S. Express Mail Sunday/Holiday Guarantee'),
      4 => t('U.S.P.S. Parcel Post'),
      5 => t('U.S.P.S. Bound Printed Matter'),
      6 => t('U.S.P.S. Media Mail'),
      7 => t('U.S.P.S. Library'),
    );
  }

  /**
   * Maps international envelope services to their IDs.
   */
  public static function internationalEnvelopeServices() {
    return array(
      13 => t('First Class Mail International Letter'),
      14 => t('First Class Mail International Large Envelope'),
      2 => t('Priority Mail International'),
      8 => t('Priority Mail International Flat Rate Envelope'),
      4 => t('Global Express Guaranteed'),
      12 => t('GXG Envelopes'),
      1 => t('Express Mail International (EMS)'),
      10 => t('Express Mail International (EMS) Flat Rate Envelope'),
    );
  }

  /**
   * Maps international parcel services to their IDs.
   */
  public static function internationalServices() {
    return array(
      15 => t('First Class Mail International Package'),
      2 => t('Priority Mail International'),
      16 => t('Priority Mail International Small Flat-Rate Box'),
      9 => t('Priority Mail International Regular/Medium Flat-Rate Box'),
      11 => t('Priority Mail International Large Flat-Rate Box'),
      4 => t('Global Express Guaranteed'),
      6 => t('Global Express Guaranteed Non-Document Rectangular'),
      7 => t('Global Express Guaranteed Non-Document Non-Rectangular'),
      1 => t('Express Mail International (EMS)'),
    );
  }

  /**
   * Convenience function for select form elements.
   */
  public static function packageTypes() {
    return array(
      'VARIABLE' => t('Variable'),
      'FLAT RATE ENVELOPE' => t('Flat rate envelope'),
      'PADDED FLAT RATE ENVELOPE' => t('Padded flat rate envelope'),
      'LEGAL FLAT RATE ENVELOPE' => t('Legal flat rate envelope'),
      'SMALL FLAT RATE ENVELOPE' => t('Small flat rate envelope'),
      'WINDOW FLAT RATE ENVELOPE' => t('Window flat rate envelope'),
      'GIFT CARD FLAT RATE BOX' => t('Gift card flat rate box'),
      'FLAT RATE BOX' => t('Flat rate box'),
      'SM FLAT RATE BOX' => t('Small flat rate box'),
      'MD FLAT RATE BOX' => t('Medium flat rate box'),
      'LG FLAT RATE BOX' => t('Large flat rate box'),
      'REGIONALRATEBOXA' => t('Regional rate box A'),
      'REGIONALRATEBOXB' => t('Regional rate box B'),
      'RECTANGULAR' => t('Rectangular'),
      'NONRECTANGULAR' => t('Non-rectangular'),
    );
  }
}
