<?php

namespace Drupal\rules\Context;

use Drupal\Core\Plugin\Context\ContextDefinition as ContextDefinitionCore;
use Drupal\Component\Plugin\Exception\ContextException;

/**
 * Extends the core context definition class with useful methods.
 *
 * This class overrides the core ContextDefinition to provide Rules-specific
 * functionality, but also to preserve the Drupal 8 behavior of
 * ContextDefinition without triggering deprecated code. Specifically, in Rules
 * we need to be able to create a ContextDefinition for ANY typed data object,
 * without prior knowledge of what that type is; we need to be able to say
 * new ContextDefinition($type) or ContextDefinition::create($type) where $type
 * may be 'integer', or 'string', or 'entity:node' etc.
 *
 * This worked in Drupal 8, but in Drupal 9 we now have to use different classes
 * for different types. Thus the core ContextDefinition::create($type) will work
 * ONLY for non-entity types, and we have to use a different way to create
 * context definitions for entities. This is a problem because now there is no
 * factory method to create context definitions, and we have to test each type
 * and just "know" the correct class to use for that type to create a context
 * definition.
 *
 * This Drupal 9 behavior is unworkable in a module like Rules where we rely on
 * introspection and reflection to manipulate typed data. Without some way to
 * programmatically create a context definition for an arbitrary data type,
 * Rules will not work.
 *
 * To work around this, we override the core ContextDefinition's __construct()
 * and create() methods. In the parent::__construct(), there is an explicit
 * assert that prevents ContextDefinition from being used for an entity. We
 * remove that here - the __construct() method of this class is otherwise
 * identical to the parent. We also override the core ContextDefinition's
 * create() method to create a Rules version of EntityContextDefinition when
 * ContextDefinition is created for an entity type. This is necessary because
 * the core EntityContextDefinition doesn't have the necessary Rules extensions
 * and there is no multiple inheritance in PHP so we have to extend
 * ContextDefinition and EntityContextDefinition separately.
 *
 * This is a poor solution that will work for existing core Rules use-cases, as
 * EntityContextDefinition is never used directly in Rules, but this may not
 * work for modules that extend Rules. A proper and permanent solution will
 * require a change to core Drupal.
 *
 * @see \Drupal\rules\Context\EntityContextDefinition
 * @see https://www.drupal.org/project/rules/issues/3161582
 * @see https://www.drupal.org/project/drupal/issues/3126747
 */
class ContextDefinition extends ContextDefinitionCore implements ContextDefinitionInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct($data_type = 'any', $label = NULL, $required = TRUE, $multiple = FALSE, $description = NULL, $default_value = NULL) {
    $this->dataType = $data_type;
    $this->label = $label;
    $this->isRequired = $required;
    $this->isMultiple = $multiple;
    $this->description = $description;
    $this->defaultValue = $default_value;
  }

  /**
   * The created context definition object.
   */
  public static function create($data_type = 'any') {
    if (strpos($data_type, 'entity:') === 0) {
      return new EntityContextDefinition($data_type);
    }
    return new static(
      $data_type
    );
  }

  /**
   * The mapping of config export keys to internal properties.
   *
   * @var array
   */
  protected static $nameMap = [
    'type' => 'dataType',
    'label' => 'label',
    'description' => 'description',
    'multiple' => 'isMultiple',
    'required' => 'isRequired',
    'default_value' => 'defaultValue',
    'constraints' => 'constraints',
    'allow_null' => 'allowNull',
    'assignment_restriction' => 'assignmentRestriction',
  ];

  /**
   * Whether the context value is allowed to be NULL or not.
   *
   * @var bool
   */
  protected $allowNull = FALSE;

  /**
   * The assignment restriction of this context.
   *
   * @var string|null
   *
   * @see \Drupal\rules\Context\ContextDefinitionInterface::getAssignmentRestriction()
   */
  protected $assignmentRestriction = NULL;

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $values = [];
    $defaults = get_class_vars(__CLASS__);
    foreach (static::$nameMap as $key => $property_name) {
      // Only export values for non-default properties.
      if ($this->$property_name !== $defaults[$property_name]) {
        $values[$key] = $this->$property_name;
      }
    }
    return $values;
  }

  /**
   * Creates a definition object from an exported array of values.
   *
   * @param array $values
   *   The array of values, as returned by toArray().
   *
   * @return static
   *   The created definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   *   If the required classes are not implemented.
   */
  public static function createFromArray(array $values) {
    if (isset($values['class']) && !in_array(ContextDefinitionInterface::class, class_implements($values['class']))) {
      throw new ContextException('ContextDefinition class must implement ' . ContextDefinitionInterface::class . '.');
    }
    // Default to Rules context definition class.
    $values['class'] = isset($values['class']) ? $values['class'] : ContextDefinition::class;
    if (!isset($values['value'])) {
      $values['value'] = 'any';
    }

    $definition = $values['class']::create($values['value']);
    foreach (array_intersect_key(static::$nameMap, $values) as $key => $name) {
      $definition->$name = $values[$key];
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedNull() {
    return $this->allowNull;
  }

  /**
   * {@inheritdoc}
   */
  public function setAllowNull($null_allowed) {
    $this->allowNull = $null_allowed;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssignmentRestriction() {
    return $this->assignmentRestriction;
  }

  /**
   * {@inheritdoc}
   */
  public function setAssignmentRestriction($restriction) {
    $this->assignmentRestriction = $restriction;
    return $this;
  }

}
