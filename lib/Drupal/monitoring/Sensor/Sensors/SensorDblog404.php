<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensors\SensorDblog404.
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Extends the SensorDatabaseAggregator generic class to capture 404
 * page with highest occurrence.
 */
class SensorDblog404 extends SensorDatabaseAggregator {

  /**
   * {@inheritdoc}
   */
  public function buildQuery() {
    $query = parent::buildQuery();
    $query->addField('watchdog', 'message');
    $query->groupBy('message');
    $query->orderBy('records_count', 'DESC');
    $query->range(0, 1);
    return $query;
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
