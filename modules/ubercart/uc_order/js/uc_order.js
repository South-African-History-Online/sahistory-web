/**
 * @file
 * Handles asynchronous requests for order editing forms.
 */

(function ($, Drupal, drupalSettings, window) {

  'use strict';

  var customer_select = '';

  /**
   * Adds double click behavior to the order and customer admin tables.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ucOrderClick = {
    attach: function (context, settings) {
      $('.view-uc-orders tbody tr, .view-uc-customers tbody tr', context).dblclick(function () {
        window.location = $(this).find('.views-field-order-id a').attr('href');
      });
    }
  };

  /**
   * Adds the submit behavior to the order form.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ucOrderSubmit = {
    attach: function (context, settings) {
      $('#uc-order-edit-form:not(.ucOrderSubmit-processed)', context).addClass('ucOrderSubmit-processed').submit(function () {
        $('#products-selector').empty().removeClass();
        $('#delivery-address-select').empty().removeClass();
        $('#billing-address-select').empty().removeClass();
        $('#customer-select').empty().removeClass();
      });
    }
  };

  /**
   * Copies the shipping data on the order edit screen to the corresponding
   * billing fields if they exist.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ucOrderCopyShippingToBilling = {
    attach: function () {
      $('#copy-shipping-to-billing').click(function () {
        if ($('#edit-delivery-zone').html() !== $('#edit-billing-zone').html()) {
          $('#edit-billing-zone').empty().append($('#edit-delivery-zone').children().clone());
        }

        $('#uc-order-edit-form input, select, textarea').each(function () {
          if (this.id.substring(0, 13) === 'edit-delivery') {
            $('#edit-billing' + this.id.substring(13)).val($(this).val());
          }
        });
      });
    }
  };

  /**
   * Copies the billing data on the order edit screen to the corresponding
   * shipping fields if they exist.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ucOrderCopyBillingToShipping = {
    attach: function () {
      $('#copy-billing-to-shipping').click(function () {
        if ($('#edit-billing-zone').html() !== $('#edit-delivery-zone').html()) {
          $('#edit-delivery-zone').empty().append($('#edit-billing-zone').children().clone());
        }

        $('#uc-order-edit-form input, select, textarea').each(function () {
          if (this.id.substring(0, 12) === 'edit-billing') {
            $('#edit-delivery' + this.id.substring(12)).val($(this).val());
          }
        });
      });
    }
  };

  /**
   * Closes the address book div.
   *
   * @todo: Replace with core Ajax.
   */
  function close_address_select(div) {
    $(div).empty().removeClass('address-select-box');
    return false;
  }

  /**
   * Loads the address book div on the order edit screen.
   *
   * @todo: Replace with core Ajax.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ucOrderLoadAddressSelect = {
    attach: function (context, settings) {
      $('.load-address-select').click(function (context, setttings) {

        // If it's already open, close it.
        // if ($('#edit-back').val() !== null) {
        //   return close_address_select(drupalSettings.paneId);
        // }

        var options = {
          uid: drupalSettings.uid,
          type: drupalSettings.addressType,
          func: "Drupal.behaviors.ucOrderLoadAddressSelect.apply_address('" + drupalSettings.addressType + "', this.value);"
        };

        $.post(drupalSettings.ucURL.adminOrders + '/address_book', options,
          function (contents) {
            $(drupalSettings.paneId).empty().addClass('address-select-box').append(contents);
          }
        );
      });
    },

    /**
     * Applies the selected address to the appropriate fields in the order edit form.
     */
    apply_address: function (type, address_str) {
   //   if ($('#edit-delivery-zone').html() !== $('#edit-billing-zone').html()) {
   //     $('#edit-billing-zone').empty().append($('#edit-delivery-zone').children().clone());
   //   }

    //  $('#uc-order-edit-form input, select, textarea').each(function () {
    //    if (this.id.substring(0, 13) === 'edit-delivery') {
    //      $('#edit-billing' + this.id.substring(13)).val($(this).val());
    //    }
    //  });

      (1, eval)('var address = ' + address_str + ';');
      $('#edit-' + type + '-first-name').val(address['first_name']);
      $('#edit-' + type + '-last-name').val(address['last_name']);
      $('#edit-' + type + '-phone').val(address['phone']);
      $('#edit-' + type + '-company').val(address['company']);
      $('#edit-' + type + '-street1').val(address['street1']);
      $('#edit-' + type + '-street2').val(address['street2']);
      $('#edit-' + type + '-city').val(address['city']);
      $('#edit-' + type + '-postal-code').val(address['postal_code']);

      if ($('#edit-' + type + '-country').val() !== address['country']) {
        $('#edit-' + type + '-country').val(address['country']);
      }

      $('#edit-' + type + '-zone').val(address['zone']);
    }
  };

  /**
   * Hides the customer selection form.
   */
  function close_customer_select() {
    $('#customer-select').empty().removeClass('customer-select-box');
    customer_select = '';
    return false;
  }

  /**
   * Loads the customer select div on the order edit screen.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ucCloseCustomerSelect = {
    attach: function (context, settings) {
      $('#close-customer-select').click(function () {
        return close_customer_select();
      });
    }
  };

  /**
   * Loads the customer select div on the order edit screen.
   *
   * @todo: Replace with core Ajax.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ucOrderLoadCustomerSearch = {
    attach: function (context, settings) {
      $('#load-customer-search').click(function () {
        // If it's already open, close it.
        if (customer_select === 'search' && $('#customer-select #edit-back').val() == null) {
          return close_customer_select();
        }

        // Else fetch it and insert it into the DOM.
        $.post(drupalSettings.ucURL.adminOrders + '/customer', {},
          function (contents) {
            $('#customer-select').empty().addClass('customer-select-box').append(contents);
            $('#customer-select #edit-first-name').val($('#edit-billing-first-name').val());
            $('#customer-select #edit-last-name').val($('#edit-billing-last-name').val());
            customer_select = 'search';
          }
        );

        return false;
      });
    }
  };

  /**
   * Displays the results of the customer search.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ucOrderLoadCustomerSearchResults = {
    attach: function () {
      $('#load-customer-search-results').click(function () {
        $.post(drupalSettings.ucURL.adminOrders + '/customer/search',
          {
            first_name: $('#customer-select #edit-first-name').val(),
            last_name: $('#customer-select #edit-last-name').val(),
            email: $('#customer-select #edit-email').val()
          },
          function (contents) {
            $('#customer-select').empty().append(contents);
          }
        );

        return false;
      });
    }
  };

  /**
   * Displays the new customer form.
   *
   * @todo: Replace with core Ajax.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ucOrderLoadNewCustomerForm = {
    attach: function (context, settings) {
      $('#load-new-customer-form').click(function () {
        // If it's already open, close it.
        if (customer_select === 'new') {
          return close_customer_select();
        }

        // Else fetch it and insert it into the DOM.
        $.post(drupalSettings.ucURL.adminOrders + '/customer/new', {},
          function (contents) {
            $('#customer-select').empty().addClass('customer-select-box').append(contents);
            customer_select = 'new';
          }
        );
        return false;
      });
    }
  };

  /**
   * Handles submit button on new customer form.
   * Validates the customer's email address.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ucCheckNewCustomerAddress = {
    attach: function (context) {
      $('#check-new-customer-address').click(function () {
        var options = {
          check: true,
          email: $('#customer-select #edit-email').val(),
          sendmail: $('#customer-select #edit-sendmail').prop('checked')
        };
        $.post(drupalSettings.ucURL.adminOrders + '/customer/new', options,
          function (contents) {
            $('#customer-select').empty().append(contents);
          }
        );
        return false;
      });
    }
  };

  /**
   * Sets customer values from search selection.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ucSelectCustomerSearch = {
    attach: function (context) {
      $('#select-customer-search').click(function () {
        var data = $('#edit-cust-select').val();
        var i = data.indexOf(':');
        drupalSettings.userId = data.substr(0, i);
        drupalSettings.userEmail = data.substr(i + 1);
        return Drupal.behaviors.ucSelectExistingCustomer.search();
      });
    }
  };

  /**
   * Loads existing customer as new order's customer.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.ucSelectExistingCustomer = {
    attach: function (context) {
      $('#select-existing-customer').click(function () {
        return Drupal.behaviors.ucSelectExistingCustomer.search();
      });
    },

    search: function () {
      $('input[name=uid], #edit-uid-text').val(drupalSettings.userId);
      $('input[name=primary_email], #edit-primary-email-text').val(drupalSettings.userEmail);
      try {
        $('#edit-submit-changes').click();
      }
      catch (err) {
      }
      return close_customer_select();
    }
  };

}(jQuery, Drupal, drupalSettings, window));
