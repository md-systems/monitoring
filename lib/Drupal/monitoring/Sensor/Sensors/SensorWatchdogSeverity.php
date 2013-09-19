<?php
/**
 * @file
 * Contains Drupal\monitoring\Sensor\Sensors\SensorWatchdogSeverity
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Class SensorWatchdogSeverity
 */
class SensorWatchdogSeverity extends SensorDatabaseAggregator {

  /**
   * {@inheritdoc}
   */
  function runSensor(SensorResultInterface $result) {
    parent::runSensor($result);

    $severities = watchdog_severity_levels();
    $severity = NULL;
    foreach ($this->info->getSetting('conditions') as $condition) {
      if ($condition['field'] == 'severity') {
        $severity = $severities[$condition['value']];
      }
    }
    $result->addSensorStatusMessage($severity);
  }
}
