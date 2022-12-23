<?php

namespace Drupal\Tests\rules\Functional;

/**
 * Tests that each Rules Condition can be editted.
 *
 * @group RulesUi
 */
class ConditionsFormTest extends RulesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ban',
    'path_alias',
    'rules',
    'typed_data',
  ];

  /**
   * We use the minimal profile because we want to test local action links.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * A user account with administration permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an article content type that we will use for testing.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();

    $this->account = $this->drupalCreateUser([
      'administer rules',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Test each condition provided by Rules.
   *
   * Check that every condition can be added to a rule and that the edit page
   * can be accessed. This ensures that the datatypes used in the definitions
   * do exist. This test does not execute the conditions or actions.
   *
   * @dataProvider dataConditionsFormWidgets
   */
  public function testConditionsFormWidgets($id, $values = [], $widgets = [], $selectors = []) {
    $expressionManager = $this->container->get('plugin.manager.rules_expression');
    $storage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Create a rule.
    $rule = $expressionManager->createRule();
    // Add the condition to the rule.
    $condition = $expressionManager->createCondition($id);
    $rule->addExpressionObject($condition);
    // Save the configuration.
    $expr_id = 'test_condition_' . $id;
    $config_entity = $storage->create([
      'id' => $expr_id,
      'expression' => $rule->getConfiguration(),
      // Specify a node event so that the node... selector values are available.
      'events' => [['event_name' => 'rules_entity_update:node']],
    ]);
    $config_entity->save();
    // Edit the condition and check that the page is generated without error.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/' . $expr_id . '/edit/' . $condition->getUuid());
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Edit ' . $condition->getLabel());

    // If any field values have been specified then fill in the form and save.
    if (!empty($values)) {

      // Switch to data selector where required.
      if (!empty($selectors)) {
        foreach ($selectors as $name) {
          $this->pressButton('edit-context-definitions-' . $name . '-switch-button');
          // Check that the switch worked.
          $assert->elementExists('xpath', '//input[@id="edit-context-definitions-' . $name . '-switch-button" and contains(@value, "Switch to the direct input mode")]');
        }
      }

      // Fill each given field with the value provided.
      foreach ($values as $name => $value) {
        $this->fillField('edit-context-definitions-' . $name . '-setting', $value);
      }

      // Check that the condition can be saved.
      $this->pressButton('Save');
      $assert->pageTextNotContains('InvalidArgumentException: Cannot set a list with a non-array value');
      $assert->pageTextNotContains('Error message');
      $assert->pageTextContains('You have unsaved changes.');
      // Allow for the ?uuid query string being present or absent in the assert
      // method by using addressMatches() with regex instead of addressEquals().
      $assert->addressMatches('#admin/config/workflow/rules/reactions/edit/' . $expr_id . '(\?uuid=' . $condition->getUuid() . '|)$#');

      // Check that re-edit and re-save works OK.
      $this->clickLink('Edit');
      $this->pressButton('Save');
      $assert->pageTextNotContains('Error message');
      $assert->addressMatches('#admin/config/workflow/rules/reactions/edit/' . $expr_id . '(\?uuid=' . $condition->getUuid() . '|)$#');

      // Save the rule.
      $this->pressButton('Save');
      $assert->pageTextContains("Reaction rule $expr_id has been updated");
    }
  }

  /**
   * Provides data for testConditionsFormWidgets().
   *
   * @return array
   *   The test data array. The top level keys are free text but should be short
   *   and relate to the test case. The values are ordered arrays of test case
   *   data with elements that must appear in the following order:
   *   - Machine name of the condition being tested.
   *   - (optional) Values to enter on the Context form. This is an associative
   *     array with keys equal to the field names and values equal to the field
   *     values.
   *   - (optional) Widget types we expect to see on the Context form. This is
   *     an associative array with keys equal to the field names as above, and
   *     values equal to expected widget type.
   *   - (optional) Names of fields for which the selector/direct input button
   *     needs pressing to 'data selection' before the field value is entered.
   */
  public function dataConditionsFormWidgets() {
    // Instead of directly returning the full set of test data, create variable
    // $data to hold it. This allows for manipulation before the final return.
    $data = [
      'Data comparison' => [
        // Machine name.
        'rules_data_comparison',
        // Values.
        [
          'data' => 'node.title.value',
          'operation' => '=this=is-not-validated=yet=',
          'value' => 'node_unchanged.title.value',
        ],
        // Widgets.
        [
          'data' => 'text-input',
          'operation' => 'text-input',
          'value' => 'text-input',
        ],
        // Selectors.
        ['value'],
      ],
      'Data is empty' => [
        'rules_data_is_empty',
        ['data' => 'node.title.value'],
      ],
      'List contains' => [
        'rules_list_contains',
        ['list' => 'node.uid.entity.roles', 'item' => 'abc'],
        ['list' => 'textarea'],
      ],
      'List Count' => [
        'rules_list_count_is',
        [
          'list' => 'node.uid.entity.roles',
          'operator' => 'not * validated * yet',
          'value' => 2,
        ],
      ],
      'Entity has field' => [
        'rules_entity_has_field',
        ['entity' => 'node', 'field' => 'abc'],
      ],
      'Entity is new' => [
        'rules_entity_is_new',
        ['entity' => 'node'],
      ],
      'Entity is bundle' => [
        'rules_entity_is_of_bundle',
        ['entity' => 'node', 'type' => 'node', 'bundle' => 'article'],
      ],
      'Entity is type' => [
        'rules_entity_is_of_type',
        ['entity' => 'node', 'type' => 'article'],
      ],
      'Node is type' => [
        'rules_node_is_of_type',
        ['node' => 'node', 'types' => 'article'],
      ],
      'Node is promoted' => [
        'rules_node_is_promoted',
        ['node' => 'node'],
      ],
      'Node is published' => [
        'rules_node_is_published',
        ['node' => 'node'],
      ],
      'Node is sticky' => [
        'rules_node_is_sticky',
        ['node' => 'node'],
      ],
      'Path alias exists' => [
        'rules_path_alias_exists',
        ['alias' => '/abc'],
      ],
      'Path has alias' => [
        'rules_path_has_alias',
        ['path' => '/node/1'],
      ],
      'Text comparison - direct' => [
        'rules_text_comparison',
        ['text' => 'node.title.value', 'match' => 'abc'],
      ],
      'Text comparison - selector' => [
        'rules_text_comparison',
        ['text' => 'node.title.value', 'match' => 'node.uid.entity.name.value'],
        [],
        ['match'],
      ],
      'Entity field access' => [
        'rules_entity_field_access',
        [
          'entity' => 'node',
          'field' => 'abc',
          'user' => '@user.current_user_context:current_user',
        ],
      ],
      'Uer has role' => [
        'rules_user_has_role',
        [
          'user' => '@user.current_user_context:current_user',
          'roles' => 'Developer',
        ],
      ],
      'User is blocked' => [
        'rules_user_is_blocked',
        ['user' => '@user.current_user_context:current_user'],
      ],
      'Ip is banned' => [
        'rules_ip_is_banned',
        ['ip' => '192.0.2.1'],
      ],
    ];

    // Use unset $data['The key to remove']; to remove a temporarily unwanted
    // item, use return [$data['The key to test']]; to selectively test just one
    // item, or use return $data; to test everything.
    return $data;
  }

}
