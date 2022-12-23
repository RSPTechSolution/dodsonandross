<?php

namespace Drupal\recurring_period\Plugin\RecurringPeriod;

use Drupal\recurring_period\Datetime\Period;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\interval\IntervalPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for recurring period plugins.
 */
abstract class RecurringPeriodBase extends PluginBase implements ContainerFactoryPluginInterface, RecurringPeriodInterface {

  use StringTranslationTrait;

  /**
   * The Interval Plugin Manager service.
   *
   * @var \Drupal\interval\IntervalPluginManagerInterface
   */
  protected $pluginManagerIntervals;

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\interval\IntervalPluginManagerInterface $plugin_manager_interval_intervals
   *   The Interval Plugin Manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    IntervalPluginManagerInterface $plugin_manager_interval_intervals
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->pluginManagerIntervals = $plugin_manager_interval_intervals;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.interval.intervals')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateStart(\DateTimeImmutable $date) {
    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateEnd(\DateTimeImmutable $start) {
    // Call the deprecated method in the plugin class.
    // TODO: reverse this, so we implement the deprecated method.
    return $this->calculateDate($start);
  }

  /**
   * {@inheritdoc}
   */
  public function getPeriodLabel(\DateTimeImmutable $start, \DateTimeImmutable $end) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPeriodFromDate(\DateTimeImmutable $start) {
    $end_date = $this->calculateDate($start);
    $label = $this->getPeriodLabel($start, $end_date);

    return new Period($start, $end_date, $label);
  }

  /**
   * {@inheritdoc}
   */
  public function getPeriodContainingDate(\DateTimeImmutable $date) {
    $start_date = $this->calculateStart($date);
    $end_date = $this->calculateDate($date);

    $label = $this->getPeriodLabel($start_date, $end_date);

    return new Period($start_date, $end_date, $label);
  }

  /**
   * {@inheritdoc}
   */
  public function getNextPeriod(Period $period) {
    $end_date = $period->getEndDate();

    return $this->getPeriodFromDate($end_date);
  }

}
