<?php

namespace Drupal\uc_attribute\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the product attribute API.
 *
 * @group Ubercart
 */
class AttributeTest extends UbercartTestBase {

  public static $modules = array('uc_attribute');
  public static $adminPermissions = array('administer attributes', 'administer product attributes', 'administer product options');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need page_title_block because we test page titles
    $this->drupalPlaceBlock('page_title_block');

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the basic attribute API.
   */
  public function testAttributeAPI() {

    // Create an attribute.
    $attribute = $this->createAttribute();

    // Test retrieval.
    $loaded_attribute = uc_attribute_load($attribute->aid);

    // Check the attribute integrity.
    foreach ($this->attributeFieldsToTest() as $field) {
      if ($loaded_attribute->$field != $attribute->$field) {
        $this->fail('Attribute integrity check failed.');
        break;
      }
    }

    // Add a product.
    $product = $this->createProduct();

    // Attach the attribute to a product.
    uc_attribute_subject_save($attribute, 'product', $product->id());

    // Confirm the database is correct.
    $this->assertEqual($attribute->aid, db_query('SELECT aid FROM {uc_product_attributes} WHERE nid = :nid', [':nid' => $product->id()])->fetchField(), 'Attribute was attached to a product properly.');
    $this->assertTrue(uc_attribute_subject_exists($attribute->aid, 'product', $product->id()));

    // Test retrieval.
    $loaded_attribute = uc_attribute_load($attribute->aid, $product->id(), 'product');

    // Check the attribute integrity.
    foreach ($this->attributeFieldsToTest('product') as $field) {
      if ($loaded_attribute->$field != $attribute->$field) {
        $this->fail('Attribute integrity check failed.');
        break;
      }
    }

    // Delete it.
    uc_attribute_subject_delete($attribute->aid, 'product', $product->id());

    // Confirm again.
    $this->assertFalse(db_query('SELECT aid FROM {uc_product_attributes} WHERE nid = :nid', [':nid' => $product->id()])->fetchField(), 'Attribute was detached from a product properly.');
    $this->assertFalse(uc_attribute_subject_exists($attribute->aid, 'product', $product->id()));

    // Add a product class.
    $product_class = $this->createProductClass();

    // Attach the attribute to a product class.
    uc_attribute_subject_save($attribute, 'class', $product_class->id());

    // Confirm the database is correct.
    $this->assertEqual($attribute->aid, db_query('SELECT aid FROM {uc_class_attributes} WHERE pcid = :pcid', [':pcid' => $product_class->id()])->fetchField(), 'Attribute was attached to a product class properly.');
    $this->assertTrue(uc_attribute_subject_exists($attribute->aid, 'class', $product_class->id()));

    // Test retrieval.
    $loaded_attribute = uc_attribute_load($attribute->aid, $product_class->id(), 'class');

    // Check the attribute integrity.
    foreach ($this->attributeFieldsToTest('class') as $field) {
      if ($loaded_attribute->$field != $attribute->$field) {
        $this->fail('Attribute integrity check failed.');
        break;
      }
    }

    // Delete it.
    uc_attribute_subject_delete($attribute->aid, 'class', $product_class->id());

    // Confirm again.
    $this->assertFalse(db_query('SELECT aid FROM {uc_class_attributes} WHERE pcid = :pcid', [':pcid' => $product_class->id()])->fetchField(), 'Attribute was detached from a product class properly.');
    $this->assertFalse(uc_attribute_subject_exists($attribute->aid, 'class', $product_class->id()));

    // Create a few more.
    for ($i = 0; $i < 5; $i++) {
      $a = $this->createAttribute();
      $attributes[$a->aid] = $a;
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
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array('aid' => $aid));
      $all_options[$option->aid][$option->oid] = $option;
    }

    // Get the options.
    $attribute = uc_attribute_load($attribute->aid);

    // Load every attribute we got.
    $attributes_with_options = uc_attribute_load_multiple();

    // Make sure all the new options are on attributes correctly.
    foreach ($all_options as $aid => $options) {
      foreach ($options as $oid => $option) {
        foreach ($this->attributeOptionFieldsToTest() as $field) {
          if ($option->$field != $attributes_with_options[$aid]->options[$oid]->$field) {
            $this->fail('Option integrity check failed.');
            break;
          }
        }
      }
    }

