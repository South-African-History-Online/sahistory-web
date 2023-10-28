<?php

namespace Drupal\uc_cart_links\Tests;

use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\filter\Entity\FilterFormat;
use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the cart links functionality.
 *
 * @group Ubercart
 */
class CartLinksTest extends UbercartTestBase {

  public static $modules = array('uc_cart_links', 'uc_attribute', 'help', 'block');
  public static $adminPermissions = array('administer cart links', 'view cart links report', 'access administration pages');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set front page so we have someplace to redirect to for invalid Cart Links.
    \Drupal::configFactory()->getEditable('system.site')->set('page.front', '/node')->save();

    // Need page_title_block because we test page titles
    $this->drupalPlaceBlock('page_title_block');

    // System help block is needed to see output from hook_help().
    $this->drupalPlaceBlock('help_block', array('region' => 'help'));

    // Testing profile doesn't include a 'page' content type.
    // We will need this to create pages with links on them.
    $this->drupalCreateContentType(
      array(
        'type' => 'page',
        'name' => 'Basic page'
      )
    );

    // Create Full HTML text format, needed because we want links
    // to appear on pages.
    $full_html_format = FilterFormat::create(array(
      'format' => 'full_html',
      'name' => 'Full HTML',
    ));
    $full_html_format->save();
  }

  /**
   * Tests access to admin settings page and tests default values.
   */
  public function testCartLinksUISettingsPage() {
    // Access settings page by anonymous user
    $this->drupalGet('admin/store/config/cart-links');
    $this->assertResponse(403);
    $this->assertText(t('Access denied'));
    $this->assertText(t('You are not authorized to access this page.'));

    // Access settings page by privileged user
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/config/cart-links');
    $this->assertResponse(200);
    $this->assertText(
      t('View the help page to learn how to create Cart Links.'),
      'Settings page found.'
    );
    $this->assertFieldByName(
      'uc_cart_links_add_show',
      0,
      'Display Cart Links product action messages is off.'
    );
    $this->assertFieldByName(
      'uc_cart_links_track',
      1,
      'Track clicks is on.'
    );
    $this->assertFieldByName(
      'uc_cart_links_empty',
      1,
      'Allow Cart Links to empty carts is on.'
    );
    $this->assertFieldByName(
      'uc_cart_links_messages',
      '',
      'Cart Links messages  is empty.'
    );
    $this->assertFieldByName(
      'uc_cart_links_restrictions',
      '',
      'Cart Links restrictions is empty.'
    );

    // Test presence of and contents of Help page
    $this->clickLink(t('View the help page'));
    $this->assertText(
      'http://www.example.com/cart/add/&lt;cart_link_content&gt;',
      'Help text found.'
    );
  }

  /**
   * Tests Cart Links on a page under a variety of conditions.
   */
  public function testCartLinksBasicFunctionality() {
    // Create product
    $products[] = $this->createCartLinksProduct(FALSE);

    // Create a product class
    $products[] = $this->createCartLinksProduct(FALSE);  // later ...

    // Create some valid Cart Links for these products
    $link_array = $this->createValidCartLinks($products);
    $cart_links = $link_array['links'];
    $link_data  = $link_array['data'];

    // Need to test incorrect links as well:
    //   links which add invalid attributes
    //   links which omit required attributes

    // Create a page containing these links
    $page = $this->createCartLinksPage($cart_links);

    //
    // Test clicking on links
    //

    foreach ($cart_links as $key => $test_link) {
      $this->drupalGet('node/' . $page->id());
      // Look for link on page
      $this->assertLink(
        t('Cart Link #@link', ['@link' => $key]),
        0,
        SafeMarkup::format('Cart Link #@link found on page.', ['@link' => $key])
      );
      $this->assertLinkByHref(
        t('@link', ['@link' => $test_link]),
        0,
        SafeMarkup::format('Cart Link @link found on page.', ['@link' => $test_link])
      );

      // Click on link
      $this->clickLink(t('Cart Link #@link', ['@link' => $key]));
      // Check for notice that item was added (this notice is set ON
      // by default, see admin/store/config/cart)
      $this->assertText(
        t('@title added to your shopping cart.', ['@title' => $link_data[$key]['title']]),
        SafeMarkup::format('Product @title added to cart.', ['@title' => $link_data[$key]['title']])
      );

      // Check contents of cart
      $this->drupalGet('cart');
      $this->assertText(
        $link_data[$key]['title'],
        'Product title correct in cart.'
      );
      $this->assertFieldByName(
        'items[0][qty]',
        $link_data[$key]['qty'],
        'Product quantity correct in cart.'
      );

      // Check for correct attribute name(s) in cart
      foreach ($link_data[$key]['attributes'] as $label => $attribute) {
        $this->assertText(
          $label . ':',
          SafeMarkup::format('Attribute @label correct in cart.', ['@label' => $label])
        );
        foreach ($attribute as $option) {
          // Check for correct option name(s) in cart
          $this->assertText(
            $option,
            SafeMarkup::format('Option @name correct in cart.', ['@name' => $option])
          );
        }
      }

      // Use the same link, but this time append an '_s' to turn
      // off message display for this product
      $this->drupalGet($test_link . '_s');
      // Default add-to-cart message is different when adding a duplicate item
      $this->assertNoText(
        t('Your item(s) have been updated.'),
        'Default add-to-cart message suppressed.'
      );

      // Empty cart (press remove button)
      $this->drupalPostForm('cart', array(), t('Remove'));
      $this->assertText(t('There are no products in your shopping cart.'));
    }
  }

  /**
   * Tests Cart Links product action messages.
   */
  public function testCartLinksProductActionMessage() {
    // Create product
    $products[] = $this->createCartLinksProduct(FALSE);

    // Create a product class
    $products[] = $this->createCartLinksProduct(FALSE);  // later ...

    // Create some valid Cart Links for these products
    $link_array = $this->createValidCartLinks($products);
    $cart_links = $link_array['links'];
    $link_data  = $link_array['data'];

    // Create a page containing these links
    $page = $this->createCartLinksPage($cart_links);

    $this->drupalLogin($this->adminUser);

    //
    // Test product action message display
    //

    // Turn on display of product action message
    $this->setCartLinksUIProductActionMessage(TRUE);
    // Go to page with Cart Links
    $this->drupalGet('node/' . $page->id());
    // Pick one of the links at random
    $test_link = array_rand($cart_links);
    $this->clickLink(t('Cart Link #@link', ['@link' => $test_link]));
    $this->assertText(
      t('Cart Link product action: @link', ['@link' => substr($cart_links[$test_link], 10)]),
      'Cart Link product action message found.'
    );

    // Empty cart (press remove button)
    $this->drupalPostForm('cart', array(), t('Remove'));
    $this->assertText(t('There are no products in your shopping cart.'));

    // Turn off display of product action message
    $this->setCartLinksUIProductActionMessage(FALSE);
    // Go to page with Cart Links
    $this->drupalGet('node/' . $page->id());
    // Pick one of the links at random
    $test_link = array_rand($cart_links);
    $this->clickLink(t('Cart Link #@link', ['@link' => $test_link]));
    $this->assertNoText(
      t('Cart Link product action: @link', ['@link' => substr($cart_links[$test_link], 10)]),
      'Cart Link product action message not present.'
    );

    $this->drupalLogout();
  }

  /**
   * Tests Cart Links cart empty action.
   */
  public function testCartLinksAllowEmptying() {
    // Create product
    $products[] = $this->createCartLinksProduct(FALSE);

    // Create a product class
    $products[] = $this->createCartLinksProduct(FALSE);  // later ...

    // Create some valid Cart Links for these products
    $link_array = $this->createValidCartLinks($products);
    $cart_links = $link_array['links'];
    $link_data  = $link_array['data'];

    // Create a page containing these links
    $page = $this->createCartLinksPage($cart_links);

    $this->drupalLogin($this->adminUser);

    //
    // Test empty cart action
    //

    // Allow links to empty cart
    $this->setCartLinksUIAllowEmptying(TRUE);
    // Go to page with Cart Links
    $this->drupalGet('node/' . $page->id());
    // Pick one of the links at random and add it to the cart
    $test_link_0 = array_rand($cart_links);
    $this->clickLink(t('Cart Link #@link', ['@link' => $test_link_0]));

    // Pick another link at random and prepend an 'e-' so it will empty cart
    $in_cart = $cart_links[$test_link_0];
    // (Don't want to use the same link.)
    unset($cart_links[$test_link_0]);
    $test_link = array_rand($cart_links);
    $this->drupalGet(str_replace('add/p', 'add/e-p', $cart_links[$test_link]));
    $this->assertText(
      t('The current contents of your shopping cart will be lost. Are you sure you want to continue?'),
      'Empty cart confirmation page found.'
    );
    // Allow
    $this->drupalPostForm(NULL, array(), t('Confirm'));

    // Verify the cart doesn't have the first item and does have the second item
    $this->drupalGet('cart');
    $this->assertText(
      $link_data[$test_link]['title'],
      'Product title correct in cart.'
    );
    $this->assertNoText(
      $link_data[$test_link_0]['title'],
      'Cart was emptied by Cart Link.'
    );

    // Still have something ($test_link) in the cart

    // Forbid links to empty cart
    $this->setCartLinksUIAllowEmptying(FALSE);
    // Re-use $test_link_0 and prepend an 'e-' so it will (try to) empty cart
    $this->drupalGet(str_replace('add/p', 'add/e-p', $in_cart));
    // Verify the cart has both items - cart wasn't emptied
    $this->drupalGet('cart');
    $this->assertText(
      $link_data[$test_link_0]['title'],
      'Cart was not emptied by Cart Link.'
    );
    $this->assertText(
      $link_data[$test_link]['title'],
      'Cart was not emptied by Cart Link.'
    );

    $this->drupalLogout();
  }

  /**
   * Tests Cart Links restrictions.
   */
  public function testCartLinksRestrictions() {
    // Create product
    $products[] = $this->createCartLinksProduct(FALSE);

    // Create a product class
    $products[] = $this->createCartLinksProduct(FALSE);  // later ...

    // Create some valid Cart Links for these products
    $link_array = $this->createValidCartLinks($products);
    $cart_links = $link_array['links'];
    $link_data  = $link_array['data'];

    // Create a page containing these links
    $page = $this->createCartLinksPage($cart_links);

    $this->drupalLogin($this->adminUser);

    //
    // Test Cart Links restrictions
    //

    // Go to page with Cart Links
    $this->drupalGet('node/' . $page->id());
    // Pick one of the links at random and restrict it
    $test_link_0 = array_rand($cart_links);
    // Only this link is allowed - strip '/cart/add/' from beginning
    $this->setCartLinksUIRestrictions(substr($cart_links[$test_link_0], 10));

    // Attempt to click link - should pass
    $this->drupalGet('node/' . $page->id());
    $this->clickLink(t('Cart Link #@link', ['@link' => $test_link_0]));

    // Check for notice that item was added (this notice is set ON
    // by default, see admin/store/config/cart)
    $this->assertText(
      t('@title added to your shopping cart.', ['@title' => $link_data[$test_link_0]['title']]),
      SafeMarkup::format('Product @title added to cart.', ['@title' => $link_data[$test_link_0]['title']])
    );

    // Pick another link at random, as long as it is different from first
    $in_cart = $cart_links[$test_link_0];
    unset($cart_links[$test_link_0]);
    $test_link = array_rand($cart_links);

    // Attempt to click it
    // It should fail and redirect to the home page (default)
    $this->drupalGet('node/' . $page->id());
    $this->clickLink(t('Cart Link #@link', ['@link' => $test_link]));
    $this->assertText(
      t('Welcome to Drupal')
    );
    $this->assertText(
      t('No front page content has been created yet.'),
      'Redirected to front page for link not in restrictions.'
    );

    // Now create a special redirect page for bad links
    $redirect_page = $this->drupalCreateNode(
      array(
        'body' => array(
          0 => array('value' => 'ERROR: Invalid Cart Link!')
        ),
        'promote' => 0,
      )
    );

    // Set redirect link
    $this->setCartLinksUIRedirect('node/' . $redirect_page->id());

    // Attempt to click same restricted link as above.
    // It should fail again but this time redirect to $redirect_page.
    $this->drupalGet('node/' . $page->id());
    $this->clickLink(t('Cart Link #@link', ['@link' => $test_link]));
    $this->assertText(
      t('ERROR: Invalid Cart Link!'),
      'Redirected to error page for link not in restrictions.'
    );

    // Remove restrictions, try to add again - it should pass
    $this->setCartLinksUIRestrictions('');
    $this->drupalGet('node/' . $page->id());
    $this->clickLink(t('Cart Link #@link', ['@link' => $test_link]));
    $this->assertText(
      t('@title added to your shopping cart.', ['@title' => $link_data[$test_link]['title']]),
      SafeMarkup::format('Product @title added to cart.', ['@title' => $link_data[$test_link]['title']])
    );

    $this->drupalLogout();
  }

  /**
   * Tests Cart Links custom messages.
   */
  public function testCartLinksMessages() {

    // Create product
    $products[] = $this->createCartLinksProduct(FALSE);

    // Create a product class
    $products[] = $this->createCartLinksProduct(FALSE);  // later ...

    // Create some valid Cart Links for these products
    $link_array = $this->createValidCartLinks($products);
    $cart_links = $link_array['links'];
    $link_data  = $link_array['data'];

    // Create a page containing these links
    $page = $this->createCartLinksPage($cart_links);

    // Need to be admin to define messages
    $this->drupalLogin($this->adminUser);

    // Define some messages
    $messages = array();
    for ($i = 0; $i < 15; $i++) {
      $key = mt_rand(1, 999);
      $messages[$key] = $key . '|' . $this->randomMachineName(32);
    }
    $this->setCartLinksUIMessages($messages);

    //
    // Test message display
    //

    // Go to page with Cart Links
    $this->drupalGet('node/' . $page->id());

    // Pick one link at random and append an '-m<#>' to display a message
    $test_link = array_rand($cart_links);
    $message_key  = array_rand($messages);
    $message_text = explode('|', $messages[$message_key]);
    $this->drupalGet($cart_links[$test_link] . '-m' . $message_key);
    $this->assertText(
      t('@message', ['@message' => $message_text[1]]),
      SafeMarkup::format('Message @key displayed.', ['@key' => $message_key])
    );

    // Empty cart (press remove button)
    $this->drupalPostForm('cart', array(), t('Remove'));
    $this->assertText(t('There are no products in your shopping cart.'));

    $this->drupalLogout();
  }

  /**
   * Tests Cart Links tracking.
   */
  public function testCartLinksTracking() {

    // Create product
    $products[] = $this->createCartLinksProduct(FALSE);

    // Create a product class
    $products[] = $this->createCartLinksProduct(FALSE);  // later ...

    // Create some valid Cart Links for these products
    $link_array = $this->createValidCartLinks($products);
    $cart_links = $link_array['links'];
    $link_data  = $link_array['data'];

    // Create a page containing these links
    $page = $this->createCartLinksPage($cart_links);

    $this->drupalLogin($this->adminUser);

    //
    // Test Cart Links tracking
    //

    // Go to page with Cart Links
    $this->drupalGet('node/' . $page->id());

    // Create three tracking IDs
    $tracking = array();
    for ($i = 0; $i < 3; $i++) {
      $tracking[$this->randomMachineName(16)] = 0;
    }

    // Click a number of links to create some statistics
    for ($i = 0; $i < 50; $i++) {
      // Pick one link at random and append an '-i<tracking ID>'
      $test_link = array_rand($cart_links);

      // Assign one of the tracking IDs
      $tracking_id = array_rand($tracking);
      $this->drupalGet($cart_links[$test_link] . '-i' . $tracking_id);
      // Keep a record of how many links were assigned this key
      $tracking[$tracking_id] += 1;
    }
    // Sort by # of clicks, as that is how Views displays them by default.
    arsort($tracking, SORT_NUMERIC);

    // Check report to see these clicks have been recorded correctly
    $this->drupalGet('admin/store/reports/cart-links');
    $total = 0;
    foreach ($tracking as $id => $clicks) {
      $total += $clicks;
//    $result = $this->xpath('//tbody/tr/td[contains(concat(" ", @class, " "), " views-field-cart-link-id ")]');
//    $result = $this->xpath('//tbody/tr/td[contains(concat(" ", @class, " "), " views-field-clicks ")]');
      $this->assertTextPattern(
        '/\s+' . preg_quote($id, '/') . '\s+' . preg_quote($clicks, '/') . '\s+/',
        SafeMarkup::format('Tracking ID @id received @clicks clicks.', ['@id' => $id, '@clicks' => $clicks])
      );
    }
    $this->assertEqual($total, 50, 'Fifty clicks recorded.');

    $this->drupalLogout();
  }

  /****************************************************************************
   * Utility Functions                                                        *
   ****************************************************************************/

  /**
   * Sets checkbox to display Cart Links product action messages.
   *
   * Must be logged in with 'administer cart links' permission.
   *
   * @param bool $state
   *   TRUE to display product action messages, FALSE to not display.
   *   Defaults to FALSE.
   */
  protected function setCartLinksUIProductActionMessage($state = FALSE) {
    $this->drupalPostForm(
      'admin/store/config/cart-links',
      array('uc_cart_links_add_show' => $state),
      t('Save configuration')
    );
    $this->assertFieldByName(
      'uc_cart_links_add_show',
      $state,
      SafeMarkup::format('Display Cart Links product action messages is @state.', ['@state' => $state ? 'TRUE' : 'FALSE'])
    );
  }

  /**
   * Sets checkbox to track Cart Links clicks.
   *
   * Must be logged in with 'administer cart links' permission.
   *
   * @param bool $state
   *   TRUE to display product action messages, FALSE to not display.
   *   Defaults to TRUE.
   */
  protected function setCartLinksUITrackClicks($state = TRUE) {
    $this->drupalPostForm(
      'admin/store/config/cart-links',
      array('uc_cart_links_track' => 0),
      t('Save configuration')
    );
    $this->assertFieldByName(
      'uc_cart_links_track',
      $state ? 1 : 0,
      SafeMarkup::format('Track clicks is @state.', ['@state' => $state ? 'TRUE' : 'FALSE'])
    );
  }

  /**
   * Sets checkbox to allow Cart Links to empty cart.
   *
   * Must be logged in with 'administer cart links' permission.
   *
   * @param bool $state
   *   TRUE to display product action messages, FALSE to not display.
   *   Defaults to TRUE.
   */
  protected function setCartLinksUIAllowEmptying($state = TRUE) {
    $this->drupalPostForm(
      'admin/store/config/cart-links',
      array('uc_cart_links_empty' => $state),
      t('Save configuration')
    );
    $this->assertFieldByName(
      'uc_cart_links_empty',
      $state,
      SafeMarkup::format('Allow Cart Links to empty carts is @state.', ['@state' => $state ? 'TRUE' : 'FALSE'])
    );
  }

  /**
   * Sets messages that can be referenced by a link.
   *
   * Must be logged in with 'administer cart links' permission.
   *
   * @param string $messages
   *   String containing user input from a textarea, one message per line.
   *   Messages have numeric key and text value, separated by '|'.
   */
  protected function setCartLinksUIMessages($messages = '') {
    $message_string = implode("\n", $messages);
    $this->drupalPostForm(
      'admin/store/config/cart-links',
      array('uc_cart_links_messages' => $message_string),
      t('Save configuration')
    );
    $this->assertFieldByName(
      'uc_cart_links_messages',
      $message_string,
      SafeMarkup::format('Cart Links messages contains "@messages".', ['@messages' => $message_string])
    );
  }

  /**
   * Sets allowed Cart Links.
   *
   * Must be logged in with 'administer cart links' permission.
   *
   * @param string $restrictions
   *   String containing user input from a textarea, one restriction per line.
   *   Restrictions are valid Cart Links - i.e. relative URLs.
   */
  protected function setCartLinksUIRestrictions($restrictions = '') {
    $this->drupalPostForm(
      'admin/store/config/cart-links',
      array('uc_cart_links_restrictions' => $restrictions),
      t('Save configuration')
    );
    $this->assertFieldByName(
      'uc_cart_links_restrictions',
      $restrictions,
      SafeMarkup::format('Cart Links restrictions contains "@restrictions".', ['@restrictions' => $restrictions])
    );
  }

  /**
   * Sets redirect destination page for invalid Cart Links.
   *
   * Must be logged in with 'administer cart links' permission.
   *
   * @param string $url
   *   Relative URL of the destination page for the redirect.  Omit leading '/'.
   */
  protected function setCartLinksUIRedirect($url = '') {
    $this->drupalPostForm(
      'admin/store/config/cart-links',
      array('uc_cart_links_invalid_page' => $url),
      t('Save configuration')
    );
    $this->assertFieldByName(
      'uc_cart_links_invalid_page',
      $url,
      SafeMarkup::format('Cart Links invalid page URL contains ":url".', [':url' => $url])
    );
  }

  /**
   * Create a page with Cart Links in the body.
   *
   * @param array $links
   *   Array of Cart Links to appear on page.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node entity.
   */
  protected function createCartLinksPage($links = array()) {
    $item_list = array(
      '#theme' => 'links',
      '#links' => array(),
    );
    if (!empty($links)) {
      $i = 0;
      foreach ($links as $link) {
        $item_list['#links'][] = array(
          'title' => t('Cart Link #@num', ['@num' => $i++]),
          'url' => Url::fromUri('base:' . $link),
        );
      }
    }

    $page = array(
      'type' => 'page', // This is default anyway ...
      'body' => array(
        0 => array(
          'value' => !empty($links) ? \Drupal::service('renderer')->renderPlain($item_list) : $this->randomMachineName(128),
          'format' => 'full_html',
        )
      ),
      'promote' => 0,
    );

    return $this->drupalCreateNode($page);
  }

  /**
   * Creates a product with all attribute types and options.
   *
   * @param bool $product_class
   *   Defaults to FALSE to create a normal product, set to TRUE to
   *   create a product class instead.
   */
  protected function createCartLinksProduct($product_class = FALSE) {

    // Create a product
    if ($product_class) {
      $product = $this->createProductClass(array('promote' => 0));
    }
    else {
      $product = $this->createProduct(array('promote' => 0));
    }

    // Create some attributes
    for ($i = 0; $i < 5; $i++) {
      $attribute = $this->createAttribute();
      $attributes[$attribute->aid] = $attribute;
    }

    // Add some options, organizing them by aid and oid.
    $attribute_aids = array_keys($attributes);

    $all_options = array();
    foreach ($attribute_aids as $aid) {
      for ($i = 0; $i < 3; $i++) {
        $option = $this->createAttributeOption(array('aid' => $aid));
        $all_options[$option->aid][$option->oid] = $option;
      }
    }

   // array('required' => TRUE)

    // Get the options.
    $attribute = uc_attribute_load($attribute->aid);

    // Load every attribute we got.
    $attributes_with_options = uc_attribute_load_multiple();

    // Pick 5 keys to check at random.
    $aids = array_rand($attributes, 3);

    // Load the attributes back.
    $loaded_attributes = uc_attribute_load_multiple($aids);

      // TODO: add attributes of all 4 types
      // TODO: create both required and not required attributes

    // Add the selected attributes to the product.
    foreach ($loaded_attributes as $loaded_attribute) {
      uc_attribute_subject_save($loaded_attribute, 'product', $product->id(), TRUE);
    }

    return $product;
  }


  /**
   * Creates Cart Links pointing to the given product(s).
   *
   * Links containing many combinations of attributes and options wil be
   * returned. Return value is an associative array containing two keys:
   *   -links: An array of the actual links we're building.
   *   -data: An array of metadata about the Cart Links so we won't have to try
   *   to re-construct this information by parsing the link at a later time.
   *
   * The 'links' and 'data' sub-arrays are both indexed by the keys used in
   * the $products array that is passed in as an argument, so these keys may
   * be used to lookup the link and metadata for a specific product.
   *
   * @param array $products
   *   An array of products.
   *
   * @return array
   *   Array containing Cart Links and link metadata.
   */
  protected function createValidCartLinks($products = array()) {
    foreach ($products as $key => $product) {
      $nid   = $product->id();
      $title = $product->label();
      $qty   = mt_rand(1, 19);
      // $link_data will hold meta information about the Cart Links
      // so we won't have to try to re-construct this information by
      // parsing the link at a later time.
      $link_data[$key] = array(
        'nid'   => $nid,
        'title' => $title,
        'qty'   => $qty,
        'attributes' => array(),
      );

      // $cart_links will hold the actual links we're building.
      // $cart_links and $link_data share the same keys.
      $cart_links[$key] = '/cart/add/p' . $nid . '_q' . $qty;

      // Loop over attributes, append all attribute/option combos to links
      $attributes = uc_product_get_attributes($nid);
      foreach ($attributes as $attribute) {
        // If this is textfield, radio, or select option, then
        // only 1 option allowed.  If checkbox, multiple are allowed.
        switch ($attribute->display) {
          case 0:  // textfield
            $value = $this->randomMachineName(12);  // Textfield
            $link_data[$key]['attributes'][$attribute->label][] = $value;
            $cart_links[$key] .= '_a' . $attribute->aid . 'o' . $value;
            break;
          case 1:  // select
          case 2:  // radios
            $option = $attribute->options[array_rand($attribute->options)];
            $link_data[$key]['attributes'][$attribute->label][] = $option->name;
            $cart_links[$key] .= '_a' . $attribute->aid . 'o' . $option->oid;
            break;
          case 3:  // checkboxes
            foreach ($attribute->options as $option) {
              $link_data[$key]['attributes'][$attribute->label][] = $option->name;
              $cart_links[$key] .= '_a' . $attribute->aid . 'o' . $option->oid;
            }
            break;
        }
      }
    }

    return array('links' => $cart_links, 'data' => $link_data);
  }

}
