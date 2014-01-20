<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Sensors\SensorUserFailedLogins.
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Aggregates user failed login watchdog messages.
 */
class SensorUserFailedLogins extends SensorDatabaseAggregator {

  /**
   * {@inheritdoc}
   */
  public function buildQuery() {
    $query = parent::buildQuery();
    $query->addField('watchdog', 'variables');
    $query->groupBy('watchdog.variables');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $records_count = 0;

    foreach ($this->getQueryResult()->fetchAll() as $row) {
      $records_count += $row->records_count;
      $variables = unserialize($row->variables);
      $result->addSensorStatusMessage('@user: @count', array('@user' => $variables['%user'], '@count' => $row->records_count));
    }

    $result->setSensorValue($records_count);
  }

}
