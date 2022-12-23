<?php

namespace Drupal\Tests\rules\Functional;

/**
 * Tests that each Rules Action can be editted.
 *
 * @group RulesUi
 */
class ActionsFormTest extends RulesBrowserTestBase {

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
   * Test each action provided by Rules.
   *
   * Check that every action can be added to a rule and that the edit page can
   * be accessed. This ensures that the datatypes used in the definitions do
   * exist. This test does not execute the conditions or actions.
   *
   * @dataProvider dataActionsFormWidgets
   */
  public function testActionsFormWidgets($id, $values = [], $widgets = [], $selectors = []) {
    $expressionManager = $this->container->get('plugin.manager.rules_expression');
    $storage = $this->container->get('entity_type.manager')->getStorage('rules_reaction_rule');

    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Create a rule.
    $rule = $expressionManager->createRule();
    // Add the action to the rule.
    $action = $expressionManager->createAction($id);
    $rule->addExpressionObject($action);
    // Save the configuration.
    $expr_id = 'test_action_' . str_replace(':', '_', $id);
    $config_entity = $storage->create([
      'id' => $expr_id,
      'expression' => $rule->getConfiguration(),
      // Specify a node event so that the node... selector values are available.
      'events' => [['event_name' => 'rules_entity_update:node']],
    ]);
    $config_entity->save();
    // Edit the action and check that the page is generated without error.
    $this->drupalGet('admin/config/workflow/rules/reactions/edit/' . $expr_id . '/edit/' . $action->getUuid());
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Edit ' . $action->getLabel());

    // If any field values have been specified then fill in the form and save.
    if (!empty($values)) {

      // Switch to data selector if required by the test settings.
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

      // Check that the action can be saved.
      $this->pressButton('Save');
      $assert->pageTextNotContains('InvalidArgumentException: Cannot set a list with a non-array value');
      $assert->pageTextNotContains('Error message');
      $assert->pageTextContains('You have unsaved changes.');
      // Allow for the ?uuid query string being present or absent in the assert
      // method by using addressMatches() with regex instead of addressEquals().
      $assert->addressMatches('#admin/config/workflow/rules/reactions/edit/' . $expr_id . '(\?uuid=' . $action->getUuid() . '|)$#');

      // Check that re-edit and re-save works OK.
      $this->clickLink('Edit');
      $this->pressButton('Save');
      $assert->pageTextNotContains('Error message');
      $assert->addressMatches('#admin/config/workflow/rules/reactions/edit/' . $expr_id . '(\?uuid=' . $action->getUuid() . '|)$#');

      // Save the rule.
      $this->pressButton('Save');
      $assert->pageTextContains("Reaction rule $expr_id has been updated");
    }

  }

