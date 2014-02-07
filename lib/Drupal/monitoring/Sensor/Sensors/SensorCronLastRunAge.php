<?php

/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensors\SensorCronLastRunAge.
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Sensor\Sensor;
use Drupal\monitoring\Sensor\SensorThresholds;
use Drupal\monitoring\Sensor\Thresholds;
use Drupal\monitoring\Result\SensorResultInterface;


/**
 * Monitors the last cron run time.
 *
 * Based on the drupal core variable cron_last.
 */
class SensorCronLastRunAge extends SensorThresholds {

  protected $variable;

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $last_cron_run_before = REQUEST_TIME - variable_get('cron_last', 0);
    $result->setValue($last_cron_run_before);
    $result->addStatusMessage('@time ago', array('@time' => format_interval($last_cron_run_before)));
  }
}
