<?php
/**
 * @file
 * Contains Drupal\monitoring\Sensor\Sensors\SensorFailedUserLogins
 */

namespace Drupal\monitoring\Sensor\Sensors;

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Aggregates user failed login watchdog messages.
 */
class SensorFailedUserLogins extends SensorDatabaseAggregator {

  /**
   * {@inheritdoc}
   */
  public function alterQuery(\SelectQuery $query) {
    $query->addField('watchdog', 'variables');
    $query->groupBy('watchdog.variables');
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $records_count = 0;

    foreach ($this->executedQuery->fetchAll() as $row) {
      $records_count += $row->records_count;
      $variables = unserialize($row->variables);
      $result->addSensorStatusMessage('@user: @count', array('@user' => $variables['%user'], '@count' => $row->records_count));
    }

    $result->setSensorValue($records_count);
  }

}
