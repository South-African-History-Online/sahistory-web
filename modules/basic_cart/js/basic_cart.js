/**
 * @file
 * Contains js for the accordion example.
 */
/*(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.basic_cart = {
    attach: function (context, settings) {

    }
  };
})(jQuery, Drupal, drupalSettings); */

(function ($, Drupal, drupalSettings) {
  $(function () {
  $(".addtocart-quantity-wrapper-container").each(function() {
                var this_id = $(this).attr('id');
                id_split = this_id.split("_");
                var dynamic_id = "quantitydynamictext_"+id_split[1];
var quantity_label = drupalSettings.basic_cart.label_value ? drupalSettings.basic_cart.label_value : 'Quantity';

                var dynamic_input = '<label for="edit-quantity" class="js-form-required form-required '+ drupalSettings.basic_cart.label_class
 +'">'+quantity_label+'</label> <input type="text" value="1" class="quantity_dynamic_text form-text required '+drupalSettings.basic_cart.textfield_class
+'" id="'+dynamic_id+'">';
                $(this).html(dynamic_input);
           });

      $(document).on('click',".basic_cart-get-quantity",function(e) {
        e.preventDefault();   e.stopPropagation();
        var this_ids = $(this).attr('id');
        id_splited = this_ids.split("_");
        var quantity = $('#quantitydynamictext_'+id_splited[1]).val();
        var basic_cart_throbber = '<div id="basic-cart-ajax-progress-throbber_'+id_splited[1]+'" class="basic_cart-ajax-progress-throbber ajax-progress ajax-progress-throbber"><div class="basic_cart-throbber throbber">&nbsp;</div></div>';
         $('#forquantitydynamictext_'+id_splited[1]).after(basic_cart_throbber);
         if($(this).hasClass('use-basic_cart-ajax')) {
         $.ajax({url: this.href+quantity, success: function(result) {
              $(".basic_cart-grid").each(function(){
                $(this).html(result.block);
              });
              $("#"+result.id).hide();
              $("#"+result.id).html(result.text);
              $(".basic_cart-circles").each(function () {
                $(this).html(result.count);
              });
              $("#"+result.id).fadeIn('slow').delay(1000).hide(2000);
              $('#basic-cart-ajax-progress-throbber_'+id_splited[1]).remove();
          },
          error: function (xhr, ajaxOptions, thrownError) {
            $('#basic-cart-ajax-progress-throbber_'+id_splited[1]).remove();
            if(xhr.status==403) {
            $('#ajax-addtocart-message-'+id_splited[1]).html('<p class="messages messages--error">You are not authorized to add</p>').show();
            }else{
            $('#ajax-addtocart-message-'+id_splited[1]).html('<p class="messages messages--error">Contact site administrator</p>').show();
            }

          }
        });
        }else {
          window.location.href = this.href+quantity;
        }
      });
  })
})(jQuery, Drupal, drupalSettings);