    // Pick 5 keys to check at random.
    $aids = array_rand($attributes, 3);
    $aids = array_combine($aids, $aids);

    // Load the attributes back.
    $loaded_attributes = uc_attribute_load_multiple($aids);

    // Make sure we only got the attributes we asked for. No more, no less.
    $this->assertEqual(count($aids), count($loaded_attributes), 'Verifying attribute result.');
    $this->assertEqual(count($aids), count(array_intersect_key($aids, $loaded_attributes)), 'Verifying attribute result.');

    // Check the attributes' integrity.
    foreach ($loaded_attributes as $aid => $loaded_attribute) {
      foreach ($this->attributeFieldsToTest() as $field) {
        if ($attributes[$aid]->$field != $loaded_attributes[$aid]->$field) {
          $this->fail('Attribute integrity check failed.');
          break;
        }
      }
    }

    // Add the selected attributes to the product.
    foreach ($loaded_attributes as $loaded_attribute) {
      uc_attribute_subject_save($loaded_attribute, 'product', $product->id(), TRUE);
    }

    // Test loading all product attributes. (This covers uc_attribute_load_product_attributes(),
    // as the semantics are the same -cha0s)
    $loaded_product_attributes = uc_attribute_load_multiple(array(), 'product', $product->id());

    // We'll get all in $loaded_attributes above, plus the original.
    $product_attributes = $loaded_attributes;

    // Make sure we only got the attributes we asked for. No more, no less.
    $this->assertEqual(count($loaded_product_attributes), count($product_attributes), 'Verifying attribute result.');
    $this->assertEqual(count($loaded_product_attributes), count(array_intersect_key($loaded_product_attributes, $product_attributes)), 'Verifying attribute result.');

    // Check the attributes' integrity.
    foreach ($loaded_product_attributes as $aid => $loaded_product_attribute) {
      foreach ($this->attributeFieldsToTest('product') as $field) {
        if ($loaded_product_attributes[$aid]->$field != $product_attributes[$aid]->$field) {
          $this->fail('Attribute integrity check failed.');
          break;
        }
      }
    }

    // Make sure all the options are on attributes correctly.
    foreach ($all_options as $aid => $options) {
      foreach ($options as $oid => $option) {
        if (empty($loaded_product_attributes[$aid]) || empty($loaded_product_attributes[$aid]->options[$oid])) continue;

        foreach ($this->attributeOptionFieldsToTest() as $field) {
          if ($option->$field != $loaded_product_attributes[$aid]->options[$oid]->$field) {
            $this->fail('Option integrity check failed.');
            break;
          }
        }
      }
    }

    // Add the selected attributes to the product.
    foreach ($loaded_attributes as $loaded_attribute) {
      uc_attribute_subject_save($loaded_attribute, 'class', $product_class->id(), TRUE);
    }

    // Test loading all product attributes. (This covers uc_attribute_load_product_attributes(),
    // as the semantics are the same -cha0s)
    $loaded_class_attributes = uc_attribute_load_multiple(array(), 'class', $product_class->id());

    // We'll get all in $loaded_attributes above, plus the original.
    $class_attributes = $loaded_attributes;

    // Make sure we only got the attributes we asked for. No more, no less.
    $this->assertEqual(count($loaded_class_attributes), count($class_attributes), 'Verifying attribute result.');
    $this->assertEqual(count($loaded_class_attributes), count(array_intersect_key($loaded_class_attributes, $class_attributes)), 'Verifying attribute result.');

    // Check the attributes' integrity.
    foreach ($loaded_class_attributes as $aid => $loaded_class_attribute) {
      foreach ($this->attributeFieldsToTest('class') as $field) {
        if ($loaded_class_attributes[$aid]->$field != $class_attributes[$aid]->$field) {
          $this->fail('Attribute integrity check failed.');
          break;
        }
      }
    }

    // Make sure all the options are on attributes correctly.
    foreach ($all_options as $aid => $options) {
      foreach ($options as $oid => $option) {
        if (empty($loaded_class_attributes[$aid]) || empty($loaded_class_attributes[$aid]->options[$oid])) continue;

        foreach ($this->attributeOptionFieldsToTest() as $field) {
          if ($option->$field != $loaded_class_attributes[$aid]->options[$oid]->$field) {
            $this->fail('Option integrity check failed.');
            break;
          }
        }
      }
    }

