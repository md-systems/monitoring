<?php

/**
 * @file
 * SensorLastCronRun sensor.
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Sensor\Sensor;
use Drupal\monitoring\Sensor\SensorThresholds;
use Drupal\monitoring\Sensor\Thresholds;
use Drupal\monitoring\Result\SensorResultInterface;


/**
 * Class SensorLastCronRun
 *
 * Monitors the last cron run time.
 */
class SensorLastCronRun extends SensorThresholds {

  protected $variable;

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $last_cron_run_before = REQUEST_TIME - variable_get('cron_last', 0);
    $result->setSensorValue($last_cron_run_before);
    $result->addSensorStatusMessage('@time ago', array('@time' => format_interval($last_cron_run_before)));
  }
}
