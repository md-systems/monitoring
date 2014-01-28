<?php
/**
 * @file
 * Contains Drupal\monitoring\Sensor\Sensors\SensorSearchApi
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\SensorThresholds;

/**
 * Monitors unindexed items for a search api index.
 */
class SensorSearchApi extends SensorThresholds {

  /**
   * {@inheritdoc}
   */
  function runSensor(SensorResultInterface $result) {
    $indexes = search_api_index_load_multiple(array($this->info->getSetting('index_id')));
    $index = reset($indexes);

    $status = search_api_index_status($index);

    $result->setSensorValue($status['total'] - $status['indexed']);
  }

}