    // Test deletion of base attribute.
    $options = $attribute->options;
    uc_attribute_delete($attribute->aid);

    $this->assertFalse(uc_attribute_load($attribute->aid), 'Attribute was deleted properly.');

    // Sanity check!
    $this->assertFalse(db_query('SELECT aid FROM {uc_attributes} WHERE aid = :aid', [':aid' => $attribute->aid])->fetchField(), 'Attribute was seriously deleted properly!');

    // Test that options were deleted properly.
    foreach ($options as $option) {
      $this->assertFalse(db_query('SELECT oid FROM {uc_attribute_options} WHERE oid = :oid', [':oid' => $option->oid])->fetchField(), 'Make sure options are deleted properly.');
    }

    // Test the deletion applied to products too.
    $loaded_product_attributes = uc_attribute_load_multiple(array(), 'product', $product->id());

    // We'll get all in $loaded_attributes above, without the original. (Which
    // has been deleted.)
    $product_attributes = $loaded_attributes;

    // Make sure we only got the attributes we asked for. No more, no less.
    $this->assertEqual(count($loaded_product_attributes), count($product_attributes), 'Verifying attribute result.');
    $this->assertEqual(count($loaded_product_attributes), count(array_intersect_key($loaded_product_attributes, $product_attributes)), 'Verifying attribute result.');

    // Test the deletion applied to classes too.
    $loaded_class_attributes = uc_attribute_load_multiple(array(), 'class', $product_class->id());

    // We'll get all in $loaded_attributes above, without the original. (Which
    // has been deleted.)
    $class_attributes = $loaded_attributes;

    // Make sure we only got the attributes we asked for. No more, no less.
    $this->assertEqual(count($loaded_class_attributes), count($class_attributes), 'Verifying attribute result.');
    $this->assertEqual(count($loaded_class_attributes), count(array_intersect_key($loaded_class_attributes, $class_attributes)), 'Verifying attribute result.');

    // Add some adjustments.
    $this->createProductAdjustment(array('combination' => 'a:1:{i:1;s:1:"1";}', 'nid' => 1));
    $this->createProductAdjustment(array('combination' => 'a:1:{i:1;s:1:"2";}', 'nid' => 1));
    $this->createProductAdjustment(array('combination' => 'a:1:{i:1;s:1:"3";}', 'nid' => 1));
    $this->createProductAdjustment(array('combination' => 'a:1:{i:2;s:1:"1";}', 'nid' => 2));
    $this->createProductAdjustment(array('combination' => 'a:1:{i:3;s:1:"1";}', 'nid' => 2));
    $this->createProductAdjustment(array('combination' => 'a:1:{i:1;s:1:"2";}', 'nid' => 3));
    $this->createProductAdjustment(array('combination' => 'a:1:{i:1;s:1:"3";}', 'nid' => 3));
    $this->createProductAdjustment(array('combination' => 'a:1:{i:3;s:1:"2";}', 'nid' => 3));
    $this->createProductAdjustment(array('combination' => 'a:1:{i:3;s:1:"3";}', 'nid' => 4));

    // Test deletion by nid.
    uc_attribute_adjustments_delete(array('nid' => 1));
    $this->assertEqual(6, db_query('SELECT COUNT(*) FROM {uc_product_adjustments}')->fetchField());

    // Test deletion by aid.
    uc_attribute_adjustments_delete(array('aid' => 2));
    $this->assertEqual(5, db_query('SELECT COUNT(*) FROM {uc_product_adjustments}')->fetchField());

    // Test deletion by oid.
    uc_attribute_adjustments_delete(array('oid' => 2));
    $this->assertEqual(3, db_query('SELECT COUNT(*) FROM {uc_product_adjustments}')->fetchField());

