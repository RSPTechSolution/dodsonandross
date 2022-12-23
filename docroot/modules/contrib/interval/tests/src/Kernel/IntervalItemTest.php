<?php

namespace Drupal\Tests\interval\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Defines a class for testing interval item.
 *
 * @group interval
 */
class IntervalItemTest extends FieldKernelTestBase {

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['interval'];

  /**
   * @inheritDoc
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_interval',
      'type' => 'interval',
      'entity_type' => 'entity_test',
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'settings' => [
        'default_value' => 'blank',
      ],
    ]);
    $this->field->save();
  }

  /**
   * Tests using entity fields of the interval field type.
   */
  public function testValueChange() {

    // Verify entity creation.
    $entity = EntityTest::create();
    $value = ['interval' => 30, 'period' => 'minute'];
    $entity->field_interval = $value;
    $entity->name->value = $this->randomMachineName();
    $this->entityValidateAndSave($entity);

    // Verify initial field value
    $this->assertEquals($entity->field_interval->interval, $value['interval']);
    $this->assertEquals($entity->field_interval->period, $value['period']);
    $this->assertEquals($entity->get('field_interval')->first()->buildPHPString(), '30 minutes');

    // Verify changing the date value.
    $new_value = ['interval' => 2, 'period' => 'hour'];
    $entity->field_interval = $new_value;
    $this->assertEquals($entity->field_interval->interval, $new_value['interval']);
    $this->assertEquals($entity->field_interval->period, $new_value['period']);
    $this->assertEquals($entity->get('field_interval')->first()->buildPHPString(), '2 hours');
  }

}
