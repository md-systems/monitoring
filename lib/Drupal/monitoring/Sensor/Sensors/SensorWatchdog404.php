<?php
/**
 * @file
 * Watchdog 404 sensor.
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Extends the SensorDatabaseAggregator generic class to capture 404
 * page with highest occurrence.
 */
class SensorWatchdog404 extends SensorDatabaseAggregator {

  /**
   * {@inheritdoc}
   */
  public function alterQuery(\SelectQuery $query) {
    $query->addField('watchdog', 'message');
    $query->groupBy('message');
    $query->orderBy('records_count', 'DESC');
    $query->range(0, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    parent::runSensor($result);
    $query_result = $this->fetchObject();
    if (!empty($query_result) && !empty($query_result->message)) {
      $result->addSensorStatusMessage($query_result->message);
    }
  }
}