    // Test deletion by aid and oid.
    uc_attribute_adjustments_delete(array('aid' => 1, 'oid' => 3));
    $this->assertEqual(2, db_query('SELECT COUNT(*) FROM {uc_product_adjustments}')->fetchField());
  }

  /**
   * Tests the "add attribute" user interface.
   */
  public function testAttributeUIAddAttribute() {
    $this->drupalGet('admin/store/products/attributes/add');

    $this->assertText(t('The name of the attribute used in administrative forms'), 'Attribute add form working.');

    $edit = (array) $this->createAttribute(array(), FALSE);

    $this->drupalPostForm('admin/store/products/attributes/add', $edit, t('Submit'));
    if ($edit['display'] != 0) {
      // We redirect to add options page ONLY for non-textfield attributes.
      $this->assertText('Options for ' . $edit['name']);
      $this->assertText('No options for this attribute have been added yet.');
    }
    else {
      // For textfield attributes we redirect to attribute list.
      $this->assertText($edit['name'], 'Attribute name created');
      $this->assertText($edit['label'], 'Attribute label created');
    }

    $this->drupalGet('admin/store/products/attributes');
    $this->assertRaw('<td>' . $edit['name'] . '</td>', 'Verify name field.');
    $this->assertRaw('<td>' . $edit['label'] . '</td>', 'Verify label field.');
    $this->assertRaw('<td>' . ($edit['required'] ? t('Yes') : t('No')) . '</td>', 'Verify required field.');
    $this->assertRaw('<td>' . $edit['ordering'] . '</td>', 'Verify ordering field.');
    $types = _uc_attribute_display_types();
    $this->assertRaw('<td>' . $types[$edit['display']] . '</td>', 'Verify display field.');

    $aid = db_query('SELECT aid FROM {uc_attributes} WHERE name = :name', [':name' => $edit['name']])->fetchField();
    $this->assertTrue($aid, 'Attribute was created.');

    $attribute = uc_attribute_load($aid);
    $fields_ok = TRUE;
    foreach ($edit as $field => $value) {
      if ($attribute->$field != $value) {
        $this->showVar($attribute);
        $this->showVar($edit);
        $fields_ok = FALSE;
        break;
      }
    }

    $this->assertTrue($fields_ok, 'Attribute created properly.');
  }

  /**
   * Tests the attribute settings page.
   */
  public function testAttributeUISettings() {
    $product = $this->createProduct();
    $attribute = $this->createAttribute(array(
      'display' => 1,
    ));

    $option = $this->createAttributeOption(array(
      'aid' => $attribute->aid,
      'price' => 30,
    ));

    $attribute->options[$option->oid] = $option;
    uc_attribute_subject_save($attribute, 'product', $product->id(), TRUE);

    $qty = $product->default_qty->value;
    if (!$qty) {
      $qty = 1;
    }

    $adjust_price = uc_currency_format($option->price * $qty);
    $total_price = uc_currency_format(($product->price->value + $option->price) * $qty);

    $raw = array(
      'none' => $option->name . '</option>',
      'adjustment' => $option->name . ', +' . $adjust_price . '</option>',
      'total' => $total_price . '</option>',
    );

    foreach (array('none', 'adjustment', 'total') as $type) {
      $edit['uc_attribute_option_price_format'] = $type;
      $this->drupalPostForm('admin/store/config/products', $edit, t('Save configuration'));

      $this->drupalGet('node/' . $product->id());
      $this->assertRaw($raw[$type], 'Attribute option pricing is correct.');
    }
  }

  /**
   * Tests the "edit attribute" user interface.
   */
  public function testAttributeUIEditAttribute() {
    $attribute = $this->createAttribute();

    $this->drupalGet('admin/store/products/attributes/' . $attribute->aid . '/edit');
    $this->assertText(t('Edit attribute: @name', ['@name' => $attribute->name]), 'Attribute edit form working.');

    $edit = (array) $this->createAttribute(array(), FALSE);
    $this->drupalPostForm('admin/store/products/attributes/' . $attribute->aid . '/edit', $edit, t('Submit'));

    $attribute = uc_attribute_load($attribute->aid);

    $fields_ok = TRUE;
    foreach ($edit as $field => $value) {
      if ($attribute->$field != $value) {
        $this->showVar($attribute);
        $this->showVar($edit);
        $fields_ok = FALSE;
        break;
      }
    }

    $this->assertTrue($fields_ok, 'Attribute edited properly.');
  }

  /**
   * Tests the "delete attribute" user interface.
   */
  public function testAttributeUIDeleteAttribute() {
    $attribute = $this->createAttribute();

    $this->drupalGet('admin/store/products/attributes/' . $attribute->aid . '/delete');

    $this->assertText(t('Are you sure you want to delete the attribute @name?', ['@name' => $attribute->name]), 'Attribute delete form working.');

    $edit = (array) $this->createAttribute();
    unset($edit['aid']);

    $this->drupalPostForm('admin/store/products/attributes/' . $attribute->aid . '/delete', array(), t('Delete'));

    $this->assertText(t('Product attribute deleted.'), 'Attribute deleted properly.');
  }

  /**
   * Tests the attribute options user interface.
   */
  public function testAttributeUIAttributeOptions() {
    $attribute = $this->createAttribute();
    $option = $this->createAttributeOption(array('aid' => $attribute->aid));

    uc_attribute_option_save($option);

    $this->drupalGet('admin/store/products/attributes/' . $attribute->aid . '/options');

    $this->assertText(t('Options for @name', ['@name' => $attribute->name]), 'Attribute options form working.');
  }

  /**
   * Tests the "add attribute option" user interface.
   */
  public function testAttributeUIAttributeOptionsAdd() {
    $attribute = $this->createAttribute();

    $this->drupalGet('admin/store/products/attributes/' . $attribute->aid . '/options/add');

    $this->assertText(t('Options for @name', ['@name' => $attribute->name]), 'Attribute options add form working.');

    $edit = (array) $this->createAttributeOption(array('aid' => $attribute->aid), FALSE);
    unset($edit['aid']);

    $this->drupalPostForm('admin/store/products/attributes/' . $attribute->aid . '/options/add', $edit, t('Submit'));

    $option = db_query('SELECT * FROM {uc_attribute_options} WHERE aid = :aid', [':aid' => $attribute->aid])->fetchObject();

    $fields_ok = TRUE;
    foreach ($edit as $field => $value) {
      if ($option->$field != $value) {
        $this->showVar($option);
        $this->showVar($edit);
        $fields_ok = FALSE;
        break;
      }
    }

    $this->assertTrue($fields_ok, 'Attribute option added successfully by form.');
  }

  /**
   * Tests the "edit attribute options" user interface.
   */
  public function testAttributeUIAttributeOptionsEdit() {
    $attribute = $this->createAttribute();
    $option = $this->createAttributeOption(array('aid' => $attribute->aid));

    uc_attribute_option_save($option);

    $this->drupalGet('admin/store/products/attributes/' . $attribute->aid . '/options/' . $option->oid . '/edit');

    $this->assertText(t('Edit option: @name', ['@name' => $option->name]), 'Attribute options edit form working.');

    $edit = (array) $this->createAttributeOption(array('aid' => $attribute->aid), FALSE);
    unset($edit['aid']);
    $this->drupalPostForm('admin/store/products/attributes/' . $attribute->aid . '/options/' . $option->oid . '/edit', $edit, t('Submit'));

    $option = uc_attribute_option_load($option->oid);

    $fields_ok = TRUE;
    foreach ($edit as $field => $value) {
      if ($option->$field != $value) {
        $this->showVar($option);
        $this->showVar($edit);
        $fields_ok = FALSE;
        break;
      }
    }

    $this->assertTrue($fields_ok, 'Attribute option edited successfully by form.');
  }

  /**
   * Tests the "delete attribute option" user interface.
   */
  public function testAttributeUIAttributeOptionsDelete() {
    $attribute = $this->createAttribute();
    $option = $this->createAttributeOption(array('aid' => $attribute->aid));

    uc_attribute_option_save($option);

    $this->drupalGet('admin/store/products/attributes/' . $attribute->aid . '/options/' . $option->oid . '/delete');

    $this->assertText(t('Are you sure you want to delete the option @name?', ['@name' => $option->name]), 'Attribute options delete form working.');

    $this->drupalPostForm('admin/store/products/attributes/' . $attribute->aid . '/options/' . $option->oid . '/delete', array(), t('Delete'));

    $option = uc_attribute_option_load($option->oid);

    $this->assertFalse($option, 'Attribute option deleted successfully by form');
  }

  /**
   * Tests the product class attribute user interface.
   */
  public function testAttributeUIClassAttributeOverview() {
    $class = $this->createProductClass();
    $attribute = $this->createAttribute();

    $this->drupalGet('admin/structure/types/manage/' . $class->id() . '/attributes');

    $this->assertText(t('No attributes available.'), 'Class attribute form working.');

    uc_attribute_subject_save($attribute, 'class', $class->id());

    $this->drupalGet('admin/structure/types/manage/' . $class->id() . '/attributes');

    $this->assertNoText(t('No attributes available.'), 'Class attribute form working.');

    $a = (array) $this->createAttribute(array(), FALSE);
    unset($a['name'], $a['description']);
    foreach ($a as $field => $value) {
      $edit["attributes[{$attribute->aid}][$field]"] = $value;
    }
    $this->showVar($edit);
    $this->drupalPostForm('admin/structure/types/manage/' . $class->id() . '/attributes', $edit, t('Save changes'));

    $attribute = uc_attribute_load($attribute->aid, $class->id(), 'class');

    $fields_ok = TRUE;
    foreach ($a as $field => $value) {
      if ($attribute->$field != $value) {
        $this->showVar($attribute);
        $this->showVar($a);
        $fields_ok = FALSE;
        break;
      }
    }

    $this->assertTrue($fields_ok, 'Class attribute edited successfully by form.');

    $edit = array();
    $edit["attributes[{$attribute->aid}][remove]"] = TRUE;
    $this->drupalPostForm('admin/structure/types/manage/' . $class->id() . '/attributes', $edit, t('Save changes'));

    $this->assertText(t('No attributes available.'), 'Class attribute form working.');
  }

  /**
   * Tests the "add product class attribute option" user interface.
   */
  public function testAttributeUIClassAttributeAdd() {
    $class = $this->createProductClass();
    $attribute = $this->createAttribute();

    $this->drupalGet('admin/structure/types/manage/' . $class->id() . '/attributes/add');

    $this->assertRaw(t('@attribute</label>', ['@attribute' => $attribute->name]), 'Class attribute add form working.');

    $edit['add_attributes[' . $attribute->aid . ']'] = 1;

    $this->drupalPostForm('admin/structure/types/manage/' . $class->id() . '/attributes/add', $edit, t('Add attributes'));

    $this->assertNoText(t('No attributes available.'), 'Class attribute form working.');
  }

  /**
   * Tests the product class attribute option user interface.
   */
  public function testAttributeUIClassAttributeOptionOverview() {
    $class = $this->createProductClass();
    $attribute = $this->createAttribute();
    $option = $this->createAttributeOption(array('aid' => $attribute->aid));

    uc_attribute_subject_save($attribute, 'class', $class->id());

    $this->drupalGet('admin/structure/types/manage/' . $class->id() . '/options');

    $this->assertRaw(t('@option</label>', ['@option' => $option->name]), 'Class attribute option form working.');

    $o = (array) $this->createAttributeOption(array('aid' => $attribute->aid), FALSE);
    unset($o['name'], $o['aid']);
    $o['select'] = TRUE;
    foreach ($o as $field => $value) {
      $edit["attributes[$attribute->aid][options][$option->oid][$field]"] = $value;
    }
    unset($o['select']);
    $edit["attributes[$attribute->aid][default]"] = $option->oid;
    $this->showVar($edit);
    $this->drupalPostForm('admin/structure/types/manage/' . $class->id() . '/options', $edit, t('Save changes'));
    $this->assertText(t('The changes have been saved.'), 'Class attribute option saved.');
    $this->showVar($option);

    $option = uc_attribute_subject_option_load($option->oid, 'class', $class->id());

    $fields_ok = TRUE;
    foreach ($o as $field => $value) {
      if ($option->$field != $value) {
        $this->showVar($option);
        $this->showVar($o);
        $fields_ok = FALSE;
        break;
      }
    }
    $this->assertTrue($fields_ok, 'Class attribute option edited successfully by form.');
  }

  /**
   * Tests the "product attributes" page.
   */
  public function testAttributeUIProductAttributes() {
    $product = $this->createProduct();
    $attribute = $this->createAttribute(array('display' => 1));
    $option = $this->createAttributeOption(array('aid' => $attribute->aid));

    $this->drupalGet('node/' . $product->id() . '/edit/attributes');
    $this->assertText('No attributes available.');

    $this->clickLink('Add existing attribute');
    $this->assertText($attribute->name);

    $this->drupalPostForm(NULL, array('add_attributes[' . $attribute->aid. ']' => 1), t('Add attributes'));
    $this->assertText('1 attribute has been added.');
    $this->assertText($attribute->name, 'Attribute name found');
    $this->assertFieldByName('attributes[' . $attribute->aid . '][label]', $attribute->label, 'Attribute label found');
    $this->assertText($option->name, 'Default option name found');
    $this->assertText(uc_currency_format($option->price), 'Default option price found');
    $this->assertFieldByName('attributes[' . $attribute->aid . '][display]', $attribute->display, 'Attribute display setting found');

    $this->drupalGet('node/' . $product->id() . '/edit/attributes/add');
    $this->assertNoText($attribute->name);
    $this->assertText('No attributes left to add.');

    $edit = array('attributes[' . $attribute->aid. '][remove]' => 1);
    $this->drupalPostForm('node/' . $product->id() . '/edit/attributes', $edit, t('Save changes'));
    $this->assertText('No attributes available.');
  }

  /**
   * Tests the "product options" page.
   */
  public function testAttributeUIProductOptions() {
    $product = $this->createProduct();
    $attribute = $this->createAttribute(array('display' => 1));
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array('aid' => $attribute->aid));
      $attribute->options[$option->oid] = $option;
    }
    uc_attribute_subject_save($attribute, 'product', $product->id(), TRUE);

    $this->drupalGet('node/' . $product->id() . '/edit/options');
    $this->assertText($attribute->name, 'Attribute name found');
    foreach ($attribute->options as $option) {
      $this->assertText($option->name, 'Option name found');
      $this->assertFieldByName('attributes[' . $attribute->aid . '][options][' . $option->oid . '][cost]', $option->cost, 'Option cost field found');
      $this->assertFieldByName('attributes[' . $attribute->aid . '][options][' . $option->oid . '][price]', $option->price, 'Option price field found');
      $this->assertFieldByName('attributes[' . $attribute->aid . '][options][' . $option->oid . '][weight]', $option->weight, 'Option weight field found');
    }
  }

  /**
   * Tests the "product adjustments" page.
   */
  public function testAttributeUIProductAdjustments() {
    $product = $this->createProduct();
    $attribute = $this->createAttribute(array('display' => 1));
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array('aid' => $attribute->aid));
      $adjustment = $this->createProductAdjustment(array('combination' => serialize(array($attribute->aid => $option->oid)), 'nid' => $product->id()));
      $option->model = $adjustment->model;
      $attribute->options[$option->oid] = $option;
    }
    uc_attribute_subject_save($attribute, 'product', $product->id(), TRUE);

    $this->drupalGet('node/' . $product->id() . '/edit/adjustments');
    $this->assertText('Default product SKU: ' . $product->model->value, 'Default product SKU found');
    $this->assertRaw('<th>' . $attribute->name . '</th>', 'Attribute name found');
    foreach ($attribute->options as $option) {
      $this->assertRaw('<td>' . $option->name . '</td>', 'Option name found');
      $this->assertRaw($option->model, 'Option SKU found');
    }
  }

  /**
   * Tests attributes applied to a product.
   */
  public function testProductAttribute() {
    $product = $this->createProduct();
    $attribute = $this->createAttribute(array('display' => 2, 'required' => TRUE));
    for ($i = 0; $i < 3; $i++) {
      $option = $this->createAttributeOption(array('aid' => $attribute->aid));
      $adjustment = $this->createProductAdjustment(array('combination' => serialize(array($attribute->aid => $option->oid)), 'nid' => $product->id()));
      $option->model = $adjustment->model;
      $attribute->options[$option->oid] = $option;
    }
    uc_attribute_subject_save($attribute, 'product', $product->id(), TRUE);

    // Product node display.
    $this->drupalGet('node/' . $product->id());
    $this->assertText($attribute->label, 'Attribute label found for product');
    $this->assertText($attribute->description, 'Attribute description found for product');
    foreach ($attribute->options as $option) {
      $this->assertText($option->name, 'Option name found for product');
      $this->assertText(uc_currency_format($option->price), 'Option price adjustment found for product');
    }

    // Test required attribute.
    $this->addToCart($product);
    $this->assertText($attribute->label . ' field is required', 'Required attribute message found.');

    // Cart display.
    $price = uc_currency_format($product->price->value + $option->price);
    $this->addToCart($product, array('attributes[' . $attribute->aid . ']' => $option->oid));
    $this->assertText($attribute->label . ': ' . $option->name, 'Attribute and selected option found in cart');
    $this->assertText($price, 'Adjusted price found in cart');

    // Checkout display.
    $this->drupalPostForm(NULL, array(), 'Checkout');
    $this->assertText($attribute->label . ': ' . $option->name, 'Attribute and selected option found at checkout');
    $this->assertText($price, 'Adjusted price found at checkout');
    $this->checkout();

    // Admin order display.
    $cost = uc_currency_format($product->cost->value + $option->cost);
    $this->drupalGet('admin/store/orders/1');
    $this->assertText($attribute->label . ': ' . $option->name, 'Attribute and selected option found in admin order display');
    $this->assertText($option->model, 'Adjusted SKU found in admin order display');
    $this->assertText($cost, 'Adjusted cost found in admin order display');
    $this->assertText($price, 'Adjusted price found in admin order display');

    // Invoice display.
    $this->drupalGet('admin/store/orders/1/invoice');
    $this->assertText($attribute->label . ': ' . $option->name, 'Attribute and selected option found on invoice');
    $this->assertText('SKU: ' . $option->model, 'Adjusted SKU found on invoice');
    $this->assertText($price, 'Adjusted price found on invoice');
  }

  /**
   * Tests that product in cart has the selected attribute option.
   */
  public function testAttributeAddToCart() {
    for ($display = 0; $display <= 3; ++$display) {
      // Set up an attribute.
      $data = array(
        'display' => $display,
      );
      $attribute = $this->createAttribute($data);

      if ($display) {
        // Give the attribute an option.
        $option = $this->createAttributeOption(array('aid' => $attribute->aid));
      }

      $attribute = uc_attribute_load($attribute->aid);

      // Put the attribute on a product.
      $product = $this->createProduct();
      uc_attribute_subject_save($attribute, 'product', $product->id(), TRUE);

      // Add the product to the cart.
      if ($display == 3) {
        $edit = array("attributes[$attribute->aid][$option->oid]" => $option->oid);
      }
      elseif (isset($option)) {
        $edit = array("attributes[$attribute->aid]" => $option->oid);
      }
      else {
        $option = new \stdClass();
        $option->name = self::randomMachineName();
        $option->price = 0;
        $edit = array("attributes[$attribute->aid]" => $option->name);
      }

      $this->addToCart($product, $edit);
      $this->assertText("$attribute->label: $option->name", 'Option selected on cart item.');
      $this->assertText(uc_currency_format($product->price->value + $option->price), 'Product has adjusted price.');
    }
  }

  /**
   * Creates a product adjustment SKU.
   *
   * @param $data
   */
  public function createProductAdjustment($data) {
    $adjustment = $data + array(
      'model' => $this->randomMachineName(8),
    );
    db_insert('uc_product_adjustments')
      ->fields($adjustment)
      ->execute();
    return (object) $adjustment;
  }

  /**
   * Returns an array of available fields for product or class attributes.
   *
   * @param $type
   */
  protected function attributeFieldsToTest($type = '') {
    $fields = array(
      'aid', 'name', 'ordering', 'required', 'display', 'description', 'label',
    );

    switch ($type) {
      case 'product':
      case 'class':
        $info = uc_attribute_type_info($type);
        $fields = array_merge($fields, array($info['id']));
        break;
    }
    return $fields;
  }

  /**
   * Returns array of available fields for product or class attribute options.
   *
   * @param $type
   */
  protected function attributeOptionFieldsToTest($type = '') {
    $fields = array(
      'aid', 'oid', 'name', 'cost', 'price', 'weight', 'ordering',
    );

    switch ($type) {
      case 'product':
      case 'class':
        $info = uc_attribute_type_info($type);
        $fields = array_merge($fields, array($info['id']));
        break;
    }
    return $fields;
  }

  /**
   * Debug helper function.
   *
   * @param $var
   */
  protected function showVar($var) {
    $this->pass('<pre>' . print_r($var, TRUE) . '</pre>');
  }
}
