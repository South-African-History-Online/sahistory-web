<?php

namespace Drupal\uc_cart_links\Controller;

use Drupal\Core\Url;

/**
 * Provides instructions on how to create Cart Links.
 *
 * @return array
 *   Form API array with help text.
 */
class CartLinksHelp {

  /**
   * Explains Cart Links syntax for help purposes.
   *
   * @return array
   *   A render array for the Help page.
   */
  public static function creationHelp() {
    $build = array(
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    );
    $build['introduction'] = array(
      '#prefix' => '<p>',
      '#markup' => t("Cart Links allow you to craft links that add products to customer shopping carts and redirect customers to any page on the site. A store owner might use a Cart Link as a 'Buy it now' link in an e-mail, in a blog post, or on any page, either on or off site. These links may be identified with a unique ID, and clicks on these links may be reported to the administrator in order to track the effectiveness of each unique ID. You may track affiliate sales, see basic reports, and make sure malicious users don't create unapproved links."),
      '#suffix' => '</p>',
    );
    $build['uses'] = array(
      '#prefix' => t('The following actions may be configured to occur when a link is clicked:'),
      '#theme' => 'item_list',
      '#items' => array(
        t("Add any quantity of any number of products to the customer's cart, with specific attributes and options for each added product, if applicable."),
        t('Display a custom message to the user.'),
        t('Track the click for display on a store report.'),
        t("Empty the customer's shopping cart."),
        t('Redirect to any page on the site.'),
      ),
    );

    $build['suggestions'] = array(
      '#prefix' => '<p>',
      '#markup' => t('A Cart Link URL looks like:<blockquote><code>/cart/add/<em>&lt;cart_link_content&gt;</em></code></blockquote>where <code><em>&lt;cart_link_content&gt;</em></code> consists of one or more actions separated by a dash. Absolute URLs may also be used, e.g.:<blockquote><code>http://www.example.com/cart/add/<em>&lt;cart_link_content&gt;</em></code></blockquote>'),
      '#suffix' => '</p>',
    );

//  t('Specify the redirection by adding ?destination=url where url is the page to go to.'),

    $header = array(t('Action'), t('Description'), t('Argument'));
    $rows = array(
      array('p', t('Adds a product to the cart.'), t('A product node number, followed by optional arguments described in the table below.')),
      array('i', t('Sets the ID of the cart link.'), t('An alphanumeric string (32 characters max) to identify the link.')),
      array('m', t('Displays a message to the customer when the link is clicked.'), t('A <a href=":url">numeric message ID</a> to identify which message to display.', [':url' => Url::fromRoute('uc_cart_links.settings')->toString()])),
      array('e', t('Empties the cart. If used, this should be the first action.'), t('None.')),
    );

    $build['commands'] = array(
      '#prefix' => t('Allowed actions are:'),
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    $build['required'] = array(
      '#prefix' => '<p>',
      '#markup' => t('The only required part of the <code><em>&lt;cart_link_content&gt;</em></code> is the "p" action, which must be immediately followed by a product node number.  For example, to add product node 23 to a cart, use the following:<blockquote><code>/cart/add/p23</code></blockquote>To use this on your site, simply create an HTML anchor tag referencing your Cart Link URL:<blockquote><code>&lt;a href="http://www.example.com/cart/add/p23"&gt;Link text.&lt;/a&gt;</code></blockquote>'),
      '#suffix' => '</p>',
    );

    $header = array(t('Argument'), t('Description'), t('Values'));
    $rows = array(
      array('q', t('Specifies quantity of this product to add.'), t('A positive integer.')),
      array('a<aid>o<oid>', t('Specifies attribute/option for this product.'), t('aid is the integer attribute ID. oid is the integer option ID for radio, checkbox, and select options, or a url-escaped text string for textfield options.')),
      array('s', t('Silent.  Suppresses add-to-cart message for this product.
      (The add-to-cart message may be enabled on the <a href=":url">cart settings page</a>).', [':url' => Url::fromRoute('uc_cart.cart_settings')->toString()]), t('None.')),
    );
    $build['args'] = array(
      '#prefix' => t('Optional arguments for "p" allow you to control the quantity, set product attributes and options, and suppress the default product action message normally shown when a product is added to a cart. These optional arguments are appended to the "p" action and separated with an underscore.  Allowed arguments for "p" are:'),
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    $build['quantity'] = array(
      '#prefix' => '<p>',
      '#markup' => t('For example, you may set the product quantity by appending the "q" argument to the "p" action. To add 5 items of product 23 you would use the link:<blockquote><code>/cart/add/p23_q5</code></blockquote>'),
      '#suffix' => '</p>',
    );

    $build['optional'] = array(
      '#prefix' => '<p>',
      '#markup' => t('Product attributes and options may be set with the <code>a&lt;aid&gt;o&lt;oid&gt;</code> argument.  For example, if product 23 has an attribute named "Size" with attribute ID = 12, and if there are three options defined for this attribute ("Small", "Medium", and "Large", with option IDs 4, 5, and 6 respectively), then to add a "Medium" to the cart you would use the link:<blockquote><code>/cart/add/p23_a12o5</code></blockquote>To add two products, one "Medium" and one "Small", you would use two actions:<blockquote><code>/cart/add/p23_a12o5-p23_a12o4</code></blockquote>Or, to just add two "Medium" products:<blockquote><code>/cart/add/p23_q2_a12o5</code></blockquote>'),
      '#suffix' => '</p>',
    );

    $build['messages']['stop'] = array(
      '#prefix' => '<p>',
      '#markup' => t('To stop the default "xxx was added to your shopping cart" message for a product, use the argument "_s". For example: <blockquote><code>/cart/add/p23_s</code></blockquote> "_s" is an argument to the "p" action, and suppresses the message for this product only.  Other products added by other actions in the Cart Link will still show the message.  e.g. <blockquote><code>/cart/add/p23_s-p15</code></blockquote> will show a message for product 15 but not for product 23.'),
      '#suffix' => '</p>',
    );
    $build['messages']['custom'] = array(
      '#prefix' => '<p>',
      '#markup' => t('To insert your own message, first define your message in the Cart Links messages panel on the Cart Links settings page, by entering for example "99|My message text". Then use the action "-m99" (a dash, not an underscore) to add the message. For example: <blockquote><code>/cart/add/p23-m99</code></blockquote>'),
      '#suffix' => '</p>',
    );
    $build['messages']['note'] = array(
      '#prefix' => '<p>',
      '#markup' => t('Note that just specifying "-m99" will display both your message 99 and the default message, unless you have turned off the default message with "_s".'),
      '#suffix' => '</p>',
    );
    $build['messages']['additional'] = array(
      '#prefix' => '<p>',
      '#markup' => t('For additional messages, add additional actions, e.g. "-m99-m1337".'),
      '#suffix' => '</p>',
    );

    $build['example'] = array(
      '#prefix' => '<p>',
      '#markup' => t('A Cart Link that uses all of the available actions and arguments might look something like this:<blockquote><code>/cart/add/e-p23_q5_a12o5_a19o9_a1oA%20Text%20String_s-ispecialoffer-m77?destination=cart/checkout</code></blockquote>Note that the "e", "p", "i", and "m" actions are separated by dashes, while the optional arguments within the "p" action are separated by underscores. This example will first empty the shopping cart, then add 5 items of product 23 to the cart, track clicks with the ID "specialoffer", display a custom message with the ID "77", then redirect the user to the checkout page. In this case product 23 has three attributes which are set (aid = 12, 19, and 1), one of which is a textfield attribute (aid = 1).'),
      '#suffix' => '</p>',
    );

    $build['help'] = array(
      '#prefix' => '<p>',
      '#markup' => t('<a href=":url">Visit the settings page</a> to set preferences, define messages, and restrict links that may be used.', [':url' => Url::fromRoute('uc_cart_links.settings')->toString()]),
      '#suffix' => '</p>',
    );

    return $build;
  }
}
