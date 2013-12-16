<?php
/**
 * @file
 * Contains Drupal\monitoring\Sensor\Sensors\SensorElysiaCron
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\SensorThresholds;

/**
 * Elysia cron sensor class.
 */
class SensorElysiaCron extends SensorThresholds {

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $name = $this->info->getSetting('name');
    $query = db_select('elysia_cron', 'e')->fields('e', array($this->info->getSetting('metric')));
    $query->condition('name', $name);

    $value = $query->execute()->fetchField();

    // In case we are querying for last_run, the value is the seconds ago.
    if ($this->info->getSetting('metric') == 'last_run') {
      $value = REQUEST_TIME - $value;
      $result->addSensorStatusMessage('@time ago', array('@time' => format_interval($value)));
    }
    else {
      $result->addSensorStatusMessage('at @time', array('@time' => format_date($value)));
    }

    $result->setSensorValue($value);
  }
}
