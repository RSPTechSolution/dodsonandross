<?php

namespace Drupal\recurring_period\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides base fields and accessors for entities that represent a time period.
 *
 * This can be used in two ways:
 *  - with a single datetime_range field (and thus requires datetime_range
 *    module)
 *  - with two date fields (and requires only date module).
 *
 * The entity type must define entity keys for the date fields, either:
 *  - for the single datetime_range field:
 *    - 'date_range'
 *  - for the pair of date fields:
 *    - 'start_date'
 *    - 'end_date'
 */
trait PeriodEntityTrait {

  /**
   * Implements \Drupal\recurring_period\Entity\PeriodEntityInterface::getStartDate().
   */
  public function getStartDate() {
    if ($this->getEntityType()->hasKey('date_range')) {
      $date_range_field = $this->getEntityType()->getKey('date_range');

      return $this->{$date_range_field}->start_date;
    }
    else {
      $start_date_field = $this->getEntityType()->getKey('start_date');

      return $this->{$start_date_field}->date;
    }
  }

  /**
   * Implements \Drupal\recurring_period\Entity\PeriodEntityInterface::getEndDate().
   */
  public function getEndDate() {
    if ($this->getEntityType()->hasKey('date_range')) {
      $date_range_field = $this->getEntityType()->getKey('date_range');

      return $this->{$date_range_field}->end_date;
    }
    else {
      $end_date_field = $this->getEntityType()->getKey('end_date');

      return $this->{$end_date_field}->date;
    }
  }

  /**
   * Implements \Drupal\recurring_period\Entity\PeriodEntityInterface::getDuration().
   */
  public function getDuration() {
    return $this->getEndDate()->format('U') - $this->getStartDate()->format('U');
  }

  /**
   * Implements \Drupal\recurring_period\Entity\PeriodEntityInterface::contains().
   */
  public function contains(DrupalDateTime $date) {
    // Unlike DateTime, DrupalDateTime objects can't be compared directly.
    $timestamp = $date->format('U');
    $starts = $this->getStartDate()->format('U');
    $ends = $this->getEndDate()->format('U');

    return $timestamp >= $starts && $timestamp < $ends;
  }

  /**
   * Returns an array of base field definitions for representing a time period.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to add the period fields to.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   An array of base field definitions.
   *
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   *   Thrown when the entity type does not implement PeriodEntityInterface
   *   or if it does not the necessary entity keys.
   */
  public static function periodBaseFieldDefinitions(EntityTypeInterface $entity_type) {
    if (!is_subclass_of($entity_type->getClass(), PeriodEntityInterface::class)) {
      throw new UnsupportedEntityTypeDefinitionException('The entity type ' . $entity_type->id() . ' does not implement \Drupal\recurring_period\Entity\PeriodEntityInterface.');
    }

    if ($entity_type->hasKey('date_range')) {
      return [
        $entity_type->getKey('date_range') => BaseFieldDefinition::create('daterange')
          ->setLabel(new TranslatableMarkup('Period date'))
          ->setRevisionable(TRUE)
          ->setDefaultValue(TRUE)
          ->setDisplayOptions("view", [
            'type' => "daterange_default",
            'weight' => "5",
          ])
          ->setDisplayConfigurable("view", TRUE)
          ->setDisplayOptions("form", [
            'type' => "daterange_default",
            'weight' => "5",
          ])
          ->setDisplayConfigurable("form", TRUE),
      ];
    }
    elseif ($entity_type->hasKey('start_date') && $entity_type->hasKey('end_date')) {
      return [
        $entity_type->getKey('start_date') => BaseFieldDefinition::create('date')
          ->setLabel(new TranslatableMarkup('Period start date'))
          ->setRevisionable(TRUE)
          ->setDefaultValue(TRUE)
          ->setDisplayConfigurable("view", TRUE)
          ->setDisplayConfigurable("form", TRUE),
        $entity_type->getKey('end_date') => BaseFieldDefinition::create('date')
          ->setLabel(new TranslatableMarkup('Period end date'))
          ->setRevisionable(TRUE)
          ->setDefaultValue(TRUE)
          ->setDisplayConfigurable("view", TRUE)
          ->setDisplayConfigurable("form", TRUE),
      ];
    }
    else {
      throw new UnsupportedEntityTypeDefinitionException('The entity type ' . $entity_type->id() . ' does not have either a "date_range" or "start_date" and "end_date" entity keys.');
    }
  }

}