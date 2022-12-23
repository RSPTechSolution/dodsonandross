<?php

namespace Drupal\recurring_period\Plugin\RecurringPeriod;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a period based on a rolling interval from the start date.
 *
 * @RecurringPeriod(
 *   id = "rolling_interval",
 *   label = @Translation("Rolling interval"),
 *   description = @Translation("Provide a period based on a rolling interval"),
 * )
 */
class RollingInterval extends RecurringPeriodBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // The interval configuration.
      'interval' => [
        // The interval period. This is the ID of an interval plugin, for
        // example 'month'.
        'period' => '',
        // The interval. This is a value which multiplies the period.
        'interval' => '',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['interval'] = [
      '#type' => 'interval',
      '#title' => 'Interval',
      '#default_value' => $config['interval'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);

    $this->configuration['interval'] = $values['interval'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDate(\DateTimeImmutable $start) {
    return $this->calculateEnd($start);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateStart(\DateTimeImmutable $date) {
    // For a rolling interval, the start date is the same as the given date.
    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateEnd(\DateTimeImmutable $start) {
    // Get our interval values from our configuration.
    $config = $this->getConfiguration();
    $interval_configuration = $config['interval'];
    // The interval plugin ID is the 'period' value.
    $interval_plugin_id = $interval_configuration['period'];

    // Create a DateInterval that represents the interval.
    // TODO: This can be removed when https://www.drupal.org/node/2900435 lands.
    $interval_plugin_definition = $this->pluginManagerIntervals->getDefinition($interval_plugin_id);
    $value = $interval_configuration['interval'] * $interval_plugin_definition['multiplier'];
    $date_interval = \DateInterval::createFromDateString($value . ' ' . $interval_plugin_definition['php']);
    return $start->add($date_interval);
  }

  /**
   * {@inheritdoc}
   */
  public function getPeriodLabel(\DateTimeImmutable $start, \DateTimeImmutable $end) {
    // Get our interval values from our configuration.
    $config = $this->getConfiguration();
    $interval_configuration = $config['interval'];
    // The interval plugin ID is the 'period' value.
    $interval_plugin_id = $interval_configuration['period'];
    $interval_plugin_definition = $this->pluginManagerIntervals->getDefinition($interval_plugin_id);

    return $this->t("@count @interval from @start-date", [
      '@count' => $config['interval']['interval'],
      '@interval' => ($config['interval']['interval'] == 1)
        ? $interval_plugin_definition['singular']
        : $interval_plugin_definition['plural'],
      '@start-date' => $start->format(\DateTimeInterface::RSS),
    ]);
  }

}