  /**
   * Provides data for testActionsFormWidgets().
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
  public function dataActionsFormWidgets() {
    // Instead of directly returning the full set of test data, create variable
    // $data to hold it. This allows for manipulation before the final return.
    $data = [
      'Data calculate value' => [
        // Machine name.
        'rules_data_calculate_value',
        // Values.
        [
          'input-1' => '3',
          'operator' => '*',
          'input-2' => '4',
        ],
        // Widgets.
        [
          'input-1' => 'text-input',
          'operator' => 'text-input',
          'input-2' => 'text-input',
        ],
      ],
      'Data convert' => [
        'rules_data_convert',
        ['value' => 'node.uid', 'target-type' => 'string'],
      ],
      'List item add' => [
        'rules_list_item_add',
        [
          'list' => 'node.uid.entity.roles',
          'item' => '1',
          'unique' => TRUE,
          'position' => 'start',
        ],
      ],
      'List item remove' => [
        'rules_list_item_remove',
        ['list' => 'node.uid.entity.roles', 'item' => '1'],
      ],
      'Data set - direct' => [
        'rules_data_set',
        ['data' => 'node.title', 'value' => 'abc'],
      ],
      'Data set - selector' => [
        // Machine name.
        'rules_data_set',
        // Values.
        ['data' => 'node.title', 'value' => '@user.current_user_context:current_user.name.value'],
        // Widgets.
        [],
        // Selectors.
        ['value'],
      ],
      'Entity create node' => [
        'rules_entity_create:node',
        ['type' => 'article', 'title' => 'abc'],
      ],
      'Entity create user' => [
        'rules_entity_create:user',
        ['name' => 'fred'],
      ],
      'Entity delete' => [
        'rules_entity_delete',
        ['entity' => 'node'],
      ],
      'Entity fetch by field - selector' => [
        'rules_entity_fetch_by_field',
        ['type' => 'node', 'field-name' => 'abc', 'field-value' => 'node.uid'],
        [],
        ['field-value'],
      ],
      'Entity fetch by field - direct' => [
        'rules_entity_fetch_by_id',
        ['type' => 'node', 'entity-id' => 123],
      ],
      'Entity path alias create' => [
        'rules_entity_path_alias_create:entity:node',
        ['entity' => 'node', 'alias' => 'abc'],
      ],
      'Entity save' => [
        'rules_entity_save',
        ['entity' => 'node', 'immediate' => TRUE],
      ],
      'Node make sticky' => [
        'rules_node_make_sticky',
        ['node' => 'node'],
      ],
      'Node make unsticky' => [
        'rules_node_make_unsticky',
        ['node' => 'node'],
      ],
      'Node publish' => [
        'rules_node_publish',
        ['node' => 'node'],
      ],
      'Node unpublish' => [
        'rules_node_unpublish',
        ['node' => 'node'],
      ],
      'Node promote' => [
        'rules_node_promote',
        ['node' => 'node'],
      ],
      'Node unpromote' => [
        'rules_node_unpromote',
        ['node' => 'node'],
      ],
      'Path alias create' => [
        'rules_path_alias_create',
        ['source' => '/node/1', 'alias' => 'abc'],
      ],
      'Path alias delete by alias' => [
        'rules_path_alias_delete_by_alias',
        ['alias' => 'abc'],
      ],
      'Path alias delete by path' => [
        'rules_path_alias_delete_by_path',
        ['path' => '/node/1'],
      ],
      'Page redirect' => [
        'rules_page_redirect',
        ['url' => '/node/1'],
      ],
      'Send account email' => [
        'rules_send_account_email',
        ['user' => 'node.uid', 'email-type' => 'abc'],
      ],
      'Email to all users of role' => [
        'rules_email_to_users_of_role',
        ['roles' => 'editor', 'subject' => 'Hello', 'message' => 'Some text'],
        ['message' => 'textarea'],
      ],
      'System message' => [
        'rules_system_message',
        ['message' => 'Some text'],
      ],
      'Send email - direct input' => [
        'rules_send_email',
        [
          'to' => 'test@example.com',
          'subject' => 'Some testing subject',
          'message' => 'Test with direct input of recipients',
        ],
        ['message' => 'textarea'],
      ],
      'Send email - data selector for address' => [
        'rules_send_email',
        [
          'to' => 'node.uid.entity.mail.value',
          'subject' => 'Some testing subject',
          'message' => 'Test with selector input of node author',
        ],
        ['message' => 'textarea'],
        ['to'],
      ],
      'User block' => [
        'rules_user_block',
        ['user' => '@user.current_user_context:current_user'],
        [],
        ['user'],
      ],
      'User role add' => [
        'rules_user_role_add',
        ['user' => '@user', 'roles' => 'Editor'],
      ],
      'User role remove' => [
        'rules_user_role_remove',
        ['user' => '@user', 'roles' => 'Editor'],
      ],
      'Unblock user' => [
        'rules_user_unblock',
        ['user' => '@user'],
      ],
      'Variable add' => [
        'rules_variable_add',
        ['type' => 'integer', 'value' => 'node.nid'],
      ],
      'Ban IP - empty' => [
        'rules_ban_ip',
        ['ip' => ''],
      ],
      'Ban IP - value' => [
        'rules_ban_ip',
        ['ip' => '192.0.2.1'],
      ],
      'Unban IP' => [
        'rules_unban_ip',
        ['ip' => '192.0.2.1'],
      ],
    ];
    // Selecting the 'to' email address using data selector will not work until
    // single data selector values with multiple = True are converted to arrays.
    // @see https://www.drupal.org/project/rules/issues/2723259
    // @todo Delete this unset() when the above issue is fixed.
    unset($data['Send email - data selector for address']);

    // Use unset $data['The key to remove']; to remove a temporarily unwanted
    // item, use return [$data['The key to test']]; to selectively test just one
    // item, or have return $data; to test everything.
    return $data;
  }

}
