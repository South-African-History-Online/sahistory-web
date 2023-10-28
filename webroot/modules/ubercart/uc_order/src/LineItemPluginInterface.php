<?php

namespace Drupal\uc_order;

/**
 * Defines an interface for line item plugins.
 *
 * A line item is a representation of charges, fees, and totals for an order.
 * Default line items include the subtotal and total line items, the tax line
 * item, and the shipping line item. There is also a generic line item that
 * store admins can use to add extra fees and discounts to manually created
 * orders. Module developers will use this plugin to define new types of line
 * items for their stores. An example use would be for a module that allows
 * customers to use coupons and wants to represent an entered coupon as a line
 * item.
 */
interface LineItemPluginInterface {
}
