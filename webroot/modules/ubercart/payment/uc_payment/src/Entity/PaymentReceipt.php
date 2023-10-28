<?php

namespace Drupal\uc_payment\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\uc_payment\PaymentReceiptInterface;

/**
 * Defines the payment receipt entity class.
 *
 * @ContentEntityType(
 *   id = "uc_payment_receipt",
 *   label = @Translation("Payment receipt"),
 *   label_singular = @Translation("payment receipt"),
 *   label_plural = @Translation("payment receipts"),
 *   label_count = @PluralTranslation(
 *     singular = "@count payment receipt",
 *     plural = "@count payment receipts",
 *   ),
 *   module = "uc_payment",
 *   base_table = "uc_payment_receipts",
 *   entity_keys = {
 *     "id" = "receipt_id",
 *   }
 * )
 */
class PaymentReceipt extends ContentEntityBase implements PaymentReceiptInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['receipt_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Payment receipt ID'))
      ->setDescription(t('The payment receipt ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);
    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order ID'))
      ->setDescription(t('The order ID.'))
      ->setSetting('target_type', 'uc_order');
    $fields['method'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payment method'))
      ->setDescription('The payment method used.')
      ->setSetting('target_type', 'uc_payment_method');
    $fields['amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Amount'))
      ->setDescription('The payment amount in the store default currency.')
      ->setSetting('default_value', 0.0)
      ->setSetting('precision', 16)
      ->setSetting('scale', 5);
    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency'))
      ->setDescription(t('The ISO currency code for the payment.'))
      ->setPropertyConstraints('value', array('Length' => array('max' => 3)))
      ->setSetting('default_value', '')
      ->setSetting('max_length', 3);
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription('The user that collected the payment.')
      ->setSetting('target_type', 'user')
      ->setSetting('default_value', 0);
    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription('A serialized array of extra data.');
    $fields['comment'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Comment'))
      ->setDescription('A comment made on the payment.')
      ->setSetting('default_value', '');
    $fields['received'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Received'))
      ->setDescription(t('The time that the payment was received.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->get('order_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderId() {
    return $this->get('order_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getMethod() {
    return $this->get('method')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getMethodId() {
    return $this->get('method')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    return $this->get('amount')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getComment() {
    return $this->get('comment')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getReceived() {
    return $this->get('received')->value;
  }

}
