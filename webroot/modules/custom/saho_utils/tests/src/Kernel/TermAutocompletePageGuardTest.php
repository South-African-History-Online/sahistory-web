<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_utils\Kernel;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * A node id pasted into a taxonomy-term autocomplete gets a plain answer.
 *
 * Issue #683: "Feature (Parent)" (a term field) and "Feature Parent" (a
 * node field) look alike, so editors paste "Title (8897)" across and core
 * only says the term does not exist.
 *
 * @group saho_utils
 */
final class TermAutocompletePageGuardTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'taxonomy',
    'saho_utils',
  ];

  /**
   * The node that shares its id with no term.
   */
  private Node $page;

  /**
   * The term an editor should have picked.
   */
  private Term $tag;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'filter', 'node']);

    NodeType::create(['type' => 'biography', 'name' => 'Biography'])->save();
    NodeType::create(['type' => 'upcomingevent', 'name' => 'Upcoming Event'])->save();
    Vocabulary::create(['vid' => 'field_feature_tag', 'name' => 'Feature tag'])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_parent',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'taxonomy_term'],
      'cardinality' => -1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_parent',
      'entity_type' => 'node',
      'bundle' => 'upcomingevent',
      'label' => 'Feature (Parent)',
      'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => []],
    ])->save();

    // Make the node id sit above every term id so the two cannot collide.
    $this->tag = Term::create(['vid' => 'field_feature_tag', 'name' => 'Solomon Tshekisho Plaatje']);
    $this->tag->save();
    $this->page = Node::create(['type' => 'biography', 'title' => 'Solomon Tshekisho Plaatje', 'nid' => 8897]);
    $this->page->save();
  }

  /**
   * Runs the guard validator against one autocomplete input.
   */
  private function validate(string $input): ?string {
    $element = [
      '#type' => 'entity_autocomplete',
      '#title' => 'Feature (Parent)',
      '#value' => $input,
      '#parents' => ['field_parent', 0, 'target_id'],
    ];
    $form_state = new FormState();
    _saho_utils_validate_term_autocomplete_is_not_a_page($element, $form_state);
    $errors = $form_state->getErrors();
    return $errors === [] ? NULL : (string) reset($errors);
  }

  /**
   * A page id in a term field is named as such.
   */
  public function testPageIdIsExplained(): void {
    $message = $this->validate('Solomon Tshekisho Plaatje (8897)');
    $this->assertNotNull($message);
    $this->assertStringContainsString('Feature (Parent)', $message);
    $this->assertStringContainsString('not a page', $message);
    $this->assertStringContainsString('Biography page', $message);
    $this->assertStringContainsString('node 8897', $message);
    $this->assertStringContainsString('pick a suggestion', $message);
  }

  /**
   * Inputs that core handles already stay silent here.
   */
  public function testOtherInputsAreLeftToCore(): void {
    // A real term, as the autocomplete writes it.
    $this->assertNull($this->validate('Solomon Tshekisho Plaatje (' . $this->tag->id() . ')'));
    // A bare numeric id that is a term.
    $this->assertNull($this->validate((string) $this->tag->id()));
    // An id that is neither a term nor a node: core says "does not exist".
    $this->assertNull($this->validate('Nobody (999999)'));
    // Free text with no id: core resolves it by label.
    $this->assertNull($this->validate('Solomon Tshekisho Plaatje'));
    // Empty input.
    $this->assertNull($this->validate(''));
  }

  /**
   * The widget alter wires the guard in front of core's validator.
   */
  public function testWidgetAlterPrependsGuard(): void {
    $node = Node::create(['type' => 'upcomingevent', 'title' => 'Talk']);
    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay('node', 'upcomingevent');
    $form_display->setComponent('field_parent', ['type' => 'entity_reference_autocomplete']);
    $widget = $form_display->getRenderer('field_parent');
    $form = ['#parents' => []];
    $form_state = new FormState();
    $element = $widget->form($node->get('field_parent'), $form, $form_state);

    $validators = $element['widget'][0]['target_id']['#element_validate'];
    $this->assertSame('_saho_utils_validate_term_autocomplete_is_not_a_page', $validators[0]);
    $this->assertSame([EntityAutocomplete::class, 'validateEntityAutocomplete'], $validators[1]);
  }

}
